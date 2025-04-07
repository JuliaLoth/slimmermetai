/**
 * User Model voor SQL database
 * Aangepast voor gebruik met aparte users database
 */

const bcrypt = require('bcryptjs');
const crypto = require('crypto');
const db = require('../config/db');

/**
 * User class voor het werken met gebruikersgegevens
 */
class User {
  /**
   * Maak een nieuwe gebruiker aan
   * @param {Object} userData - De gebruikersgegevens
   * @returns {Promise<Number>} - ID van de nieuwe gebruiker
   */
  static async create(userData) {
    // Hash het wachtwoord
    const salt = await bcrypt.genSalt(10);
    const hashedPassword = await bcrypt.hash(userData.password, salt);
    
    // Maak verificatie token
    const verificationToken = crypto.randomBytes(32).toString('hex');
    const hashedToken = crypto
      .createHash('sha256')
      .update(verificationToken)
      .digest('hex');
    
    // Bereid data voor voor invoegen
    const user = {
      name: userData.name || null,
      email: userData.email.toLowerCase(),
      password: hashedPassword,
      profile_picture: userData.profilePicture || null,
      bio: userData.bio || null,
      activation_code: hashedToken,
      is_active: 0, // Standaard niet actief
      created_at: new Date(),
      updated_at: new Date()
    };
    
    // Creëer de users tabel als deze nog niet bestaat
    try {
      await db.queryUsers(`
        CREATE TABLE IF NOT EXISTS users (
          id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          name VARCHAR(255) NOT NULL,
          email VARCHAR(255) NOT NULL UNIQUE,
          password VARCHAR(255) NOT NULL,
          profile_picture VARCHAR(255) DEFAULT NULL,
          bio TEXT DEFAULT NULL,
          activation_code VARCHAR(64) DEFAULT NULL,
          reset_token VARCHAR(64) DEFAULT NULL,
          reset_token_expires_at DATETIME DEFAULT NULL,
          is_active TINYINT(1) NOT NULL DEFAULT 0,
          last_login DATETIME DEFAULT NULL,
          login_attempts INT UNSIGNED DEFAULT 0,
          locked_until DATETIME DEFAULT NULL,
          created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          updated_at DATETIME DEFAULT NULL,
          INDEX (email),
          INDEX (activation_code),
          INDEX (reset_token)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
      `);
    } catch (error) {
      console.error('Fout bij het aanmaken van de users tabel:', error.message);
      // Doorgaan met de functie ondanks een fout, het kan zijn dat de tabel al bestaat
    }
    
    // Voeg gebruiker toe aan database
    const sql = `
      INSERT INTO users (
        name, email, password, profile_picture, bio, activation_code, 
        is_active, created_at, updated_at
      ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    `;
    
    const params = [
      user.name, user.email, user.password, user.profile_picture, user.bio,
      user.activation_code, user.is_active, user.created_at, user.updated_at
    ];
    
    const userId = await db.insertUserId(sql, params);
    
    // Return zowel het ID als de verificatie token
    return {
      id: userId,
      verificationToken
    };
  }
  
  /**
   * Vind een gebruiker op basis van e-mail
   * @param {string} email - E-mailadres van de gebruiker
   * @param {boolean} includePassword - Of het wachtwoord moet worden meegenomen
   * @returns {Promise<Object|null>} - Gebruikersgegevens of null
   */
  static async findByEmail(email, includePassword = false) {
    const fields = includePassword 
      ? '*' 
      : 'id, name, email, profile_picture, bio, is_active, last_login, created_at, updated_at';
    
    const sql = `SELECT ${fields} FROM users WHERE email = ? LIMIT 1`;
    return db.getUserRow(sql, [email.toLowerCase()]);
  }
  
  /**
   * Vind een gebruiker op basis van ID
   * @param {number} id - Gebruikers-ID
   * @returns {Promise<Object|null>} - Gebruikersgegevens of null
   */
  static async findById(id) {
    const sql = `
      SELECT id, name, email, profile_picture, bio, is_active, 
      last_login, created_at, updated_at
      FROM users WHERE id = ? LIMIT 1
    `;
    return db.getUserRow(sql, [id]);
  }
  
