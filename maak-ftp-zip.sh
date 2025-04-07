#!/bin/bash
# Script om alle bestanden in te pakken voor FTP-upload

# Stel kleuren in
GREEN='\033[0;32m'
BLUE='\033[0;34m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # Geen kleur

# Maak logs map als die er nog niet is
mkdir -p logs

# Stel datum in voor backup-naam
DATE=$(date +"%Y-%m-%d_%H-%M-%S")
ZIP_NAME="slimmermetai_ftp_upload_$DATE.zip"

echo -e "${BLUE}========== Voorbereiden van FTP upload pakket ===========${NC}"
echo -e "${YELLOW}Start tijd: $(date)${NC}"
echo

# Maak tijdelijke map
echo -e "${GREEN}Maken van tijdelijke map...${NC}"
TMP_DIR="tmp_ftp_upload"
mkdir -p $TMP_DIR
echo

# Maak mappenstructuur
echo -e "${GREEN}Maken van mappenstructuur...${NC}"
mkdir -p $TMP_DIR/assets/css
mkdir -p $TMP_DIR/assets/js
mkdir -p $TMP_DIR/assets/images
mkdir -p $TMP_DIR/assets/fonts
mkdir -p $TMP_DIR/database
mkdir -p $TMP_DIR/includes
mkdir -p $TMP_DIR/includes/ajax
mkdir -p $TMP_DIR/partials
mkdir -p $TMP_DIR/uploads/profile_pictures
mkdir -p $TMP_DIR/uploads/temp
echo

# Kopieer bestanden
echo -e "${GREEN}Kopiëren van bestanden...${NC}"

# Kopieer PHP-bestanden uit root
echo "Kopiëren PHP-bestanden..."
cp *.php $TMP_DIR/ 2>/dev/null || echo "Geen PHP-bestanden in root gevonden."

# Kopieer HTML-bestanden uit root
echo "Kopiëren HTML-bestanden..."
cp *.html $TMP_DIR/ 2>/dev/null || echo "Geen HTML-bestanden in root gevonden."

# Kopieer CSS-bestanden
echo "Kopiëren CSS-bestanden..."
cp -r css/* $TMP_DIR/assets/css/ 2>/dev/null || echo "Geen CSS-bestanden gevonden."

# Kopieer JavaScript-bestanden
echo "Kopiëren JavaScript-bestanden..."
cp -r js/* $TMP_DIR/assets/js/ 2>/dev/null || echo "Geen JavaScript-bestanden gevonden."

# Kopieer afbeeldingen
echo "Kopiëren afbeeldingen..."
cp -r images/* $TMP_DIR/assets/images/ 2>/dev/null || echo "Geen afbeeldingen gevonden."

# Kopieer fonts
echo "Kopiëren fonts..."
cp -r fonts/* $TMP_DIR/assets/fonts/ 2>/dev/null || echo "Geen fonts gevonden."

# Kopieer database-bestanden
echo "Kopiëren database-bestanden..."
cp -r var/www/html/database/* $TMP_DIR/database/ 2>/dev/null || echo "Geen database-bestanden gevonden."

# Kopieer includes-bestanden
echo "Kopiëren includes-bestanden..."
cp -r var/www/html/includes/* $TMP_DIR/includes/ 2>/dev/null || 
cp -r includes/* $TMP_DIR/includes/ 2>/dev/null || echo "Geen includes-bestanden gevonden."

# Kopieer partials-bestanden
echo "Kopiëren partials-bestanden..."
cp -r var/www/html/partials/* $TMP_DIR/partials/ 2>/dev/null || echo "Geen partials-bestanden gevonden."

# Kopieer .htaccess
echo "Kopiëren .htaccess..."
cp .htaccess $TMP_DIR/ 2>/dev/null || echo "Geen .htaccess gevonden."

# Kopieer README.md
echo "Kopiëren README.md..."
cp README.md $TMP_DIR/ 2>/dev/null || echo "Geen README.md gevonden."

# Kopieer productie-config.php naar de tijdelijke map als config.php
echo "Voorbereiden config.php voor productie..."
cp productie-config.php $TMP_DIR/includes/config.php 2>/dev/null || echo "productie-config.php niet gevonden. Vergeet niet de config.php aan te passen voor productie!"

# Maak lege index.php bestanden in mappen voor beveiliging
echo "Aanmaken beveiligingsbestanden..."
echo '<?php header("Location: /index.php"); exit; ?>' > $TMP_DIR/uploads/index.php
echo '<?php header("Location: /index.php"); exit; ?>' > $TMP_DIR/uploads/profile_pictures/index.php
echo '<?php header("Location: /index.php"); exit; ?>' > $TMP_DIR/uploads/temp/index.php

# Maak .htaccess bestanden voor uploads mappen
echo "# Voorkom directory listing" > $TMP_DIR/uploads/.htaccess
echo "Options -Indexes" >> $TMP_DIR/uploads/.htaccess

echo "# Voorkom directory listing" > $TMP_DIR/uploads/profile_pictures/.htaccess
echo "Options -Indexes" >> $TMP_DIR/uploads/profile_pictures/.htaccess
echo "# Sta alleen afbeeldingen toe" >> $TMP_DIR/uploads/profile_pictures/.htaccess
echo "<FilesMatch \"\.(jpg|jpeg|png|gif)$\">" >> $TMP_DIR/uploads/profile_pictures/.htaccess
echo "    Allow from all" >> $TMP_DIR/uploads/profile_pictures/.htaccess
echo "</FilesMatch>" >> $TMP_DIR/uploads/profile_pictures/.htaccess
echo "<FilesMatch \"^(?!\.(jpg|jpeg|png|gif)$).*$\">" >> $TMP_DIR/uploads/profile_pictures/.htaccess
echo "    Order deny,allow" >> $TMP_DIR/uploads/profile_pictures/.htaccess
echo "    Deny from all" >> $TMP_DIR/uploads/profile_pictures/.htaccess
echo "</FilesMatch>" >> $TMP_DIR/uploads/profile_pictures/.htaccess
echo

# Kopieer FTP-instructies
echo "Kopiëren instructies..."
cp ftp-instructies.md $TMP_DIR/ 2>/dev/null || echo "ftp-instructies.md niet gevonden."
cp ftp-bestandslijst.md $TMP_DIR/ 2>/dev/null || echo "ftp-bestandslijst.md niet gevonden."
echo

# Maak ZIP-bestand
echo -e "${GREEN}Maken van ZIP-bestand...${NC}"
zip -r "$ZIP_NAME" $TMP_DIR > /dev/null
echo

# Opschonen
echo -e "${GREEN}Opschonen van tijdelijke bestanden...${NC}"
rm -rf $TMP_DIR
echo

# Controleer of ZIP-bestand is aangemaakt
if [ -f "$ZIP_NAME" ]; then
    SIZE=$(du -h "$ZIP_NAME" | cut -f1)
    echo -e "${GREEN}De website is succesvol ingepakt in ${BLUE}$ZIP_NAME${GREEN} (Grootte: ${YELLOW}$SIZE${GREEN})${NC}"
    echo -e "${GREEN}Je kunt nu dit bestand uploaden via FTP naar je webserver.${NC}"
    echo -e "${YELLOW}Vergeet niet de ftp-instructies.md te raadplegen voor verdere stappen.${NC}"
else
    echo -e "${RED}Er is een fout opgetreden bij het maken van het ZIP-bestand.${NC}"
fi

echo
echo -e "${BLUE}========== Proces voltooid ===========${NC}"
echo -e "${YELLOW}Eind tijd: $(date)${NC}" 