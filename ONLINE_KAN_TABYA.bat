@echo off
setlocal
cd /d "%~dp0"
title Tabya - Cloudflare Tunnel (Online Server)
color 0b
cls

echo ================================================================
echo           TABYA - ONLINEKAN KE INTERNET (CLOUDFLARE)
echo ================================================================
echo.
echo 1. Pastikan Laragon [Apache dan MySQL] sudah START!
echo 2. Script ini akan membuat link internet ber-HTTPS resmi.
echo 3. Link bisa dibuka siapa saja dari HP atau laptop dosen.
echo.
echo Sedang menghubungkan ke Cloudflare...
echo Tunggu sebentar sampai muncul link trycloudflare.com
echo.
echo [INFO] Tekan Ctrl + C jika ingin menghentikan link online.
echo ================================================================
echo.

if exist "tools\cloudflared.exe" goto RUN_TUNNEL

echo Mengunduh cloudflared...
if not exist "tools" mkdir "tools"
powershell -Command "Invoke-WebRequest -Uri 'https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-windows-amd64.exe' -OutFile 'tools\cloudflared.exe'"

:RUN_TUNNEL
"tools\cloudflared.exe" tunnel --url http://localhost:80

echo.
echo ================================================================
echo Tunnel selesai atau telah ditutup.
echo ================================================================
pause
