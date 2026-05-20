@echo off
REM ===========================================================
REM HeySentinel - Start Local Stack
REM Double-click to bring up Laravel + MySQL + Redis + Mailpit
REM ===========================================================

setlocal
cd /d "%~dp0"

echo.
echo === HeySentinel: starting local stack ===
echo.

REM Check Docker daemon
docker info >nul 2>&1
if errorlevel 1 (
    echo [ERROR] Docker Desktop is not running.
    echo         Start Docker Desktop and try again.
    echo.
    pause
    exit /b 1
)

REM Set required env vars that PowerShell would normally export
set WWWUSER=1000
set WWWGROUP=1000
set PWD=%CD%

REM Bring stack up
docker compose up -d
if errorlevel 1 (
    echo.
    echo [ERROR] docker compose up failed. See output above.
    pause
    exit /b 1
)

echo.
echo Waiting for MySQL to be ready...
:wait_mysql
docker compose exec -T mysql mysqladmin ping -ppassword --silent >nul 2>&1
if errorlevel 1 (
    timeout /t 2 /nobreak >nul
    goto wait_mysql
)

echo.
echo === Stack is UP ===
echo.
echo   App           http://localhost:8080
echo   Filament      http://localhost:8080/admin
echo   Horizon       http://localhost:8080/horizon
echo   Mailpit       http://localhost:8026
echo   MySQL         localhost:3307  (user=sail  pass=password  db=heysentinel)
echo   Redis         localhost:6380
echo.
echo Useful next steps:
echo   status.bat                              ^<- check service health
echo   stop.bat                                ^<- stop everything
echo   docker compose exec laravel.test bash   ^<- shell inside container
echo.

endlocal
pause
