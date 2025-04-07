/**
 * Eenvoudig test script zonder externe modules
 * Gebruik: node simpleTest.js
 */

const fs = require('fs');
const path = require('path');

// Functie om .env bestand te lezen
function parseEnvFile(filePath) {
  try {
    const envContent = fs.readFileSync(filePath, 'utf8');
    const envVars = {};
    
    // Parse elke lijn in het .env bestand
    envContent.split('\n').forEach(line => {
      // Negeer commentaarregels en lege regels
      if (!line || line.startsWith('#')) return;
      
      // Split de lijn op het = teken
      const [key, value] = line.split('=');
      if (key && value) {
        // Verwijder aanhalingstekens als die er zijn
        envVars[key.trim()] = value.trim().replace(/^["'](.*)["']$/, '$1');
      }
    });
    
    return envVars;
  } catch (err) {
    console.error(`Fout bij lezen .env bestand: ${err.message}`);
    return {};
  }
}

// Hoofd test functie
function runTest() {
  console.log('🧪 Start eenvoudige test');
  console.log('---------------------------');
  
  // Pad naar .env bestand
  const envPath = path.join(__dirname, '..', '.env');
  
  // Lees .env bestand
  console.log(`Lezen van .env bestand op pad: ${envPath}`);
  const envVars = parseEnvFile(envPath);
  
  // Toon database configuratie
  console.log('---------------------------');
  console.log('Database configuratie:');
  console.log(`- DB Host: ${envVars.DB_HOST || 'Niet gevonden'}`);
  console.log(`- DB User: ${envVars.DB_USER || 'Niet gevonden'}`);
  console.log(`- DB Name: ${envVars.DB_NAME || 'Niet gevonden'}`);
  console.log(`- Sessions DB: ${envVars.SESSIONS_DB_NAME || 'Niet gevonden'}`);
  console.log(`- Login Attempts DB: ${envVars.LOGIN_ATTEMPTS_DB_NAME || 'Niet gevonden'}`);
  console.log('---------------------------');
  
  // Toon SMTP configuratie
  console.log('Email configuratie:');
  console.log(`- SMTP Host: ${envVars.SMTP_HOST || 'Niet gevonden'}`);
  console.log(`- SMTP Port: ${envVars.SMTP_PORT || 'Niet gevonden'}`);
  console.log(`- SMTP User: ${envVars.SMTP_USER || 'Niet gevonden'}`);
  console.log(`- Email From: ${envVars.EMAIL_FROM || 'Niet gevonden'}`);
  console.log('---------------------------');
  
  // Toon andere configuratie
  console.log('Overige configuratie:');
  console.log(`- Frontend URL: ${envVars.FRONTEND_URL || 'Niet gevonden'}`);
  console.log(`- JWT Secret: ${envVars.JWT_SECRET ? '✓ Ingesteld' : '✗ Niet ingesteld'}`);
  console.log(`- JWT Expires In: ${envVars.JWT_EXPIRES_IN || 'Niet gevonden'}`);
  console.log('---------------------------');
  
  console.log('✅ Test voltooid');
}

// Voer de test uit
runTest(); 