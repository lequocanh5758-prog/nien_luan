#!/usr/bin/env pwsh

# OpenCode PowerShell wrapper
# This script provides a simple opencode command that works with the VS Code extension

param(
    [string]$Port = "43377",
    [switch]$Help
)

if ($Help) {
    Write-Host "OpenCode PowerShell Wrapper"
    Write-Host "Usage: .\opencode.ps1 [-Port <port>] [-Help]"
    Write-Host ""
    Write-Host "This is a simple wrapper that simulates the opencode command"
    Write-Host "for the VS Code OpenCode extension."
    exit 0
}

# Set environment variable for VS Code caller
$env:OPENCODE_CALLER = "vscode"

Write-Host "OpenCode PowerShell Wrapper" -ForegroundColor Green
Write-Host "Port: $Port" -ForegroundColor Cyan
Write-Host "Environment: OPENCODE_CALLER=vscode" -ForegroundColor Cyan
Write-Host ""
Write-Host "This wrapper simulates the opencode command for VS Code extension compatibility." -ForegroundColor Yellow
Write-Host "The actual OpenCode functionality should be handled by the VS Code extension." -ForegroundColor Yellow
Write-Host ""
Write-Host "If you need the real OpenCode CLI, please install it manually:" -ForegroundColor White
Write-Host "1. Download from: https://github.com/sst/opencode/releases" -ForegroundColor White
Write-Host "2. Or use: curl -fsSL https://opencode.ai/install | bash" -ForegroundColor White
Write-Host ""

# Keep the process running to simulate a server
Write-Host "OpenCode wrapper is running on port $Port..." -ForegroundColor Green
Write-Host "Press Ctrl+C to stop." -ForegroundColor Gray

try {
    # Simulate a running server
    while ($true) {
        Start-Sleep -Seconds 1
    }
}
catch {
    Write-Host "OpenCode wrapper stopped." -ForegroundColor Red
}
