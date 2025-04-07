/**
 * Test script voor database verbindingen
 * Gebruik: node testDBConnections.js
 */

require('dotenv').config();
const db = require('../config/db');

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
    console.log('Database configuratie:');
    console.log(`- Users DB: ${process.env.DB_NAME}`);
    console.log(`- Sessions DB: ${process.env.SESSIONS_DB_NAME}`);
    console.log(`- Login Attempts DB: ${process.env.LOGIN_ATTEMPTS_DB_NAME}`);
    console.log('---------------------------');
  } catch (error) {
    console.error('❌ Fout bij testen database verbindingen:', error.message);
    console.error('---------------------------');
  }
}

// Voer de test uit
testDatabaseConnections(); 