# PowerShell script to check for BOM in PHP files
# Usage: .\scripts\check-bom.ps1 [file_path]

param(
    [string]$FilePath = ""
)

if ($FilePath -eq "") {
    Write-Host "Usage: .\scripts\check-bom.ps1 <file_path>"
    Write-Host "Example: .\scripts\check-bom.ps1 config\admin-kit.php"
    exit 1
}

if (-not (Test-Path $FilePath)) {
    Write-Host "Error: File not found: $FilePath" -ForegroundColor Red
    exit 1
}

$bytes = [System.IO.File]::ReadAllBytes($FilePath)

if ($bytes.Length -ge 3 -and $bytes[0] -eq 0xEF -and $bytes[1] -eq 0xBB -and $bytes[2] -eq 0xBF) {
    Write-Host "✗ BOM detected in: $FilePath" -ForegroundColor Red
    Write-Host "Run 'composer remove-bom' to fix it." -ForegroundColor Yellow
    exit 1
} else {
    Write-Host "✓ No BOM found in: $FilePath" -ForegroundColor Green
    exit 0
}
