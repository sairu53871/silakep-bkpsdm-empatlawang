@echo off
cd /d "%~dp0"
echo Semua data akan DIHAPUS dan diisi ulang dengan data dummy.
pause
php artisan migrate:fresh --seed
echo Selesai. Jalankan jalankan.bat untuk membuka aplikasi.
pause
