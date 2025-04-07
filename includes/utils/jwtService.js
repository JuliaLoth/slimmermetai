const jwt = require('jsonwebtoken');
const crypto = require('crypto');
const RefreshToken = require('../models/RefreshToken');
require('dotenv').config();

/**
 * Genereer JWT token
 * @param {Object} payload - Data om in token op te slaan
 * @returns {String} JWT token
 */
const generateToken = (payload) => {
  return jwt.sign(
    payload,
    process.env.JWT_SECRET,
    { expiresIn: process.env.JWT_EXPIRES_IN }
  );
};

/**
 * Genereer refresh token
 * @param {Object} user - Gebruiker waarvoor token wordt gegenereerd
 * @param {String} ipAddress - IP adres van request
 * @returns {Promise<Object>} Refresh token object
 */
const generateRefreshToken = async (user, ipAddress) => {
  // Creëer refresh token die 7 dagen geldig is
  const refreshToken = new RefreshToken({
    user: user.id,
    token: crypto.randomBytes(40).toString('hex'),
    expires: new Date(Date.now() + parseInt(process.env.JWT_REFRESH_EXPIRES_IN) * 1000),
    createdByIp: ipAddress
  });

  // Sla token op
  await refreshToken.save();

  return refreshToken;
};

/**
 * Vernieuw access token met refresh token
 * @param {String} token - Refresh token
 * @param {String} ipAddress - IP adres van request
 * @returns {Promise<Object>} Nieuwe tokens en gebruikersinfo
 */
const refreshAccessToken = async (token, ipAddress) => {
  const refreshToken = await getRefreshToken(token);
  const { user } = refreshToken;

  // Vervang oude refresh token met nieuwe (token rotation)
  const newRefreshToken = await generateRefreshToken(user, ipAddress);
  
  // Revoke oude token
  refreshToken.revoked = Date.now();
  refreshToken.revokedByIp = ipAddress;
  refreshToken.replacedByToken = newRefreshToken.token;
  await refreshToken.save();

  // Genereer nieuwe JWT
  const jwtToken = generateToken({ 
    id: user.id,
    email: user.email,
    role: user.role
  });

  // Return nieuwe token en user
  return {
    token: jwtToken,
    refreshToken: newRefreshToken.token,
    user: {
      id: user.id,
      email: user.email,
      firstName: user.firstName,
      lastName: user.lastName,
      role: user.role
    }
  };
};

/**
 * Haal refresh token op en valideer het
 * @param {String} token - Refresh token string
 * @returns {Promise<Object>} RefreshToken document
 */
const getRefreshToken = async (token) => {
  const refreshToken = await RefreshToken.findOne({ token }).populate('user');
  
  // Valideer token
  if (!refreshToken || !refreshToken.isActive) {
    throw new Error('Ongeldige refresh token');
  }
  
  return refreshToken;
};

/**
 * Herroep een refresh token
 * @param {String} token - Refresh token om te herroepen
 * @param {String} ipAddress - IP adres van request
 * @returns {Promise<void>}
 */
const revokeToken = async (token, ipAddress) => {
  const refreshToken = await getRefreshToken(token);
  
  // Set token to revoked
  refreshToken.revoked = Date.now();
  refreshToken.revokedByIp = ipAddress;
  
  await refreshToken.save();
};

/**
 * Herroep alle refresh tokens van een gebruiker
 * @param {String} userId - Gebruiker ID
 * @param {String} ipAddress - IP adres van request
 * @returns {Promise<void>}
 */
const revokeAllUserTokens = async (userId, ipAddress) => {
  // Zoek alle actieve tokens voor gebruiker
  const refreshTokens = await RefreshToken.find({ 
    user: userId,
    revoked: { $exists: false }
  });
  
  // Herroep alle gevonden tokens
  for (const token of refreshTokens) {
    token.revoked = Date.now();
    token.revokedByIp = ipAddress;
    await token.save();
  }
};

module.exports = {
  generateToken,
  generateRefreshToken,
  refreshAccessToken,
  getRefreshToken,
  revokeToken,
  revokeAllUserTokens
}; 