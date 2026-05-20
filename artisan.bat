@echo off
REM ===========================================================
REM HeySentinel - Run artisan inside container
REM Usage:  artisan migrate
REM         artisan make:filament-user
REM         artisan tinker
REM ===========================================================

setlocal
cd /d "%~dp0"

set WWWUSER=1000
set WWWGROUP=1000
set PWD=%CD%

docker compose exec laravel.test php artisan %*

endlocal