  /**
   * Vind gebruiker via verificatie token
   * @param {string} token - Verificatie token
   * @returns {Promise<Object|null>} - Gebruikersgegevens of null
   */
  static async findByVerificationToken(token) {
    const hashedToken = crypto
      .createHash('sha256')
      .update(token)
      .digest('hex');
    
    const sql = 'SELECT * FROM users WHERE activation_code = ? LIMIT 1';
    return db.getUserRow(sql, [hashedToken]);
  }
  
  /**
   * Vind gebruiker via reset token
   * @param {string} token - Reset token
   * @returns {Promise<Object|null>} - Gebruikersgegevens of null
   */
  static async findByResetToken(token) {
    const hashedToken = crypto
      .createHash('sha256')
      .update(token)
      .digest('hex');
    
    const sql = `
      SELECT * FROM users 
      WHERE reset_token = ? AND reset_token_expires_at > NOW() 
      LIMIT 1
    `;
    return db.getUserRow(sql, [hashedToken]);
  }
  
  /**
   * Vergelijk ingevoerd wachtwoord met opgeslagen hash
   * @param {string} enteredPassword - Ingevoerd wachtwoord
   * @param {string} hashedPassword - Opgeslagen hash
   * @returns {Promise<boolean>} - Of wachtwoord overeenkomt
   */
  static async matchPassword(enteredPassword, hashedPassword) {
    return await bcrypt.compare(enteredPassword, hashedPassword);
  }
  
  /**
   * Activeer een gebruiker via token
   * @param {string} token - Verificatie token
   * @returns {Promise<boolean>} - Of activatie succesvol was
   */
  static async verifyEmail(token) {
    const hashedToken = crypto
      .createHash('sha256')
      .update(token)
      .digest('hex');
    
    const updateSql = `
      UPDATE users 
      SET is_active = 1, activation_code = NULL, updated_at = NOW() 
      WHERE activation_code = ? AND is_active = 0
    `;
    
    const result = await db.queryUsers(updateSql, [hashedToken]);
    return result.affectedRows > 0;
  }
  
  /**
   * Genereer een nieuwe verificatie token voor een bestaande gebruiker
   * @param {number} userId - Gebruikers-ID
   * @returns {Promise<Object>} - Object met gebruikers-ID en verificationToken
   */
  static async generateVerificationToken(userId) {
    // Controleer of gebruiker bestaat en nog niet geactiveerd is
    const user = await this.findById(userId);
    
    if (!user) {
      throw new Error('Gebruiker niet gevonden');
    }
    
    if (user.is_active) {
      throw new Error('Gebruiker is al geactiveerd');
    }
    
    // Genereer nieuw token
    const verificationToken = crypto.randomBytes(32).toString('hex');
    const hashedToken = crypto
      .createHash('sha256')
      .update(verificationToken)
      .digest('hex');
    
    // Update de gebruiker
    const sql = `
      UPDATE users 
      SET activation_code = ?, updated_at = NOW() 
      WHERE id = ? AND is_active = 0
    `;
    
    const result = await db.queryUsers(sql, [hashedToken, userId]);
    
    if (result.affectedRows === 0) {
      throw new Error('Kon verificatie token niet bijwerken');
    }
    
    return {
      id: userId,
      verificationToken
    };
  }
  
  /**
   * Genereer een wachtwoord reset token
   * @param {number} userId - Gebruikers-ID
   * @returns {Promise<string>} - Gegenereerde token
   */
  static async generateResetToken(userId) {
    const resetToken = crypto.randomBytes(32).toString('hex');
    
    const hashedToken = crypto
      .createHash('sha256')
      .update(resetToken)
      .digest('hex');
    
    // Token verloopt na 1 uur
    const expires = new Date();
    expires.setHours(expires.getHours() + 1);
    
    const sql = `
      UPDATE users 
      SET reset_token = ?, reset_token_expires_at = ?, updated_at = NOW() 
      WHERE id = ?
    `;
    
    await db.queryUsers(sql, [hashedToken, expires, userId]);
    return resetToken;
  }
  
