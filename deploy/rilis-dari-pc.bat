@echo off
setlocal
rem Membuat rilis baru dari komputer kantor (klik dua kali). Lihat README bagian "Tanda tangan rilis".
rem Butuh: Git for Windows (https://git-scm.com/download/win) + kunci dari buat-kunci-cadangan.bat
set "PHPEXE=%~dp0..\..\..\php\php.exe"
if not exist "%PHPEXE%" set "PHPEXE=C:\xampp\php\php.exe"
if not exist "%PHPEXE%" set "PHPEXE=php"
where git >nul 2>nul
if errorlevel 1 (
  echo Git belum terpasang. Unduh dan pasang Git for Windows dari https://git-scm.com/download/win
  echo ^(pilihan bawaan saat instalasi sudah cukup^), lalu jalankan berkas ini lagi.
  goto selesai
)
set "REPO=%USERPROFILE%\satker-rilis\repo"
if not exist "%REPO%\.git" (
  echo Mengunduh repositori ke %REPO% ...
  git clone https://github.com/rohmansyah1228/website-satker-pengadilan-agama.git "%REPO%" || goto gagal
)
cd /d "%REPO%"
git config user.email >nul 2>nul || git config user.email "rohmansyah1228@users.noreply.github.com"
git config user.name >nul 2>nul || git config user.name "Pengelola Website Satker"
echo Mengambil perubahan terbaru dari GitHub ...
git pull --ff-only origin main || goto gagal
echo.
"%PHPEXE%" deploy\rilis.php --cek || goto gagal
echo.
set "CATATAN="
set /p CATATAN=Catatan rilis (ringkas, kosongkan untuk batal): 
if "%CATATAN%"=="" goto selesai
set "VERSI="
set /p VERSI=Nomor versi (kosongkan = otomatis): 
"%PHPEXE%" deploy\rilis.php "%CATATAN%" %VERSI% || goto gagal
echo.
set "OK="
set /p OK=Kirim (push) ke GitHub sekarang? (y/n): 
if /i not "%OK%"=="y" (
  echo Belum dikirim. Untuk mengirim nanti: buka %REPO% lalu jalankan: git push origin main
  goto selesai
)
git push origin main || goto gagal
echo.
echo SELESAI. Semua satker akan melihat "Versi baru tersedia" di Dashboard.
goto selesai
:gagal
echo.
echo GAGAL. Baca pesan di atas.
:selesai
echo.
pause
