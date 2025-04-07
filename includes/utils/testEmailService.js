/**
 * Test script voor email functionaliteit
 * Gebruik: node testEmailService.js
 */

require('dotenv').config();
const { 
  sendVerificationEmail, 
  sendPasswordResetEmail, 
  sendWelcomeEmail,
  sendNewUserNotificationEmail
} = require('./emailService');
const db = require('../config/db');
const LoginAttempt = require('../models/LoginAttempt');

// Test parameters
const testEmail = process.env.TEST_EMAIL || 'je_test_email@voorbeeld.nl';
const testName = 'Test Gebruiker';
const testVerificationToken = 'test-verification-token-123456789';
const testResetToken = 'test-reset-token-987654321';
const adminEmail = process.env.ADMIN_EMAIL || 'admin@slimmermetai.com';

/**
 * Test database verbindingen
 */
async function testDatabaseConnections() {
  console.log('🔌 Test database verbindingen');
  console.log('---------------------------');
  
  try {
    // Test users database
    const usersConnected = await db.connectUsersDB();
    if (usersConnected) {
      console.log('✅ Verbinding met users database succesvol!');
    } else {
      console.error('❌ Kon geen verbinding maken met users database.');
    }
    
    // Test sessions database
    const sessionsConnected = await db.connectSessionsDB();
    if (sessionsConnected) {
      console.log('✅ Verbinding met sessions database succesvol!');
    } else {
      console.error('❌ Kon geen verbinding maken met sessions database.');
    }
    
    // Test login attempts database
    const loginAttemptsConnected = await db.connectLoginAttemptsDB();
    if (loginAttemptsConnected) {
      console.log('✅ Verbinding met login attempts database succesvol!');
    } else {
      console.error('❌ Kon geen verbinding maken met login attempts database.');
    }
    
    console.log('---------------------------');
  } catch (error) {
    console.error('❌ Fout bij testen database verbindingen:', error.message);
    console.error('---------------------------');
  }
}

/**
 * Test login attempts functionaliteit
 */
async function testLoginAttempts() {
  console.log('🔒 Test login attempts functionaliteit');
  console.log('---------------------------');
  
  try {
    const testIp = '127.0.0.1';
    const testUserAgent = 'Test Browser/1.0';
    
    // Test het creëren van de login_attempts tabel
    console.log('📝 Maak test login attempt...');
    await LoginAttempt.create(testEmail, testIp, testUserAgent, true);
    console.log('✅ Succesvolle login poging geregistreerd');
    
    // Test mislukte poging
    console.log('📝 Maak mislukte login attempt...');
    await LoginAttempt.create(testEmail, testIp, testUserAgent, false);
    console.log('✅ Mislukte login poging geregistreerd');
    
    // Test statistieken ophalen
    console.log('📊 Ophalen login statistieken...');
    const stats = await LoginAttempt.getStats(1); // Laatste dag
    console.log('✅ Statistieken opgehaald:');
    console.log(`- Totaal pogingen: ${stats.total || 0}`);
    console.log(`- Succesvolle pogingen: ${stats.successful || 0}`);
    console.log(`- Mislukte pogingen: ${stats.failed || 0}`);
    console.log(`- Unieke emails: ${stats.unique_emails || 0}`);
    console.log(`- Unieke IPs: ${stats.unique_ips || 0}`);
    
    console.log('---------------------------');
  } catch (error) {
    console.error('❌ Fout bij testen login attempts:', error.message);
    console.error('---------------------------');
  }
}

/**
 * Test functie voor de email service
 */
async function testEmailService() {
  console.log('🧪 Start email service test');
  console.log('---------------------------');
  console.log('Database configuratie:');
  console.log(`- Users DB: ${process.env.DB_NAME}`);
  console.log(`- Sessions DB: ${process.env.SESSIONS_DB_NAME}`);
  console.log(`- Login Attempts DB: ${process.env.LOGIN_ATTEMPTS_DB_NAME}`);
  console.log('---------------------------');
  console.log(`Email instellingen:`);
  console.log(`- SMTP Host: ${process.env.SMTP_HOST}`);
  console.log(`- SMTP Port: ${process.env.SMTP_PORT}`);
  console.log(`- SMTP User: ${process.env.SMTP_USER}`);
  console.log(`- From: ${process.env.EMAIL_FROM}`);
  console.log(`- Test Email: ${testEmail}`);
  console.log(`- Admin Email: ${adminEmail}`);
  console.log('---------------------------');
  
  try {
    // Test verificatie email
    console.log('📧 Versturen verificatie email...');
    await sendVerificationEmail(testEmail, testVerificationToken, testName);
    console.log('✅ Verificatie email verstuurd!');
    
    // Test welkomst email
    console.log('⏳ Even wachten (2 seconden)...');
    await new Promise(resolve => setTimeout(resolve, 2000));
    
    console.log('📧 Versturen welkomst email...');
    await sendWelcomeEmail(testEmail, testName);
    console.log('✅ Welkomst email verstuurd!');
    
    // Test wachtwoord reset email
    console.log('⏳ Even wachten (2 seconden)...');
    await new Promise(resolve => setTimeout(resolve, 2000));
    
    console.log('📧 Versturen wachtwoord reset email...');
    await sendPasswordResetEmail(testEmail, testResetToken, testName);
    console.log('✅ Wachtwoord reset email verstuurd!');
    
    // Test admin notificatie email
    console.log('⏳ Even wachten (2 seconden)...');
    await new Promise(resolve => setTimeout(resolve, 2000));
    
    console.log('📧 Versturen admin notificatie email...');
    await sendNewUserNotificationEmail(adminEmail, {
      id: 123,
      name: testName,
      email: testEmail
    });
    console.log('✅ Admin notificatie email verstuurd!');
    
    console.log('---------------------------');
    console.log('✨ Alle tests succesvol voltooid!');
    console.log('Controleer je inbox op de test emails.');
    console.log('---------------------------');
  } catch (error) {
    console.error('❌ Test gefaald:');
    console.error(error);
    console.error('---------------------------');
    console.log('🔍 Controleer je .env bestand voor correcte SMTP instellingen:');
    console.log('- SMTP_HOST: De hostname van je SMTP-server (bijv. mail.antagonist.nl)');
    console.log('- SMTP_PORT: De poort van je SMTP-server (meestal 587 of 465)');
    console.log('- SMTP_USER: Je email gebruikersnaam');
    console.log('- SMTP_PASSWORD: Je email wachtwoord');
    console.log('- EMAIL_FROM: Het afzenderadres (bijv. "Slimmer met AI <admin@slimmermetai.com>")');
    console.log('- TEST_EMAIL: Het email adres om tests naar te versturen');
    console.log('- ADMIN_EMAIL: Het admin email adres voor notificaties');
    console.log('---------------------------');
  }
}

// Voer de tests uit
async function runTests() {
  await testDatabaseConnections();
  await testLoginAttempts();
  await testEmailService();
}

runTests(); 