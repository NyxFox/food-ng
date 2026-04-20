import * as pdfjsLib from '../vendor/pdfjs/pdf.mjs';

pdfjsLib.GlobalWorkerOptions.workerSrc = new URL('../vendor/pdfjs/pdf.worker.mjs', import.meta.url).toString();

const resizeDelay = 150;

function parsePageSelection(datasetValue, totalPages) {
  if (!datasetValue || datasetValue === 'all') {
    return Array.from({ length: totalPages }, (_, index) => index + 1);
  }

  return datasetValue
    .split(',')
    .map((value) => Number.parseInt(value.trim(), 10))
    .filter((value) => Number.isInteger(value) && value >= 1 && value <= totalPages);
}

async function renderViewer(container) {
  const pdfUrl = container.dataset.pdfUrl;

  if (!pdfUrl) {
    return;
  }

  container.innerHTML = '<div class="pdf-viewer__message">PDF wird geladen …</div>';

  try {
    const loadingTask = pdfjsLib.getDocument({ url: pdfUrl });
    const pdf = await loadingTask.promise;
    const pageNumbers = parsePageSelection(container.dataset.pages, pdf.numPages);

    container.innerHTML = '';

    for (const pageNumber of pageNumbers) {
      const page = await pdf.getPage(pageNumber);
      const initialViewport = page.getViewport({ scale: 1 });
      const availableWidth = Math.max(container.clientWidth, 240);
      const scale = availableWidth / initialViewport.width;
      const viewport = page.getViewport({ scale });
      const wrapper = document.createElement('div');
      const canvas = document.createElement('canvas');
      const context = canvas.getContext('2d', { alpha: false });

      canvas.width = viewport.width;
      canvas.height = viewport.height;
      canvas.className = 'pdf-viewer__canvas';
      wrapper.className = 'pdf-viewer__page';
      wrapper.appendChild(canvas);
      container.appendChild(wrapper);

      await page.render({
        canvasContext: context,
        viewport,
      }).promise;
    }
  } catch (error) {
    container.innerHTML = '<div class="pdf-viewer__message">PDF-Vorschau konnte nicht geladen werden.</div>';
    console.error(error);
  }
}

function mountViewer(container) {
  let timer = null;
  let lastWidth = Math.round(container.clientWidth);

  const schedule = () => {
    window.clearTimeout(timer);
    timer = window.setTimeout(() => {
      const width = Math.round(container.clientWidth);

      if (width !== lastWidth) {
        lastWidth = width;
        renderViewer(container);
      }
    }, resizeDelay);
  };

  renderViewer(container);
  window.addEventListener('resize', schedule);
}

document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.pdf-viewer[data-pdf-url]').forEach((container) => {
    mountViewer(container);
  });
});
