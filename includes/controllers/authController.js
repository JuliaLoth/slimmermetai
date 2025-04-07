/**
 * Controller voor authenticatie met SQL database
 */

const User = require('../models/User');
const RefreshToken = require('../models/RefreshToken');
const LoginAttempt = require('../models/LoginAttempt');
const { 
  sendVerificationEmail, 
  sendPasswordResetEmail, 
  sendWelcomeEmail,
  sendNewUserNotificationEmail
} = require('../utils/emailService');
const jwt = require('jsonwebtoken');
const axios = require('axios');

// Helper functie voor reCAPTCHA verificatie
async function verifyRecaptcha(recaptchaToken) {
  try {
    const response = await axios.post(
      'https://www.google.com/recaptcha/api/siteverify',
      null,
      {
        params: {
          secret: process.env.RECAPTCHA_SECRET_KEY,
          response: recaptchaToken
        }
      }
    );
    
    return response.data.success;
  } catch (error) {
    console.error('reCAPTCHA verificatie fout:', error);
    return false;
  }
}

/**
 * @desc    Gebruiker registreren
 * @route   POST /api/auth/register
 * @access  Openbaar
 */
exports.register = async (req, res, next) => {
  try {
    const { name, email, password, recaptchaToken } = req.body;

    // Verifieer reCAPTCHA
    if (!recaptchaToken) {
      return res.status(400).json({
        success: false,
        message: 'reCAPTCHA verificatie ontbreekt'
      });
    }

    const recaptchaValid = await verifyRecaptcha(recaptchaToken);
    if (!recaptchaValid) {
      return res.status(400).json({
        success: false,
        message: 'reCAPTCHA verificatie mislukt'
      });
    }

    // Controleer of gebruiker bestaat
    const userExists = await User.findByEmail(email);
    if (userExists) {
      return res.status(400).json({ 
        success: false, 
        message: 'E-mailadres is al in gebruik' 
      });
    }

    // Valideer wachtwoord
    if (password.length < 8) {
      return res.status(400).json({
        success: false,
        message: 'Wachtwoord moet minimaal 8 tekens bevatten'
      });
    }

    // Maak nieuwe gebruiker aan
    const result = await User.create({
      name,
      email,
      password,
      profilePicture: req.body.profilePicture || null,
      bio: req.body.bio || null
    });

    // Stuur verificatie e-mail
    await sendVerificationEmail(
      email,
      result.verificationToken,
      name
    );

    // Stuur notificatie naar admin als ADMIN_EMAIL is ingesteld
    try {
      if (process.env.ADMIN_EMAIL) {
        await sendNewUserNotificationEmail(
          process.env.ADMIN_EMAIL,
          { name, email, id: result.id }
        );
      }
    } catch (notificationError) {
      // Loggen maar geen error gooien, registratie moet doorgaan zelfs als admin notificatie mislukt
      console.error('Fout bij versturen admin notificatie:', notificationError.message);
    }

    res.status(201).json({
      success: true,
      message: 'Registratie succesvol, check je e-mail voor verificatie'
    });
  } catch (error) {
    next(error);
  }
};

/**
 * @desc    E-mail adres verifiëren
 * @route   POST /api/auth/verify-email
 * @access  Openbaar
 */
exports.verifyEmail = async (req, res, next) => {
  try {
    const { token } = req.body;

    if (!token) {
      return res.status(400).json({
        success: false,
        message: 'Verificatie token ontbreekt'
      });
    }

    // Zoek de gebruiker op basis van de token voordat we de verificatie uitvoeren
    const user = await User.findByVerificationToken(token);
    
    if (!user) {
      return res.status(400).json({
        success: false,
        message: 'Ongeldige token of token is verlopen'
      });
    }

    // Verifieer e-mailadres
    const success = await User.verifyEmail(token);

    if (!success) {
      return res.status(400).json({
        success: false,
        message: 'Activering mislukt, probeer het later opnieuw'
      });
    }

    // Stuur welkomst e-mail
    try {
      await sendWelcomeEmail(user.email, user.name);
    } catch (welcomeEmailError) {
      // Loggen maar geen error gooien, verificatie is al geslaagd
      console.error('Fout bij versturen welkomst e-mail:', welcomeEmailError.message);
    }

    res.json({
      success: true,
      message: 'E-mail verificatie succesvol, je kunt nu inloggen'
    });
  } catch (error) {
    next(error);
  }
};

/**
 * @desc    Gebruiker inloggen
 * @route   POST /api/auth/login
 * @access  Openbaar
 */
