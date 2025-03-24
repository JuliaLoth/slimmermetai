@echo off
rem Eenvoudig batch script om bestanden voor te bereiden voor FTP
rem Dit is een alternatief voor het PowerShell script

echo ========== Voorbereiden van FTP upload pakket ==========
echo Start tijd: %date% %time%
echo.

rem Maak een logs map als deze niet bestaat
if not exist logs mkdir logs

rem Maak een tijdelijke map voor het verzamelen van bestanden
echo Maken van tijdelijke map...
set TMP_DIR=tmp_ftp_upload
if exist %TMP_DIR% rmdir /s /q %TMP_DIR%
mkdir %TMP_DIR%
echo.

rem Maak de mappenstructuur
echo Maken van mappenstructuur...
mkdir %TMP_DIR%\assets\css
mkdir %TMP_DIR%\assets\js
mkdir %TMP_DIR%\assets\images
mkdir %TMP_DIR%\assets\fonts
mkdir %TMP_DIR%\database
mkdir %TMP_DIR%\includes
mkdir %TMP_DIR%\includes\ajax
mkdir %TMP_DIR%\partials
mkdir %TMP_DIR%\uploads\profile_pictures
mkdir %TMP_DIR%\uploads\temp
echo.

rem Kopieer bestanden
echo Kopiëren van bestanden...

rem Kopieer PHP-bestanden uit root
echo Kopiëren PHP-bestanden...
copy *.php %TMP_DIR%\ > nul 2>&1
if %errorlevel% neq 0 echo Geen PHP-bestanden in root gevonden.

rem Kopieer HTML-bestanden uit root
echo Kopiëren HTML-bestanden...
copy *.html %TMP_DIR%\ > nul 2>&1
if %errorlevel% neq 0 echo Geen HTML-bestanden in root gevonden.

rem Kopieer CSS-bestanden
echo Kopiëren CSS-bestanden...
if exist css xcopy /s /y css\* %TMP_DIR%\assets\css\ > nul 2>&1
if %errorlevel% neq 0 echo Geen CSS-bestanden gevonden.

rem Kopieer JavaScript-bestanden
echo Kopiëren JavaScript-bestanden...
if exist js xcopy /s /y js\* %TMP_DIR%\assets\js\ > nul 2>&1
if %errorlevel% neq 0 echo Geen JavaScript-bestanden gevonden.

rem Kopieer afbeeldingen
echo Kopiëren afbeeldingen...
if exist images xcopy /s /y images\* %TMP_DIR%\assets\images\ > nul 2>&1
if %errorlevel% neq 0 echo Geen afbeeldingen gevonden.

rem Kopieer fonts
echo Kopiëren fonts...
if exist fonts xcopy /s /y fonts\* %TMP_DIR%\assets\fonts\ > nul 2>&1
if %errorlevel% neq 0 echo Geen fonts gevonden.

rem Kopieer database-bestanden
echo Kopiëren database-bestanden...
if exist var\www\html\database xcopy /s /y var\www\html\database\* %TMP_DIR%\database\ > nul 2>&1
if %errorlevel% neq 0 echo Geen database-bestanden gevonden.

rem Kopieer includes-bestanden
echo Kopiëren includes-bestanden...
if exist var\www\html\includes xcopy /s /y var\www\html\includes\* %TMP_DIR%\includes\ > nul 2>&1
if %errorlevel% neq 0 (
  if exist includes xcopy /s /y includes\* %TMP_DIR%\includes\ > nul 2>&1
  if %errorlevel% neq 0 echo Geen includes-bestanden gevonden.
)

rem Kopieer partials-bestanden
echo Kopiëren partials-bestanden...
if exist var\www\html\partials xcopy /s /y var\www\html\partials\* %TMP_DIR%\partials\ > nul 2>&1
if %errorlevel% neq 0 echo Geen partials-bestanden gevonden.

rem Kopieer .htaccess
echo Kopiëren .htaccess...
if exist .htaccess copy .htaccess %TMP_DIR%\ > nul 2>&1
if %errorlevel% neq 0 echo Geen .htaccess gevonden.

rem Kopieer README.md
echo Kopiëren README.md...
if exist README.md copy README.md %TMP_DIR%\ > nul 2>&1
if %errorlevel% neq 0 echo Geen README.md gevonden.

