@echo off
REM Bootstrap script untuk setup Laravel Digi di Windows

echo.
echo ================================
echo DIGI Laravel Setup Script (Windows)
echo ================================
echo.

REM 1. Install Dependencies
echo 1. Installing Composer Dependencies...
call composer install
if %ERRORLEVEL% NEQ 0 (
    echo Error installing composer dependencies!
    pause
    exit /b 1
)

REM 2. Generate Key
echo.
echo 2. Generating Application Key...
call php artisan key:generate
if %ERRORLEVEL% NEQ 0 (
    echo Error generating key!
    pause
    exit /b 1
)

REM 3. Run Migrations
echo.
echo 3. Running Migrations...
call php artisan migrate --force
if %ERRORLEVEL% NEQ 0 (
    echo Error running migrations!
    pause
    exit /b 1
)

REM 4. Create Symlink for Storage
echo.
echo 4. Creating Storage Symlink...
call php artisan storage:link
if %ERRORLEVEL% NEQ 0 (
    echo Warning: Could not create storage symlink (may need admin rights)
)

REM 5. Cache Config
echo.
echo 5. Caching Configuration...
call php artisan config:cache

echo.
echo ================================
echo Setup Complete!
echo ================================
echo.
echo Next steps:
echo 1. Access the application at: http://localhost/xampp/digi/laravel-app/public
echo 2. Create test users via database or seed
echo 3. Configure email settings in .env
echo.
pause