exports.login = async (req, res, next) => {
  try {
    const { email, password, recaptchaToken } = req.body;
    const ipAddress = req.ip || req.connection.remoteAddress;
    const userAgent = req.headers['user-agent'];
    
    // Controleer of IP of email geblokkeerd is vanwege te veel mislukte pogingen
    // Haal de maximale pogingen en lockout tijd op uit de environment variables
    const maxAttempts = parseInt(process.env.LOGIN_MAX_ATTEMPTS) || 5;
    const lockoutTime = parseInt(process.env.LOGIN_LOCKOUT_TIME) || 15; // minuten

    const isIPBlocked = await LoginAttempt.isIPBlocked(ipAddress, maxAttempts * 2, lockoutTime);
    const isEmailBlocked = await LoginAttempt.isBlocked(email, maxAttempts, lockoutTime);
    
    // Als IP of email geblokkeerd is, vereisen we reCAPTCHA
    if (isIPBlocked || isEmailBlocked) {
      // Verifieer reCAPTCHA
      if (!recaptchaToken) {
        await LoginAttempt.create(email, ipAddress, userAgent, false);
        return res.status(429).json({
          success: false,
          message: 'Te veel mislukte pogingen. Vul de reCAPTCHA in.',
          requireRecaptcha: true
        });
      }

      const recaptchaValid = await verifyRecaptcha(recaptchaToken);
      if (!recaptchaValid) {
        await LoginAttempt.create(email, ipAddress, userAgent, false);
        return res.status(400).json({
          success: false,
          message: 'reCAPTCHA verificatie mislukt',
          requireRecaptcha: true
        });
      }
    }
    
    if (isIPBlocked) {
      // Logt poging en geeft error
      await LoginAttempt.create(email, ipAddress, userAgent, false);
      return res.status(429).json({
        success: false,
        message: `Te veel mislukte inlogpogingen. Probeer het over ${lockoutTime} minuten opnieuw.`
      });
    }

    if (isEmailBlocked) {
      // Logt poging en geeft error
      await LoginAttempt.create(email, ipAddress, userAgent, false);
      return res.status(429).json({
        success: false,
        message: `Te veel mislukte inlogpogingen. Probeer het over ${lockoutTime} minuten opnieuw.`
      });
    }

    // Zoek gebruiker met wachtwoord
    const user = await User.findByEmail(email, true);
    
    // Controleer of gebruiker bestaat
    if (!user) {
      // Log mislukte poging
      await LoginAttempt.create(email, ipAddress, userAgent, false);
      return res.status(401).json({ 
        success: false, 
        message: 'Ongeldige inloggegevens' 
      });
    }

    // Controleer wachtwoord
    const isMatch = await User.matchPassword(password, user.password);
    if (!isMatch) {
      // Log mislukte poging
      await LoginAttempt.create(email, ipAddress, userAgent, false);
      return res.status(401).json({ 
        success: false, 
        message: 'Ongeldige inloggegevens' 
      });
    }

    // Controleer of account actief is
    if (!user.is_active) {
      // Log mislukte poging
      await LoginAttempt.create(email, ipAddress, userAgent, false);
      return res.status(401).json({
        success: false,
        message: 'E-mail nog niet geverifieerd, check je inbox'
      });
    }

    // Log succesvolle poging
    await LoginAttempt.create(email, ipAddress, userAgent, true);

    // Update laatste login
    await User.updateLoginTime(user.id);
    
    // Genereer JWT token
    const token = generateToken(user);

    // Genereer refresh token
    const refreshToken = await RefreshToken.create(user.id, userAgent, ipAddress);

    // Zet cookie voor refresh token
    setRefreshTokenCookie(res, refreshToken);

    // Verwijder wachtwoord uit response
    delete user.password;

    res.json({
      success: true,
      token,
      user: {
        id: user.id,
        name: user.name,
        email: user.email,
        profilePicture: user.profile_picture,
        bio: user.bio
      }
    });
  } catch (error) {
    next(error);
  }
};

/**
 * @desc    Vernieuw toegangstoken
 * @route   POST /api/auth/refresh-token
 * @access  Openbaar
 */
exports.refreshToken = async (req, res, next) => {
  try {
    const token = req.cookies.refreshToken;
    const ipAddress = req.ip || req.connection.remoteAddress;
    const userAgent = req.headers['user-agent'];

    if (!token) {
      return res.status(401).json({ 
        success: false, 
        message: 'Refresh token ontbreekt' 
      });
    }

    // Vind token in database
    const refreshTokenData = await RefreshToken.findOne(token);

    if (!refreshTokenData) {
      return res.status(401).json({ 
        success: false, 
        message: 'Ongeldige refresh token' 
      });
    }

    // Haal gebruiker op
    const user = await User.findById(refreshTokenData.user_id);

    if (!user) {
      return res.status(401).json({ 
        success: false, 
        message: 'Gebruiker niet gevonden' 
      });
    }

    // Genereer nieuwe tokens
    const newToken = generateToken(user);
    const newRefreshToken = await RefreshToken.create(user.id, userAgent, ipAddress);

    // Verwijder oude token
    await RefreshToken.delete(token);

    // Zet cookie voor nieuwe refresh token
    setRefreshTokenCookie(res, newRefreshToken);

    res.json({
      success: true,
      token: newToken,
      user
    });
  } catch (error) {
    // Als refresh token expired/invalid, verwijder cookie
    res.clearCookie('refreshToken');
    
    next(error);
  }
};

