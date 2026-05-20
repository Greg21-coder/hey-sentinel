@echo off
REM ===========================================================
REM HeySentinel - Open shell inside Laravel container
REM Use for artisan, composer, npm, pest, mysql client, etc.
REM ===========================================================

setlocal
cd /d "%~dp0"

set WWWUSER=1000
set WWWGROUP=1000
set PWD=%CD%

docker compose exec laravel.test bash

endlocal
