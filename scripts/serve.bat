@echo off
setlocal

cd /d "%~dp0.."
php -S 127.0.0.1:8080 -t public
