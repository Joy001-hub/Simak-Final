# PHP/Blade/JS Comment Stripper and Minifier for Source Code Protection
# This script removes comments and minifies PHP files to protect source code

param(
    [string]$Path = ".",
    [switch]$DryRun = $false,
    [switch]$Minify = $false
)

$ErrorActionPreference = "Stop"

Write-Host ""
Write-Host "========================================================" -ForegroundColor Magenta
Write-Host "  Source Code Protection - Comment Stripper" -ForegroundColor Magenta
Write-Host "========================================================" -ForegroundColor Magenta
Write-Host ""

if ($DryRun) {
    Write-Host "[DRY RUN MODE] No files will be modified" -ForegroundColor Yellow
    Write-Host ""
}

# Folders to process
$foldersToProcess = @(
    "app",
    "config", 
    "routes",
    "database\migrations",
    "database\seeders"
)

# File extensions to process
$extensions = @("*.php")

# Files/folders to skip
$skipPatterns = @(
    "*\vendor\*",
    "*\node_modules\*",
    "*\storage\*",
    "*\.git\*"
)

$totalFiles = 0
$processedFiles = 0
$skippedFiles = 0

function Remove-PhpComments {
    param([string]$content)
    
    $result = $content
    
    # Remove multi-line comments /* ... */ (non-greedy)
    $result = [regex]::Replace($result, '/\*[\s\S]*?\*/', '')
    
    # Remove single line comments // but NOT URLs (http://, https://)
    # Match // only if not preceded by : (to preserve URLs)
    $result = [regex]::Replace($result, '(?<!:)//[^\r\n]*', '')
    
    # Remove # comments (but not in strings or hex colors)
    $result = [regex]::Replace($result, '(?m)^\s*#[^\r\n]*$', '')
    
    # Remove empty lines (multiple consecutive newlines -> single newline)
    $result = [regex]::Replace($result, '(\r?\n){3,}', "`n`n")
    
    # Trim leading/trailing whitespace from each line
    $lines = $result -split "`n"
    $lines = $lines | ForEach-Object { $_.TrimEnd() }
    $result = $lines -join "`n"
    
    return $result
}

function Minify-PhpContent {
    param([string]$content)
    
    # First remove comments
    $result = Remove-PhpComments -content $content
    
    # Remove extra whitespace but preserve string contents
    # This is a simplified minification - does not handle all edge cases
    
    # Collapse multiple spaces to single space
    $result = [regex]::Replace($result, ' {2,}', ' ')
    
    # Remove spaces around operators (careful with strings)
    $result = [regex]::Replace($result, '\s*([=\+\-\*\/\.\,\;\:\?\>\<\!\&\|])\s*', '$1')
    
    # Remove newlines (convert to single line) - optional, can break some code
    # $result = [regex]::Replace($result, '[\r\n]+', ' ')
    
    return $result
}

Write-Host "Scanning folders..." -ForegroundColor Cyan

foreach ($folder in $foldersToProcess) {
    $fullPath = Join-Path $Path $folder
    
    if (-not (Test-Path $fullPath)) {
        Write-Host "  [SKIP] Folder not found: $folder" -ForegroundColor Gray
        continue
    }
    
    Write-Host "  Processing: $folder" -ForegroundColor Gray
    
    foreach ($ext in $extensions) {
        $files = Get-ChildItem -Path $fullPath -Filter $ext -Recurse -File -ErrorAction SilentlyContinue
        
        foreach ($file in $files) {
            $skip = $false
            foreach ($pattern in $skipPatterns) {
                if ($file.FullName -like $pattern) {
                    $skip = $true
                    break
                }
            }
            
            if ($skip) {
                $skippedFiles++
                continue
            }
            
            $totalFiles++
            
            try {
                $originalContent = Get-Content $file.FullName -Raw -Encoding UTF8
                
                if ($Minify) {
                    $newContent = Minify-PhpContent -content $originalContent
                } else {
                    $newContent = Remove-PhpComments -content $originalContent
                }
                
                # Only write if content changed
                if ($originalContent -ne $newContent) {
                    if ($DryRun) {
                        $relativePath = $file.FullName.Replace($Path, "").TrimStart("\")
                        Write-Host "  [WOULD MODIFY] $relativePath" -ForegroundColor Yellow
                    } else {
                        # Write without BOM
                        $utf8NoBom = New-Object System.Text.UTF8Encoding $false
                        [System.IO.File]::WriteAllText($file.FullName, $newContent, $utf8NoBom)
                    }
                    $processedFiles++
                }
            }
            catch {
                Write-Host "  [ERROR] $($file.Name): $($_.Exception.Message)" -ForegroundColor Red
            }
        }
    }
}

Write-Host ""
Write-Host "========================================================" -ForegroundColor Magenta
Write-Host "  Summary" -ForegroundColor Magenta
Write-Host "========================================================" -ForegroundColor Magenta
Write-Host "  Total files scanned: $totalFiles" -ForegroundColor Gray
Write-Host "  Files processed: $processedFiles" -ForegroundColor Green
Write-Host "  Files skipped: $skippedFiles" -ForegroundColor Gray
Write-Host ""
