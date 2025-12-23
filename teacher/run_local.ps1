Param(
    [string]$InputFile,
    [string]$OutputFile
)

# PowerShell helper to run local processor and create venv if missing
$here = Split-Path -Path $MyInvocation.MyCommand.Definition -Parent
Set-Location $here

if (-not (Test-Path venv)) {
    Write-Output "Creating virtual environment..."
    python -m venv venv
}

# Activate venv
. .\venv\Scripts\Activate.ps1

Write-Output "Installing requirements (if needed)..."
pip install --upgrade pip
pip install -r requirements-local.txt

if (-not $InputFile) {
    Write-Output "Usage: .\run_local.ps1 -InputFile <input.xlsx> [-OutputFile <out.xlsx>]"
    exit 1
}

if (-not $OutputFile) {
    $p = Split-Path $InputFile -Leaf
    $stem = [System.IO.Path]::GetFileNameWithoutExtension($p)
    $ext = [System.IO.Path]::GetExtension($p)
    $OutputFile = "$stem`_processed$ext"
}

python .\process_excel_local.py $InputFile $OutputFile
