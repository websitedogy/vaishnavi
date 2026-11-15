@echo off
set "PATH=%~dp0;C:\laragon\bin\php\php-8.2.29;%PATH%"
"C:\laragon\bin\php\php-8.2.29\php.exe" -c "%~dp0php.ini" "C:\laragon\bin\composer\composer.phar" %*
