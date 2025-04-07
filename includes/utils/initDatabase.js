/**
 * Database initialisatie script
 * Dit script maakt de benodigde tabellen aan in de database
 */

require('dotenv').config();
const fs = require('fs');
const path = require('path');
const mysql = require('mysql2/promise');

// Database connectie configuratie
const dbConfig = {
  host: process.env.DB_HOST || 'localhost',
  user: process.env.DB_USER || 'root',
  password: process.env.DB_PASSWORD || '',
  multipleStatements: true  // Nodig voor het uitvoeren van meerdere SQL statements
};

async function initDatabase() {
  let connection;
  
  try {
    console.log('Verbinden met de database server...');
    connection = await mysql.createConnection(dbConfig);
    
    // Maak de database aan als deze nog niet bestaat
    const dbName = process.env.DB_NAME || 'slimmermetai';
    console.log(`Database "${dbName}" aanmaken als deze niet bestaat...`);
    await connection.query(`CREATE DATABASE IF NOT EXISTS ${dbName} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;`);
    
    // Selecteer de database
    console.log(`Database "${dbName}" selecteren...`);
    await connection.query(`USE ${dbName};`);
    
    // Lees het SQL-bestand
    const schemaFilePath = path.join(__dirname, '../database/sql/users_schema.sql');
    console.log(`SQL bestand lezen: ${schemaFilePath}`);
    const sqlContent = fs.readFileSync(schemaFilePath, 'utf8');
    
    // Voer de SQL uit
    console.log('Tabellen aanmaken...');
    await connection.query(sqlContent);
    
    console.log('Database initialisatie voltooid!');
    
    // Controleer of er een admin user moet worden aangemaakt
    const [admins] = await connection.query('SELECT COUNT(*) as count FROM users WHERE email = ?', [process.env.ADMIN_EMAIL || 'admin@slimmermetai.com']);
    
    if (admins[0].count === 0 && process.env.ADMIN_PASSWORD) {
      const bcrypt = require('bcryptjs');
      const salt = await bcrypt.genSalt(10);
      const hashedPassword = await bcrypt.hash(process.env.ADMIN_PASSWORD, salt);
      
      console.log('Admin gebruiker aanmaken...');
      await connection.query(
        'INSERT INTO users (name, email, password, is_active, created_at, updated_at) VALUES (?, ?, ?, 1, NOW(), NOW())',
        ['Admin', process.env.ADMIN_EMAIL || 'admin@slimmermetai.com', hashedPassword]
      );
      console.log('Admin gebruiker aangemaakt!');
    }
    
  } catch (error) {
    console.error('Fout bij initialiseren database:', error.message);
    process.exit(1);
  } finally {
    if (connection) {
      await connection.end();
      console.log('Database verbinding gesloten.');
    }
  }
}

// Voer het script uit
initDatabase(); 