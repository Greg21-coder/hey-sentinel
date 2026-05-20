@echo off
REM ===========================================================
REM HeySentinel - Stop Local Stack
REM Stops containers but preserves DB/Redis volumes
REM ===========================================================

setlocal
cd /d "%~dp0"

set WWWUSER=1000
set WWWGROUP=1000
set PWD=%CD%

echo.
echo === HeySentinel: stopping local stack ===
echo.

docker compose stop
if errorlevel 1 (
    echo [ERROR] docker compose stop failed.
    pause
    exit /b 1
)

echo.
echo Stack stopped. Data volumes preserved.
echo Run start.bat to bring it back up.
echo.

endlocal
pause
