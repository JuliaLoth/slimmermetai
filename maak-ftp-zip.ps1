# PowerShell script om alle bestanden in te pakken voor FTP-upload

# Functie om berichten te kleuren
function Write-ColorOutput($ForegroundColor) {
    $fc = $host.UI.RawUI.ForegroundColor
    $host.UI.RawUI.ForegroundColor = $ForegroundColor
    if ($args) {
        Write-Output $args
    }
    else {
        $input | Write-Output
    }
    $host.UI.RawUI.ForegroundColor = $fc
}

# Maak logs map als die er nog niet is
if (!(Test-Path "logs")) {
    New-Item -ItemType Directory -Force -Path "logs"
}

# Stel datum in voor backup-naam
$date = Get-Date -Format "yyyy-MM-dd_HH-mm-ss"
$zipName = "slimmermetai_ftp_upload_$date.zip"

Write-ColorOutput Green "========== Voorbereiden van FTP upload pakket =========="
Write-Output "Start tijd: $(Get-Date)"
Write-Output ""

# Maak tijdelijke map
Write-ColorOutput Cyan "Maken van tijdelijke map..."
$tmpDir = "tmp_ftp_upload"
if (Test-Path $tmpDir) {
    Remove-Item -Path $tmpDir -Recurse -Force
}
New-Item -ItemType Directory -Force -Path $tmpDir
Write-Output ""

# Maak mappenstructuur
Write-ColorOutput Cyan "Maken van mappenstructuur..."
New-Item -ItemType Directory -Force -Path "$tmpDir\assets\css"
New-Item -ItemType Directory -Force -Path "$tmpDir\assets\js"
New-Item -ItemType Directory -Force -Path "$tmpDir\assets\images"
New-Item -ItemType Directory -Force -Path "$tmpDir\assets\fonts"
New-Item -ItemType Directory -Force -Path "$tmpDir\database"
New-Item -ItemType Directory -Force -Path "$tmpDir\includes"
New-Item -ItemType Directory -Force -Path "$tmpDir\includes\ajax"
New-Item -ItemType Directory -Force -Path "$tmpDir\partials"
New-Item -ItemType Directory -Force -Path "$tmpDir\uploads\profile_pictures"
New-Item -ItemType Directory -Force -Path "$tmpDir\uploads\temp"
Write-Output ""

# Kopieer bestanden
Write-ColorOutput Cyan "Kopiëren van bestanden..."

# Kopieer PHP-bestanden uit root
Write-Output "Kopiëren PHP-bestanden..."
if (Test-Path "*.php") {
    Copy-Item -Path "*.php" -Destination $tmpDir -ErrorAction SilentlyContinue
} else {
    Write-Output "Geen PHP-bestanden in root gevonden."
}

# Kopieer HTML-bestanden uit root
Write-Output "Kopiëren HTML-bestanden..."
if (Test-Path "*.html") {
    Copy-Item -Path "*.html" -Destination $tmpDir -ErrorAction SilentlyContinue
} else {
    Write-Output "Geen HTML-bestanden in root gevonden."
}

# Kopieer CSS-bestanden
Write-Output "Kopiëren CSS-bestanden..."
if (Test-Path "css") {
    Copy-Item -Path "css\*" -Destination "$tmpDir\assets\css" -Recurse -ErrorAction SilentlyContinue
} else {
    Write-Output "Geen CSS-bestanden gevonden."
}

# Kopieer JavaScript-bestanden
Write-Output "Kopiëren JavaScript-bestanden..."
if (Test-Path "js") {
    Copy-Item -Path "js\*" -Destination "$tmpDir\assets\js" -Recurse -ErrorAction SilentlyContinue
} else {
    Write-Output "Geen JavaScript-bestanden gevonden."
}

# Kopieer afbeeldingen
Write-Output "Kopiëren afbeeldingen..."
if (Test-Path "images") {
    Copy-Item -Path "images\*" -Destination "$tmpDir\assets\images" -Recurse -ErrorAction SilentlyContinue
} else {
    Write-Output "Geen afbeeldingen gevonden."
}

# Kopieer fonts
Write-Output "Kopiëren fonts..."
if (Test-Path "fonts") {
    Copy-Item -Path "fonts\*" -Destination "$tmpDir\assets\fonts" -Recurse -ErrorAction SilentlyContinue
} else {
    Write-Output "Geen fonts gevonden."
}

# Kopieer database-bestanden
Write-Output "Kopiëren database-bestanden..."
if (Test-Path "var\www\html\database") {
    Copy-Item -Path "var\www\html\database\*" -Destination "$tmpDir\database" -Recurse -ErrorAction SilentlyContinue
} else {
    Write-Output "Geen database-bestanden gevonden."
}

# Kopieer includes-bestanden
Write-Output "Kopiëren includes-bestanden..."
if (Test-Path "var\www\html\includes") {
    Copy-Item -Path "var\www\html\includes\*" -Destination "$tmpDir\includes" -Recurse -ErrorAction SilentlyContinue
} elseif (Test-Path "includes") {
    Copy-Item -Path "includes\*" -Destination "$tmpDir\includes" -Recurse -ErrorAction SilentlyContinue
} else {
    Write-Output "Geen includes-bestanden gevonden."
}