/**
 * @desc    Uitloggen (revoke refresh token)
 * @route   POST /api/auth/logout
 * @access  Privé
 */
exports.logout = async (req, res, next) => {
  try {
    const token = req.cookies.refreshToken;

    if (token) {
      // Verwijder token uit database
      await RefreshToken.delete(token);
    }

    // Verwijder refresh token cookie
    res.clearCookie('refreshToken');

    res.json({
      success: true,
      message: 'Uitgelogd'
    });
  } catch (error) {
    next(error);
  }
};

/**
 * @desc    Huidige gebruiker ophalen
 * @route   GET /api/auth/me
 * @access  Privé
 */
exports.getMe = async (req, res, next) => {
  try {
    const user = await User.findById(req.user.id);

    if (!user) {
      return res.status(404).json({
        success: false,
        message: 'Gebruiker niet gevonden'
      });
    }

    res.json({
      success: true,
      user
    });
  } catch (error) {
    next(error);
  }
};

/**
 * @desc    Wachtwoord vergeten / reset link aanvragen
 * @route   POST /api/auth/forgot-password
 * @access  Openbaar
 */
exports.forgotPassword = async (req, res, next) => {
  try {
    const { email } = req.body;

    // Zoek gebruiker
    const user = await User.findByEmail(email);

    if (!user) {
      return res.status(200).json({
        success: true,
        message: 'Als je account bestaat, ontvang je een e-mail met instructies'
      });
    }

    // Genereer reset token
    const resetToken = await User.generateResetToken(user.id);

    // Stuur e-mail
    await sendPasswordResetEmail(
      user.email,
      resetToken,
      user.name
    );

    res.json({
      success: true,
      message: 'E-mail met reset instructies verstuurd'
    });
  } catch (error) {
    next(error);
  }
};

/**
 * @desc    Reset wachtwoord met token
 * @route   POST /api/auth/reset-password
 * @access  Openbaar
 */
exports.resetPassword = async (req, res, next) => {
  try {
    const { token, password } = req.body;

    // Valideer wachtwoord
    if (password.length < 8) {
      return res.status(400).json({
        success: false,
        message: 'Wachtwoord moet minimaal 8 tekens bevatten'
      });
    }

    // Reset wachtwoord
    const success = await User.resetPassword(token, password);

    if (!success) {
      return res.status(400).json({
        success: false,
        message: 'Ongeldige of verlopen token'
      });
    }

    res.json({
      success: true,
      message: 'Wachtwoord succesvol gewijzigd'
    });
  } catch (error) {
    next(error);
  }
};

/**
 * @desc    Google OAuth callback
 * @route   GET /api/auth/google/callback
 * @access  Openbaar
 */
exports.googleCallback = async (req, res, next) => {
  // Deze functie zou normaal door Passport.js worden afgehandeld
  res.redirect('/');
};

/**
 * @desc    Resend verificatie email
 * @route   POST /api/auth/resend-verification
 * @access  Openbaar
 */
exports.resendVerification = async (req, res, next) => {
  try {
    const { email } = req.body;

    // Zoek gebruiker
    const user = await User.findByEmail(email);

    if (!user) {
      return res.status(200).json({
        success: true,
        message: 'Als je account bestaat en nog niet is geverifieerd, ontvang je een nieuwe verificatie e-mail'
      });
    }

    // Controleer of account al geactiveerd is
    if (user.is_active) {
      return res.status(400).json({
        success: false,
        message: 'Dit account is al geactiveerd'
      });
    }

    // Maak nieuwe verificatie token als de oude er niet meer is
    let token;
    if (!user.activation_code) {
      // Genereer nieuwe token
      const result = await User.generateVerificationToken(user.id);
      token = result.verificationToken;
    }

    // Stuur verificatie e-mail
    await sendVerificationEmail(
      user.email,
      token || user.activation_code,
      user.name
    );

    res.json({
      success: true,
      message: 'Nieuwe verificatie e-mail verstuurd'
    });
  } catch (error) {
    next(error);
  }
};

/**
 * Helper functie om JWT token te genereren
 */
const generateToken = (user) => {
  return jwt.sign(
    { 
      id: user.id,
      email: user.email
    },
    process.env.JWT_SECRET,
    { expiresIn: process.env.JWT_EXPIRES || '1h' }
  );
};

/**
 * Helper functie om refresh token cookie in te stellen
 */
const setRefreshTokenCookie = (res, token) => {
  const cookieOptions = {
    httpOnly: true,
    secure: process.env.NODE_ENV === 'production',
    sameSite: 'strict',
    maxAge: 30 * 24 * 60 * 60 * 1000 // 30 dagen
  };

  res.cookie('refreshToken', token, cookieOptions);
}; 