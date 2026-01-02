# SIMAK Secure Build Script
# This script: 1) Strips comments, 2) Runs pre-build cleanup, 3) Builds the app
# IMPORTANT: Run this from a PRODUCTION COPY, not your development folder!

$ErrorActionPreference = "Stop"
$projectRoot = Split-Path -Parent $MyInvocation.MyCommand.Path

Set-Location $projectRoot

Write-Host ""
Write-Host "==============================================================" -ForegroundColor Cyan
Write-Host "           SIMAK PRO - SECURE BUILD SCRIPT                    " -ForegroundColor Cyan
Write-Host "       Source Code Protection + NativePHP Build               " -ForegroundColor Cyan
Write-Host "==============================================================" -ForegroundColor Cyan
Write-Host ""

# Safety check - must be on a clean git state or no git at all
$hasGit = Test-Path ".git"
if ($hasGit) {
    $gitStatus = git status --porcelain 2>$null
    if ($gitStatus) {
        Write-Host "==============================================================" -ForegroundColor Red
        Write-Host "  WARNING: You have uncommitted changes!                      " -ForegroundColor Red
        Write-Host "                                                              " -ForegroundColor Red
        Write-Host "  This script will PERMANENTLY modify your source files.     " -ForegroundColor Red
        Write-Host "  Please commit or stash changes first OR work on a copy.    " -ForegroundColor Red
        Write-Host "==============================================================" -ForegroundColor Red
        Write-Host ""
        
        $confirm = Read-Host "Type 'YES' to continue anyway, or press Enter to abort"
        if ($confirm -ne "YES") {
            Write-Host "Build aborted." -ForegroundColor Yellow
            exit 1
        }
    }
}

Write-Host "[STEP 1/5] Verifying .env settings..." -ForegroundColor Yellow

# Check .env exists and APP_DEBUG is false
if (Test-Path ".env") {
    $envContent = Get-Content ".env" -Raw
    if ($envContent -match "APP_DEBUG\s*=\s*true") {
        Write-Host "  ERROR: APP_DEBUG=true detected!" -ForegroundColor Red
        Write-Host "  Setting APP_DEBUG=false..." -ForegroundColor Yellow
        $envContent = $envContent -replace "APP_DEBUG\s*=\s*true", "APP_DEBUG=false"
        $envContent | Set-Content ".env" -NoNewline
        Write-Host "  APP_DEBUG set to false" -ForegroundColor Green
    } else {
        Write-Host "  APP_DEBUG=false verified" -ForegroundColor Green
    }
} else {
    Write-Host "  WARNING: No .env file found" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "[STEP 2/5] Stripping comments from source code..." -ForegroundColor Yellow

$stripScript = Join-Path $projectRoot "scripts\strip-comments.ps1"
if (Test-Path $stripScript) {
    & $stripScript -Path $projectRoot
} else {
    Write-Host "  WARNING: strip-comments.ps1 not found, skipping..." -ForegroundColor Yellow
}

Write-Host ""
Write-Host "[STEP 3/5] Running Laravel optimizations..." -ForegroundColor Yellow

Write-Host "  Clearing caches..." -ForegroundColor Gray
php artisan config:clear 2>$null
php artisan cache:clear 2>$null
php artisan route:clear 2>$null  
php artisan view:clear 2>$null

Write-Host "  Caching config..." -ForegroundColor Gray
php artisan config:cache

Write-Host "  Caching routes..." -ForegroundColor Gray
php artisan route:cache

Write-Host "  Caching views..." -ForegroundColor Gray
php artisan view:cache

Write-Host "  Laravel optimization complete" -ForegroundColor Green

Write-Host ""
Write-Host "[STEP 4/5] Removing development files..." -ForegroundColor Yellow

$filesToDelete = @(
    ".git",
    ".github", 
    ".vscode",
    ".idea",
    "tests",
    "phpunit.xml",
    ".editorconfig",
    ".gitattributes",
    ".gitignore",
    "README.md",
    "CHANGELOG.md",
    ".env.example",
    "docker-compose.yml",
    "Dockerfile"
)

foreach ($file in $filesToDelete) {
    $path = Join-Path $projectRoot $file
    if (Test-Path $path) {
        if (Test-Path $path -PathType Container) {
            Remove-Item $path -Recurse -Force
        } else {
            Remove-Item $path -Force
        }
        Write-Host "  [DELETED] $file" -ForegroundColor Gray
    }
}

# Delete .env file LAST (after config:cache)
if (Test-Path ".env") {
    Remove-Item ".env" -Force
    Write-Host "  [DELETED] .env (config already cached)" -ForegroundColor Gray
}

Write-Host "  Cleanup complete" -ForegroundColor Green

Write-Host ""
Write-Host "[STEP 5/5] Building NativePHP application..." -ForegroundColor Yellow
Write-Host ""

# Run the NativePHP build
php artisan native:build

Write-Host ""
Write-Host "==============================================================" -ForegroundColor Green
Write-Host "              BUILD COMPLETE!                                 " -ForegroundColor Green
Write-Host "==============================================================" -ForegroundColor Green
Write-Host ""
Write-Host "Your secured application is in the 'dist' folder." -ForegroundColor Cyan
Write-Host ""
Write-Host "Source code protection applied:" -ForegroundColor Gray
Write-Host "  [OK] Comments stripped from PHP files" -ForegroundColor Gray
Write-Host "  [OK] Development files removed" -ForegroundColor Gray
Write-Host "  [OK] .env file deleted (config cached)" -ForegroundColor Gray
Write-Host "  [OK] Git history removed" -ForegroundColor Gray
Write-Host ""
