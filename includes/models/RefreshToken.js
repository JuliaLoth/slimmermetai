/**
 * RefreshToken Model voor SQL database
 * Aangepast voor gebruik met aparte sessions database
 */

const crypto = require('crypto');
const db = require('../config/db');

/**
 * RefreshToken class voor het werken met refresh tokens
 */
class RefreshToken {
  /**
   * Maak een nieuw refresh token aan
   * @param {number} userId - Gebruikers-ID
   * @param {string} userAgent - User agent string
   * @param {string} ipAddress - IP adres
   * @returns {Promise<string>} - Gegenereerde token
   */
  static async create(userId, userAgent, ipAddress) {
    // Genereer een unieke token
    const token = crypto.randomBytes(40).toString('hex');
    
    // Bereken verlooptijd (30 dagen vanaf nu)
    const expiresAt = new Date();
    expiresAt.setDate(expiresAt.getDate() + 30);
    
    // Creëer de user_sessions tabel als deze nog niet bestaat
    try {
      await db.querySessions(`
        CREATE TABLE IF NOT EXISTS user_sessions (
          id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          user_id INT UNSIGNED NOT NULL,
          session_id VARCHAR(128) NOT NULL UNIQUE,
          ip_address VARCHAR(45) NOT NULL,
          user_agent TEXT NOT NULL,
          created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          expires_at DATETIME NOT NULL,
          INDEX (user_id),
          INDEX (session_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
      `);
    } catch (error) {
      console.error('Fout bij het aanmaken van de user_sessions tabel:', error.message);
      // Doorgaan met de functie ondanks een fout, het kan zijn dat de tabel al bestaat
    }
    
    // Sla token op in database
    const sql = `
      INSERT INTO user_sessions (
        user_id, session_id, ip_address, user_agent, 
        created_at, expires_at
      ) VALUES (?, ?, ?, ?, NOW(), ?)
    `;
    
    try {
      await db.querySessions(sql, [
        userId, token, ipAddress, userAgent, expiresAt
      ]);
    } catch (error) {
      console.error('Fout bij opslaan refresh token:', error.message);
      
      // Als er een foreign key error is, dan bestaat de gebruiker waarschijnlijk niet
      if (error.message.includes('foreign key constraint fails')) {
        throw new Error('Gebruiker bestaat niet, kan geen refresh token aanmaken');
      }
      
      throw error;
    }
    
    return token;
  }
  
  /**
   * Vind een token in de database
   * @param {string} token - Token string
   * @returns {Promise<Object|null>} - Token informatie of null
   */
  static async findOne(token) {
    const sql = `
      SELECT * FROM user_sessions 
      WHERE session_id = ? AND expires_at > NOW() 
      LIMIT 1
    `;
    
    return db.getSessionRow(sql, [token]);
  }
  
  /**
   * Verwijder een specifiek token
   * @param {string} token - Token string
   * @returns {Promise<boolean>} - Of verwijderen succesvol was
   */
  static async delete(token) {
    const sql = 'DELETE FROM user_sessions WHERE session_id = ?';
    const result = await db.querySessions(sql, [token]);
    return result.affectedRows > 0;
  }
  
  /**
   * Verwijder alle tokens van een gebruiker
   * @param {number} userId - Gebruikers-ID
   * @returns {Promise<boolean>} - Of verwijderen succesvol was
   */
  static async deleteAll(userId) {
    const sql = 'DELETE FROM user_sessions WHERE user_id = ?';
    const result = await db.querySessions(sql, [userId]);
    return result.affectedRows > 0;
  }
  
  /**
   * Verwijder verlopen tokens
   * @returns {Promise<number>} - Aantal verwijderde tokens
   */
  static async deleteExpired() {
    const sql = 'DELETE FROM user_sessions WHERE expires_at < NOW()';
    const result = await db.querySessions(sql);
    return result.affectedRows;
  }
}

module.exports = RefreshToken; 