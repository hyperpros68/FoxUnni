@echo off
set "PHP_BIN=D:\Tools\php-8.5.1-nts-Win32-vs17-x64\php.exe"
set "WWW_DIR=D:\Project\FoxUnni\www"
set "PORT=9001"

echo ===================================================
echo   FoxUnni Local Web Service (Port %PORT%)
echo   URL: http://localhost:%PORT%/login.php
echo ===================================================
echo Press Ctrl+C to terminate.
echo.

"%PHP_BIN%" -S 0.0.0.0:%PORT% -t "%WWW_DIR%"
pause
