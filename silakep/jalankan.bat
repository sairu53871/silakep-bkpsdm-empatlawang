@echo off
cd /d "%~dp0"
echo ==============================================
echo   SILAKEP - MVP KGB  (BKPSDM Empat Lawang)
echo   Buka di browser: http://localhost:8000
echo   Tutup jendela ini untuk menghentikan server
echo ==============================================
if not exist database\database.sqlite (
    type nul > database\database.sqlite
    php artisan migrate:fresh --seed
)
start "" http://localhost:8000
php artisan serve --port=8000
