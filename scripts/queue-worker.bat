@echo off
REM ---------------------------------------------------------------------------
REM CSV Import - persistent queue worker
REM Keeps `php artisan queue:work` alive. --max-time makes the worker exit once
REM an hour so this loop relaunches it with fresh code + memory (workers cache
REM code in memory, so this also picks up deploys automatically). If the worker
REM ever crashes, the loop restarts it after a short pause.
REM ---------------------------------------------------------------------------
cd /d D:\xampp\htdocs\csv_import

:loop
D:\xampp\php\php.exe artisan queue:work --tries=3 --timeout=180 --max-time=3600 --sleep=2
echo Worker exited at %date% %time%, restarting in 3s... >> storage\logs\queue-worker.log
timeout /t 3 /nobreak >nul
goto loop
