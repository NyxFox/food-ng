document.addEventListener('DOMContentLoaded', () => {
  const toggle = document.querySelector('[data-nav-toggle]');
  const nav = document.querySelector('[data-nav]');

  if (toggle && nav) {
    toggle.addEventListener('click', () => {
      const open = nav.classList.toggle('is-open');
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
  }

  document.querySelectorAll('form[data-confirm]').forEach((form) => {
    form.addEventListener('submit', (event) => {
      const message = form.getAttribute('data-confirm') || 'Aktion wirklich ausführen?';

      if (!window.confirm(message)) {
        event.preventDefault();
      }
    });
  });

  const updaterConsole = document.querySelector('[data-updater-console]');
  const updaterStatus = document.querySelector('[data-updater-status]');
  const updaterOutput = document.querySelector('[data-updater-output]');
  const updaterButton = document.querySelector('[data-updater-start]');

  if (updaterConsole && updaterStatus && updaterOutput && updaterButton) {
    const streamUrl = updaterConsole.getAttribute('data-stream-url') || '';
    const csrfField = updaterConsole.getAttribute('data-csrf-field') || '_csrf';
    const csrfToken = updaterConsole.getAttribute('data-csrf-token') || '';
    const autoStart = updaterConsole.getAttribute('data-auto-start') === 'true';

    const appendOutput = (text) => {
      updaterOutput.textContent += text;
      updaterOutput.scrollTop = updaterOutput.scrollHeight;
    };

    const setStatus = (text, isError = false) => {
      updaterStatus.textContent = text;
      updaterStatus.classList.toggle('flash--error', isError);
      updaterStatus.classList.toggle('flash--success', !isError);
    };

    let running = false;

    const runUpdater = async () => {
      if (running || !streamUrl) {
        return;
      }

      running = true;
      updaterButton.disabled = true;
      updaterOutput.textContent = '';
      setStatus('Update-Lauf wird vorbereitet...');

      try {
        const body = new URLSearchParams();
        body.set(csrfField, csrfToken);

        const response = await fetch(streamUrl, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
            'X-Requested-With': 'XMLHttpRequest',
          },
          body: body.toString(),
        });

        if (!response.ok || !response.body) {
          throw new Error('Stream-Antwort konnte nicht geöffnet werden.');
        }

        const reader = response.body.getReader();
        const decoder = new TextDecoder();
        let buffer = '';

        while (true) {
          const { value, done } = await reader.read();

          if (done) {
            break;
          }

          buffer += decoder.decode(value, { stream: true });
          const lines = buffer.split('\n');
          buffer = lines.pop() || '';

          lines.forEach((line) => {
            if (!line.trim()) {
              return;
            }

            let payload;

            try {
              payload = JSON.parse(line);
            } catch (error) {
              appendOutput(line + '\n');
              return;
            }

            if (payload.type === 'line' && typeof payload.text === 'string') {
              appendOutput(payload.text);
            }

            if (payload.type === 'status' && typeof payload.message === 'string') {
              setStatus(payload.message);
            }

            if (payload.type === 'result' && typeof payload.message === 'string') {
              appendOutput('\n' + payload.message + '\n');
              setStatus(payload.message, payload.success === false);
            }
          });
        }
      } catch (error) {
        const message = error instanceof Error ? error.message : 'Update-Lauf fehlgeschlagen.';
        appendOutput('\n' + message + '\n');
        setStatus(message, true);
      } finally {
        running = false;
        updaterButton.disabled = false;
      }
    };

    updaterButton.addEventListener('click', () => {
      runUpdater();
    });

    if (autoStart) {
      runUpdater();
    }
  }
});