# Kopieer partials-bestanden
Write-Output "Kopiëren partials-bestanden..."
if (Test-Path "var\www\html\partials") {
    Copy-Item -Path "var\www\html\partials\*" -Destination "$tmpDir\partials" -Recurse -ErrorAction SilentlyContinue
} else {
    Write-Output "Geen partials-bestanden gevonden."
}

# Kopieer .htaccess
Write-Output "Kopiëren .htaccess..."
if (Test-Path ".htaccess") {
    Copy-Item -Path ".htaccess" -Destination $tmpDir -ErrorAction SilentlyContinue
} else {
    Write-Output "Geen .htaccess gevonden."
}

# Kopieer README.md
Write-Output "Kopiëren README.md..."
if (Test-Path "README.md") {
    Copy-Item -Path "README.md" -Destination $tmpDir -ErrorAction SilentlyContinue
} else {
    Write-Output "Geen README.md gevonden."
}

# Kopieer productie-config.php naar de tijdelijke map als config.php
Write-Output "Voorbereiden config.php voor productie..."
if (Test-Path "productie-config.php") {
    Copy-Item -Path "productie-config.php" -Destination "$tmpDir\includes\config.php" -ErrorAction SilentlyContinue
} else {
    Write-Output "productie-config.php niet gevonden. Vergeet niet de config.php aan te passen voor productie!"
}

# Maak lege index.php bestanden in mappen voor beveiliging
Write-Output "Aanmaken beveiligingsbestanden..."
"<?php header('Location: /index.php'); exit; ?>" | Out-File -FilePath "$tmpDir\uploads\index.php" -Encoding utf8
"<?php header('Location: /index.php'); exit; ?>" | Out-File -FilePath "$tmpDir\uploads\profile_pictures\index.php" -Encoding utf8
"<?php header('Location: /index.php'); exit; ?>" | Out-File -FilePath "$tmpDir\uploads\temp\index.php" -Encoding utf8

# Maak .htaccess bestanden voor uploads mappen
@"
# Voorkom directory listing
Options -Indexes
"@ | Out-File -FilePath "$tmpDir\uploads\.htaccess" -Encoding utf8

@"
# Voorkom directory listing
Options -Indexes
# Sta alleen afbeeldingen toe
<FilesMatch "\.(jpg|jpeg|png|gif)$">
    Allow from all
</FilesMatch>
<FilesMatch "^(?!\.(jpg|jpeg|png|gif)$).*$">
    Order deny,allow
    Deny from all
</FilesMatch>
"@ | Out-File -FilePath "$tmpDir\uploads\profile_pictures\.htaccess" -Encoding utf8

# Kopieer FTP-instructies
Write-Output "Kopiëren instructies..."
if (Test-Path "ftp-instructies.md") {
    Copy-Item -Path "ftp-instructies.md" -Destination $tmpDir -ErrorAction SilentlyContinue
} else {
    Write-Output "ftp-instructies.md niet gevonden."
}

if (Test-Path "ftp-bestandslijst.md") {
    Copy-Item -Path "ftp-bestandslijst.md" -Destination $tmpDir -ErrorAction SilentlyContinue
} else {
    Write-Output "ftp-bestandslijst.md niet gevonden."
}

Write-Output ""

# Maak ZIP-bestand
Write-ColorOutput Cyan "Maken van ZIP-bestand..."
if (Get-Command Compress-Archive -ErrorAction SilentlyContinue) {
    # Gebruikt PowerShell's eigen Compress-Archive
    Compress-Archive -Path "$tmpDir\*" -DestinationPath $zipName -Force
} else {
    # Fallback naar .NET methode
    [Reflection.Assembly]::LoadWithPartialName("System.IO.Compression.FileSystem") | Out-Null
    [System.IO.Compression.ZipFile]::CreateFromDirectory($tmpDir, $zipName, [System.IO.Compression.CompressionLevel]::Optimal, $false)
}
Write-Output ""

# Opschonen
Write-ColorOutput Cyan "Opschonen van tijdelijke bestanden..."
Remove-Item -Path $tmpDir -Recurse -Force
Write-Output ""

# Controleer of ZIP-bestand is aangemaakt
if (Test-Path $zipName) {
    $fileInfo = Get-Item $zipName
    $size = [Math]::Round($fileInfo.Length / 1MB, 2)
    Write-ColorOutput Green "De website is succesvol ingepakt in $zipName (Grootte: $size MB)"
    Write-ColorOutput Green "Je kunt nu dit bestand uploaden via FTP naar je webserver."
    Write-ColorOutput Yellow "Vergeet niet de ftp-instructies.md te raadplegen voor verdere stappen."
} else {
    Write-ColorOutput Red "Er is een fout opgetreden bij het maken van het ZIP-bestand."
}

Write-Output ""
Write-ColorOutput Green "========== Proces voltooid =========="
Write-Output "Eind tijd: $(Get-Date)" 