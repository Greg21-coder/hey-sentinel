@echo off
REM ===========================================================
REM HeySentinel - Status Check
REM ===========================================================

setlocal
cd /d "%~dp0"

set WWWUSER=1000
set WWWGROUP=1000
set PWD=%CD%

echo.
echo === HeySentinel: stack status ===
echo.

docker info >nul 2>&1
if errorlevel 1 (
    echo [WARNING] Docker Desktop is not running.
    echo.
    pause
    exit /b 1
)

docker compose ps

echo.
echo === Access URLs ===
echo   App:       http://localhost:8080
echo   Filament:  http://localhost:8080/admin
echo   Horizon:   http://localhost:8080/horizon
echo   Mailpit:   http://localhost:8026
echo   MySQL:     localhost:3307
echo   Redis:     localhost:6380
echo.

endlocal
pause
