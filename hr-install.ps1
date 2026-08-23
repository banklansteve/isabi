<#
    One-shot installer for the iSabi People / HR module (Windows PowerShell).

    Run from the ROOT of your local isabi project, with the Cursor preview
    ("Laravel :43123") open so the download URL is reachable.

        Invoke-WebRequest http://localhost:43123/hr-install.ps1 -OutFile hr-install.ps1
        powershell -ExecutionPolicy Bypass -File .\hr-install.ps1

    Optional args:
        -BaseUrl  http://localhost:43123   (change if your preview uses a different URL)
        -Demo                              (also load demo data)
#>
param(
    [string]$BaseUrl = "http://localhost:43123",
    [switch]$Demo
)

function Say($msg)  { Write-Host "`n==> $msg" -ForegroundColor Cyan }
function Fail($msg) { Write-Host "`nError: $msg" -ForegroundColor Red; exit 1 }
function Check($what) { if ($LASTEXITCODE -ne 0) { Fail "$what failed (exit $LASTEXITCODE)." } }

# --- sanity checks -----------------------------------------------------------
if (-not (Test-Path "artisan")) { Fail "No 'artisan' here. cd into your isabi project root, then re-run." }
if (-not (Test-Path ".git"))    { Fail "This folder is not a git repository." }

git diff --quiet
$dirty = ($LASTEXITCODE -ne 0)
git diff --cached --quiet
if ($LASTEXITCODE -ne 0) { $dirty = $true }
if ($dirty) {
    Say "You have uncommitted changes. Committing them first is recommended so you can undo cleanly."
    $reply = Read-Host "Continue anyway? [y/N]"
    if ($reply -notmatch '^[yY]') { Fail "Aborted. Commit or stash your changes, then re-run." }
}

# --- download the patch ------------------------------------------------------
Say "Downloading hr-module.patch from $BaseUrl"
try {
    Invoke-WebRequest -Uri "$BaseUrl/hr-module.patch" -OutFile "hr-module.patch" -UseBasicParsing
} catch {
    Fail "Could not download the patch. Make sure the Cursor 'Laravel :43123' preview is open, or pass -BaseUrl with the correct URL."
}
Say "Patch downloaded."

# --- apply the patch ---------------------------------------------------------
Say "Applying the HR module patch"
git apply --3way --whitespace=fix hr-module.patch
if ($LASTEXITCODE -ne 0) {
    Say "Some hunks did not match your local files. Applying everything possible and leaving .rej files for the few spots to merge by hand."
    git apply --reject --whitespace=fix hr-module.patch
    $rej = Get-ChildItem -Recurse -Filter *.rej -ErrorAction SilentlyContinue |
        Where-Object { $_.FullName -notmatch '\\vendor\\' -and $_.FullName -notmatch '\\node_modules\\' }
    if ($rej) {
        Say "Conflicts left in these .rej files - resolve them after this script finishes:"
        $rej | ForEach-Object { Write-Host "    $($_.FullName)" }
    }
}

# --- PHP dependencies --------------------------------------------------------
Say "Installing barryvdh/laravel-dompdf (payslip PDFs)"
composer require barryvdh/laravel-dompdf
Check "composer require"

Say "Refreshing autoloader and caches"
composer dump-autoload
Check "composer dump-autoload"
php artisan optimize:clear
Check "artisan optimize:clear"

# --- database ----------------------------------------------------------------
Say "Running migrations"
php artisan migrate --force
Check "artisan migrate"

Say "Seeding leave types + checklist templates"
php artisan db:seed --class=HrSeeder --force
Check "artisan db:seed HrSeeder"

if ($Demo) {
    Say "Seeding demo HR data"
    php artisan db:seed --class=HrDemoSeeder --force
    Check "artisan db:seed HrDemoSeeder"
}

# --- frontend ----------------------------------------------------------------
Say "Installing npm packages"
npm install
Check "npm install"
Say "Building assets"
npm run build
Check "npm run build"

# --- done --------------------------------------------------------------------
Remove-Item -Force "hr-module.patch" -ErrorAction SilentlyContinue
Say "Done. Log in as super@isabi.dev and open /admin/hr (Admin home -> People)."
Say "Review with 'git status' / 'git diff', then commit and push to your repo as usual."
