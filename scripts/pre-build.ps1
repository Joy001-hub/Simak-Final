# Master Pre-Build Security Cleanup Script for Laravel NativePHP
# Run this script on a PRODUCTION COPY of your codebase, NOT on development!

param(
    [switch]$SkipCommentStrip = $false,
    [switch]$SkipConfigCache = $false,
    [switch]$SkipCleanup = $false,
    [switch]$DryRun = $false
)

$ErrorActionPreference = "Stop"
$projectRoot = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)

Set-Location $projectRoot

Write-Host ""
Write-Host "========================================================" -ForegroundColor Magenta
Write-Host "  SIMAK PRO - Pre-Build Security Cleanup" -ForegroundColor Magenta
Write-Host "========================================================" -ForegroundColor Magenta
Write-Host ""

if ($DryRun) {
    Write-Host "[DRY RUN MODE] No changes will be made" -ForegroundColor Yellow
    Write-Host ""
}

# ============================================================
# Step 0: Validation
# ============================================================
Write-Host "[Step 0] Validating environment..." -ForegroundColor Cyan

if (-not (Test-Path "artisan")) {
    Write-Host "ERROR: This script must be run from Laravel project root" -ForegroundColor Red
    exit 1
}

if (-not (Test-Path ".env")) {
    Write-Host "WARNING: .env file not found. Config may already be cached." -ForegroundColor Yellow
}

Write-Host "  Project root: $projectRoot" -ForegroundColor Gray
Write-Host "  Validation passed" -ForegroundColor Green
Write-Host ""

# ============================================================
# Step 1: Strip Comments
# ============================================================
if (-not $SkipCommentStrip) {
    Write-Host "[Step 1] Stripping comments from PHP/JS/Blade files..." -ForegroundColor Cyan
    
    $stripScript = Join-Path $projectRoot "scripts\strip-comments.ps1"
    
    if (Test-Path $stripScript) {
        if ($DryRun) {
            & $stripScript -Path $projectRoot -DryRun
        }
        else {
            & $stripScript -Path $projectRoot
        }
    }
    else {
        Write-Host "  WARNING: strip-comments.ps1 not found, skipping..." -ForegroundColor Yellow
    }
    Write-Host ""
}
else {
    Write-Host "[Step 1] SKIPPED: Comment stripping" -ForegroundColor Yellow
    Write-Host ""
}

# ============================================================
# Step 2: Config Caching
# ============================================================
if (-not $SkipConfigCache) {
    Write-Host "[Step 2] Caching configuration..." -ForegroundColor Cyan
    
    # Verify APP_DEBUG is false
    if (Test-Path ".env") {
        $envContent = Get-Content ".env" -Raw
        if ($envContent -match "APP_DEBUG\s*=\s*true") {
            Write-Host "  ERROR: APP_DEBUG is set to true in .env" -ForegroundColor Red
            Write-Host "  Please set APP_DEBUG=false before building" -ForegroundColor Red
            exit 1
        }
        Write-Host "  APP_DEBUG=false verified" -ForegroundColor Green
    }
    
    if (-not $DryRun) {
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
        
        # Delete .env file
        if (Test-Path ".env") {
            Remove-Item ".env" -Force
            Write-Host "  .env file deleted" -ForegroundColor Green
        }
    }
    else {
        Write-Host "  [DRY RUN] Would clear and cache configs" -ForegroundColor Yellow
        Write-Host "  [DRY RUN] Would delete .env file" -ForegroundColor Yellow
    }
    Write-Host ""
}
else {
    Write-Host "[Step 2] SKIPPED: Config caching" -ForegroundColor Yellow
    Write-Host ""
}

# ============================================================
# Step 3: Remove Development Files
# ============================================================
if (-not $SkipCleanup) {
    Write-Host "[Step 3] Removing development artifacts..." -ForegroundColor Cyan
    
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
        "DUMMY_DATA.md",
        "DUMMY_DATA_LARAVEL.md",
        "DUMMY_DATA_QUICKSTART.md",
        "PANDUAN_PENGGUNAAN.md",
        "README.md",
        "temp_view_dashboard.txt"
    )
    
    foreach ($file in $filesToDelete) {
        $path = Join-Path $projectRoot $file
        if (Test-Path $path) {
            if ($DryRun) {
                Write-Host "  [WOULD DELETE] $file" -ForegroundColor Yellow
            }
            else {
                if (Test-Path $path -PathType Container) {
                    Remove-Item $path -Recurse -Force
                }
                else {
                    Remove-Item $path -Force
                }
                Write-Host "  [DELETED] $file" -ForegroundColor Green
            }
        }
    }
    Write-Host ""
}
else {
    Write-Host "[Step 3] SKIPPED: Dev artifact cleanup" -ForegroundColor Yellow
    Write-Host ""
}

# ============================================================
# Step 4: Final Verification
# ============================================================
Write-Host "[Step 4] Final verification..." -ForegroundColor Cyan

$issues = @()

if (Test-Path ".env") {
    $issues += ".env file still exists"
}

if (Test-Path ".git") {
    $issues += ".git folder still exists"
}

if (Test-Path "tests") {
    $issues += "tests folder still exists"
}

if (-not (Test-Path "bootstrap\cache\config.php")) {
    $issues += "Config cache not found"
}

if ($issues.Count -gt 0) {
    Write-Host "  WARNINGS:" -ForegroundColor Yellow
    foreach ($issue in $issues) {
        Write-Host "    - $issue" -ForegroundColor Yellow
    }
}
else {
    Write-Host "  All checks passed!" -ForegroundColor Green
}

Write-Host ""
Write-Host "========================================================" -ForegroundColor Magenta
Write-Host "  Pre-Build Cleanup Complete!" -ForegroundColor Magenta
Write-Host "========================================================" -ForegroundColor Magenta
Write-Host ""
Write-Host "Next step: Run 'php artisan native:build' to create the .exe" -ForegroundColor Cyan
Write-Host ""
