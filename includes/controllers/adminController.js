/**
 * Controller voor admin functies met SQL database
 */

const LoginAttempt = require('../models/LoginAttempt');
const User = require('../models/User');

/**
 * @desc    Haal login statistieken op
 * @route   GET /api/admin/statistics/login
 * @access  Admin
 */
exports.getLoginStatistics = async (req, res, next) => {
  try {
    const days = parseInt(req.query.days) || 7;
    
    // Beperk tot maximaal 30 dagen om de query niet te zwaar te maken
    const period = Math.min(days, 30);
    
    // Haal statistieken op
    const stats = await LoginAttempt.getStats(period);
    
    // Converteer null naar 0 voor leesbaarheid
    const formattedStats = {
      total: stats.total || 0,
      successful: stats.successful || 0,
      failed: stats.failed || 0,
      unique_emails: stats.unique_emails || 0,
      unique_ips: stats.unique_ips || 0,
      failRate: stats.total ? Math.round((stats.failed / stats.total) * 100) : 0
    };
    
    res.json({
      success: true,
      data: formattedStats,
      period: period
    });
  } catch (error) {
    next(error);
  }
};

/**
 * @desc    Verwijder oude login pogingen
 * @route   DELETE /api/admin/statistics/login
 * @access  Admin
 */
exports.cleanupLoginAttempts = async (req, res, next) => {
  try {
    const days = parseInt(req.query.olderThan) || 30;
    
    // Beperk tot minimaal 7 dagen zodat niet alle recente data per ongeluk wordt verwijderd
    const retentionPeriod = Math.max(days, 7);
    
    // Voer opschoning uit
    const deletedCount = await LoginAttempt.cleanupOldAttempts(retentionPeriod);
    
    res.json({
      success: true,
      message: `${deletedCount} login pogingen verwijderd ouder dan ${retentionPeriod} dagen`,
      deletedCount
    });
  } catch (error) {
    next(error);
  }
};

/**
 * @desc    Haal dashboard gegevens op
 * @route   GET /api/admin/dashboard
 * @access  Admin
 */
exports.getDashboardStats = async (req, res, next) => {
  try {
    // Haal basisgegevens op voor het admin dashboard
    const [userStats, loginStats] = await Promise.all([
      // Gebruikers statistieken
      (async () => {
        const users = await User.findAll();
        const activeUsers = users.filter(user => user.is_active).length;
        const inactiveUsers = users.length - activeUsers;
        
        return {
          total: users.length,
          active: activeUsers,
          inactive: inactiveUsers,
          activationRate: users.length ? Math.round((activeUsers / users.length) * 100) : 0
        };
      })(),
      
      // Login statistieken (laatste 7 dagen)
      LoginAttempt.getStats(7)
    ]);
    
    // Converteer null naar 0 voor leesbaarheid
    const formattedLoginStats = {
      total: loginStats.total || 0,
      successful: loginStats.successful || 0,
      failed: loginStats.failed || 0,
      unique_emails: loginStats.unique_emails || 0,
      unique_ips: loginStats.unique_ips || 0,
      failRate: loginStats.total ? Math.round((loginStats.failed / loginStats.total) * 100) : 0
    };
    
    res.json({
      success: true,
      data: {
        users: userStats,
        loginAttempts: formattedLoginStats
      }
    });
  } catch (error) {
    next(error);
  }
}; 