@echo off
echo Tailwind CSS Build Baslatiliyor...
echo.

call npx tailwindcss -i ./assets/css/input.css -o ./assets/css/main.min.css --minify

if %ERRORLEVEL% GEQ 1 (
    echo.
    echo HATA: Build islemi basarisiz oldu!
    echo Lutfen Node.js'in yuklu oldugundan emin olun.
    pause
    exit /b 1
)

echo.
echo BASARILI! assets/css/main.min.css olusturuldu.
echo Simdi bu dosyayi sunucuya yukleyin.
pause