  /**
   * Reset wachtwoord met token
   * @param {string} token - Reset token
   * @param {string} newPassword - Nieuw wachtwoord
   * @returns {Promise<boolean>} - Of reset succesvol was
   */
  static async resetPassword(token, newPassword) {
    const hashedToken = crypto
      .createHash('sha256')
      .update(token)
      .digest('hex');
    
    // Hash het nieuwe wachtwoord
    const salt = await bcrypt.genSalt(10);
    const hashedPassword = await bcrypt.hash(newPassword, salt);
    
    const sql = `
      UPDATE users 
      SET password = ?, reset_token = NULL, reset_token_expires_at = NULL, updated_at = NOW() 
      WHERE reset_token = ? AND reset_token_expires_at > NOW()
    `;
    
    const result = await db.queryUsers(sql, [hashedPassword, hashedToken]);
    return result.affectedRows > 0;
  }
  
  /**
   * Update gebruiker gegevens
   * @param {number} userId - Gebruikers-ID
   * @param {Object} updateData - Bij te werken velden
   * @returns {Promise<boolean>} - Of update succesvol was
   */
  static async update(userId, updateData) {
    // Velden die bijgewerkt mogen worden
    const allowedFields = [
      'name', 'profile_picture', 'bio'
    ];
    
    // Bouw de SQL query dynamisch op
    const updateFields = [];
    const values = [];
    
    for (const field of allowedFields) {
      if (updateData[field] !== undefined) {
        updateFields.push(`${field} = ?`);
        values.push(updateData[field]);
      }
    }
    
    // Als er geen velden zijn om bij te werken
    if (updateFields.length === 0) {
      return false;
    }
    
    // Voeg updated_at timestamp toe
    updateFields.push('updated_at = NOW()');
    
    // Voeg user ID toe aan parameters
    values.push(userId);
    
    const sql = `
      UPDATE users 
      SET ${updateFields.join(', ')} 
      WHERE id = ?
    `;
    
    const result = await db.queryUsers(sql, values);
    return result.affectedRows > 0;
  }
  
  /**
   * Wijzig wachtwoord
   * @param {number} userId - Gebruikers-ID
   * @param {string} newPassword - Nieuw wachtwoord
   * @returns {Promise<boolean>} - Of wijziging succesvol was
   */
  static async changePassword(userId, newPassword) {
    // Hash het nieuwe wachtwoord
    const salt = await bcrypt.genSalt(10);
    const hashedPassword = await bcrypt.hash(newPassword, salt);
    
    const sql = `
      UPDATE users 
      SET password = ?, updated_at = NOW() 
      WHERE id = ?
    `;
    
    const result = await db.queryUsers(sql, [hashedPassword, userId]);
    return result.affectedRows > 0;
  }
  
  /**
   * Verwijder een gebruiker
   * @param {number} userId - Gebruikers-ID
   * @returns {Promise<boolean>} - Of verwijdering succesvol was
   */
  static async delete(userId) {
    const sql = 'DELETE FROM users WHERE id = ?';
    const result = await db.queryUsers(sql, [userId]);
    return result.affectedRows > 0;
  }
  
  /**
   * Log login poging en update lastLogin
   * @param {number} userId - Gebruikers-ID
   * @returns {Promise<void>}
   */
  static async updateLoginTime(userId) {
    const sql = 'UPDATE users SET last_login = NOW() WHERE id = ?';
    await db.queryUsers(sql, [userId]);
  }
  
  /**
   * Haal alle gebruikers op (voor admin)
   * @returns {Promise<Array>} - Lijst van gebruikers
   */
  static async findAll() {
    const sql = `
      SELECT id, name, email, profile_picture, is_active, 
      last_login, created_at, updated_at
      FROM users 
      ORDER BY created_at DESC
    `;
    return db.queryUsers(sql);
  }
}

module.exports = User; 