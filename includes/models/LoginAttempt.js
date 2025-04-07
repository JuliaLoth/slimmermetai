/**
 * LoginAttempt Model voor SQL database
 * Gebruikt een aparte database voor het bijhouden van login pogingen
 */

const db = require('../config/db');

/**
 * LoginAttempt class voor het bijhouden van login pogingen
 */
class LoginAttempt {
  /**
   * Registreer een nieuwe login poging
   * @param {string} email - Gebruikte e-mailadres
   * @param {string} ipAddress - IP adres
   * @param {string} userAgent - User agent string
   * @param {boolean} success - Of de poging succesvol was
   * @returns {Promise<number>} - ID van de nieuwe login poging
   */
  static async create(email, ipAddress, userAgent, success) {
    // Creëer de login_attempts tabel als deze nog niet bestaat
    try {
      await db.queryLoginAttempts(`
        CREATE TABLE IF NOT EXISTS login_attempts (
          id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          email VARCHAR(255) NOT NULL,
          ip_address VARCHAR(45) NOT NULL,
          user_agent TEXT NOT NULL,
          success TINYINT(1) NOT NULL DEFAULT 0,
          timestamp DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          INDEX (email),
          INDEX (ip_address),
          INDEX (timestamp)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
      `);
    } catch (error) {
      console.error('Fout bij het aanmaken van de login_attempts tabel:', error.message);
      // Doorgaan met de functie ondanks een fout, het kan zijn dat de tabel al bestaat
    }

    // Voeg login poging toe aan database
    const sql = `
      INSERT INTO login_attempts (
        email, ip_address, user_agent, success, timestamp
      ) VALUES (?, ?, ?, ?, NOW())
    `;

    const params = [
      email.toLowerCase(), ipAddress, userAgent, success ? 1 : 0
    ];

    return db.insertLoginAttemptId(sql, params);
  }

  /**
   * Tel het aantal mislukte pogingen voor een email binnen een tijdsperiode
   * @param {string} email - E-mailadres
   * @param {number} minutesAgo - Aantal minuten terug om te controleren
   * @returns {Promise<number>} - Aantal mislukte pogingen
   */
  static async countFailedAttempts(email, minutesAgo = 15) {
    const sql = `
      SELECT COUNT(*) AS count
      FROM login_attempts
      WHERE email = ?
        AND success = 0
        AND timestamp > DATE_SUB(NOW(), INTERVAL ? MINUTE)
    `;

    const result = await db.getLoginAttemptRow(sql, [email.toLowerCase(), minutesAgo]);
    return result ? result.count : 0;
  }

  /**
   * Tel het aantal mislukte pogingen voor een IP adres binnen een tijdsperiode
   * @param {string} ipAddress - IP adres
   * @param {number} minutesAgo - Aantal minuten terug om te controleren
   * @returns {Promise<number>} - Aantal mislukte pogingen
   */
  static async countFailedAttemptsIP(ipAddress, minutesAgo = 15) {
    const sql = `
      SELECT COUNT(*) AS count
      FROM login_attempts
      WHERE ip_address = ?
        AND success = 0
        AND timestamp > DATE_SUB(NOW(), INTERVAL ? MINUTE)
    `;

    const result = await db.getLoginAttemptRow(sql, [ipAddress, minutesAgo]);
    return result ? result.count : 0;
  }

  /**
   * Controleer of een email te veel mislukte pogingen heeft
   * @param {string} email - E-mailadres
   * @param {number} maxAttempts - Maximum aantal pogingen
   * @param {number} lockoutTime - Lockout tijd in minuten
   * @returns {Promise<boolean>} - Of de email geblokkeerd moet worden
   */
  static async isBlocked(email, maxAttempts = 5, lockoutTime = 15) {
    const count = await this.countFailedAttempts(email, lockoutTime);
    return count >= maxAttempts;
  }

  /**
   * Controleer of een IP te veel mislukte pogingen heeft
   * @param {string} ipAddress - IP adres
   * @param {number} maxAttempts - Maximum aantal pogingen
   * @param {number} lockoutTime - Lockout tijd in minuten
   * @returns {Promise<boolean>} - Of het IP geblokkeerd moet worden
   */
  static async isIPBlocked(ipAddress, maxAttempts = 10, lockoutTime = 15) {
    const count = await this.countFailedAttemptsIP(ipAddress, lockoutTime);
    return count >= maxAttempts;
  }

  /**
   * Laatste login poging ophalen voor een email
   * @param {string} email - E-mailadres
   * @returns {Promise<Object|null>} - Laatste login poging of null
   */
  static async getLastAttempt(email) {
    const sql = `
      SELECT * FROM login_attempts
      WHERE email = ?
      ORDER BY timestamp DESC
      LIMIT 1
    `;

    return db.getLoginAttemptRow(sql, [email.toLowerCase()]);
  }

  /**
   * Verwijder oude login pogingen (ouder dan X dagen)
   * @param {number} days - Aantal dagen om bij te houden
   * @returns {Promise<number>} - Aantal verwijderde records
   */
  static async cleanupOldAttempts(days = 30) {
    const sql = `
      DELETE FROM login_attempts
      WHERE timestamp < DATE_SUB(NOW(), INTERVAL ? DAY)
    `;

    const result = await db.queryLoginAttempts(sql, [days]);
    return result.affectedRows;
  }

  /**
   * Haal statistieken op over login pogingen
   * @param {number} days - Aantal dagen terug om te analyseren
   * @returns {Promise<Object>} - Login statistieken
   */
  static async getStats(days = 7) {
    const sql = `
      SELECT 
        COUNT(*) AS total,
        SUM(CASE WHEN success = 1 THEN 1 ELSE 0 END) AS successful,
        SUM(CASE WHEN success = 0 THEN 1 ELSE 0 END) AS failed,
        COUNT(DISTINCT email) AS unique_emails,
        COUNT(DISTINCT ip_address) AS unique_ips
      FROM login_attempts
      WHERE timestamp > DATE_SUB(NOW(), INTERVAL ? DAY)
    `;

    return db.getLoginAttemptRow(sql, [days]);
  }
}

module.exports = LoginAttempt; 