rem Kopieer productie-config.php naar de tijdelijke map als config.php
echo Voorbereiden config.php voor productie...
if exist productie-config.php copy productie-config.php %TMP_DIR%\includes\config.php > nul 2>&1
if %errorlevel% neq 0 echo productie-config.php niet gevonden. Vergeet niet de config.php aan te passen voor productie!

rem Maak lege index.php bestanden in mappen voor beveiliging
echo Aanmaken beveiligingsbestanden...
echo ^<?php header('Location: /index.php'); exit; ?^> > %TMP_DIR%\uploads\index.php
echo ^<?php header('Location: /index.php'); exit; ?^> > %TMP_DIR%\uploads\profile_pictures\index.php
echo ^<?php header('Location: /index.php'); exit; ?^> > %TMP_DIR%\uploads\temp\index.php

rem Maak .htaccess bestanden voor uploads mappen
echo # Voorkom directory listing > %TMP_DIR%\uploads\.htaccess
echo Options -Indexes >> %TMP_DIR%\uploads\.htaccess

echo # Voorkom directory listing > %TMP_DIR%\uploads\profile_pictures\.htaccess
echo Options -Indexes >> %TMP_DIR%\uploads\profile_pictures\.htaccess
echo # Sta alleen afbeeldingen toe >> %TMP_DIR%\uploads\profile_pictures\.htaccess
echo ^<FilesMatch "\.(jpg^|jpeg^|png^|gif)$"^> >> %TMP_DIR%\uploads\profile_pictures\.htaccess
echo     Allow from all >> %TMP_DIR%\uploads\profile_pictures\.htaccess
echo ^</FilesMatch^> >> %TMP_DIR%\uploads\profile_pictures\.htaccess
echo ^<FilesMatch "^(?!\.(jpg^|jpeg^|png^|gif)$).*$"^> >> %TMP_DIR%\uploads\profile_pictures\.htaccess
echo     Order deny,allow >> %TMP_DIR%\uploads\profile_pictures\.htaccess
echo     Deny from all >> %TMP_DIR%\uploads\profile_pictures\.htaccess
echo ^</FilesMatch^> >> %TMP_DIR%\uploads\profile_pictures\.htaccess

rem Kopieer FTP-instructies
echo Kopiëren instructies...
if exist ftp-instructies.md copy ftp-instructies.md %TMP_DIR%\ > nul 2>&1
if %errorlevel% neq 0 echo ftp-instructies.md niet gevonden.
if exist ftp-bestandslijst.md copy ftp-bestandslijst.md %TMP_DIR%\ > nul 2>&1
if %errorlevel% neq 0 echo ftp-bestandslijst.md niet gevonden.
echo.

rem Maak een datum-tijd string voor de bestandsnaam
set datetime=%date:~-4,4%-%date:~-7,2%-%date:~-10,2%_%time:~0,2%-%time:~3,2%-%time:~6,2%
set datetime=%datetime: =0%
set ZIP_NAME=slimmermetai_ftp_upload_%datetime%.zip

rem Gebruik ingebouwde zip functionaliteit of waarschuw de gebruiker
echo Voorbereiden van ZIP-bestand...
if exist %TMP_DIR% (
  echo Om een ZIP-bestand te maken:
  echo 1. Open de map '%TMP_DIR%'
  echo 2. Selecteer alle bestanden en mappen
  echo 3. Klik met de rechtermuisknop en kies "Verzenden naar" ^> "Gecomprimeerde (gezipte) map"
  echo 4. Hernoem het bestand naar "slimmermetai_ftp_upload.zip"
  
  echo.
  echo OPMERKING: De bestanden zijn klaar in de map '%TMP_DIR%'
  echo Het kopieëren is voltooid, maar het ZIP-bestand moet handmatig gemaakt worden.
  echo Verwijder deze map niet totdat je het ZIP-bestand hebt gemaakt!
) else (
  echo Fout bij het maken van de tijdelijke map.
)

echo.
echo ========== Proces voltooid ==========
echo Eind tijd: %date% %time%

echo.
echo Druk op een toets om dit venster te sluiten...
pause > nul 