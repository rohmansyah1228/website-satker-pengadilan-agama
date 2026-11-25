@echo off
rem Membuat kunci cadangan penandatangan rilis (klik dua kali). Lihat deploy\buat-kunci-cadangan.php
set "PHPEXE=%~dp0..\..\..\php\php.exe"
if not exist "%PHPEXE%" set "PHPEXE=php"
"%PHPEXE%" "%~dp0buat-kunci-cadangan.php"
echo.
pause
