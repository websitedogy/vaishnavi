@echo off
cd /d "%~dp0"
call "%~dp0php.bat" vendor/phpunit/phpunit/phpunit %*
