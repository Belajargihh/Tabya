@echo off
title Tabya - Cloudflare Tunnel (Online Server)
color 0b
cls
echo ================================================================
echo           TABYA - ONLINEKAN KE INTERNET (CLOUDFLARE TUNNEL)
echo ================================================================
echo.
echo [1] Pastikan Laragon (Apache ^& MySQL) sudah dalam status START.
echo [2] Script ini akan membuat link internet resmi ber-HTTPS.
echo [3] Link ini BISA DIBUKA SIAPA SAJA dari HP, laptop penguji, dll.
echo.

if not exist "%~dp0tools\cloudflared.exe" (
    echo [INFO] Mengunduh cloudflared binary pertama kali (hanya sekali)...
    if not exist "%~dp0tools" mkdir "%~dp0tools"
    powershell -Command "Invoke-WebRequest -Uri 'https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-windows-amd64.exe' -OutFile '%~dp0tools\cloudflared.exe'"
)

echo Sedang menghubungkan ke Cloudflare...
echo Tunggu beberapa detik sampai muncul kotak URL "https://...trycloudflare.com"
echo.
echo (Tekan Ctrl + C jika ingin mematikan link online ini)
echo ================================================================
echo.

"%~dp0tools\cloudflared.exe" tunnel --url http://localhost:80

echo.
echo ================================================================
echo Tunnel telah ditutup.
echo ================================================================
pause
