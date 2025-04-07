const User = require('../models/User');
const { revokeAllUserTokens } = require('../utils/jwtService');

/**
 * @desc    Alle gebruikers ophalen
 * @route   GET /api/users
 * @access  Privé/Admin
 */
exports.getUsers = async (req, res, next) => {
  try {
    const users = await User.find().select('-refreshTokens');

    res.json({
      success: true,
      count: users.length,
      data: users
    });
  } catch (error) {
    next(error);
  }
};

/**
 * @desc    Gebruiker ophalen op ID
 * @route   GET /api/users/:id
 * @access  Privé/Admin
 */
exports.getUser = async (req, res, next) => {
  try {
    const user = await User.findById(req.params.id).select('-refreshTokens');

    if (!user) {
      return res.status(404).json({ 
        success: false, 
        message: 'Gebruiker niet gevonden' 
      });
    }

    res.json({
      success: true,
      data: user
    });
  } catch (error) {
    next(error);
  }
};

/**
 * @desc    Profiel bijwerken
 * @route   PUT /api/users/profile
 * @access  Privé
 */
exports.updateProfile = async (req, res, next) => {
  try {
    const { firstName, lastName, email } = req.body;
    const userId = req.user.id;

    // Controleer of e-mailadres al in gebruik is door een andere gebruiker
    if (email) {
      const existingUser = await User.findOne({ email });
      if (existingUser && existingUser._id.toString() !== userId) {
        return res.status(400).json({
          success: false,
          message: 'E-mailadres is al in gebruik'
        });
      }
    }

    const user = await User.findByIdAndUpdate(
      userId, 
      { firstName, lastName, email },
      { new: true, runValidators: true }
    ).select('-refreshTokens');

    if (!user) {
      return res.status(404).json({ 
        success: false, 
        message: 'Gebruiker niet gevonden' 
      });
    }

    res.json({
      success: true,
      data: user
    });
  } catch (error) {
    next(error);
  }
};

/**
 * @desc    Wachtwoord wijzigen
 * @route   PUT /api/users/password
 * @access  Privé
 */
exports.updatePassword = async (req, res, next) => {
  try {
    const { currentPassword, newPassword } = req.body;
    const userId = req.user.id;

    // Haal gebruiker op met wachtwoord
    const user = await User.findById(userId).select('+password');

    if (!user) {
      return res.status(404).json({ 
        success: false, 
        message: 'Gebruiker niet gevonden' 
      });
    }

    // Controleer huidige wachtwoord
    const isMatch = await user.matchPassword(currentPassword);
    if (!isMatch) {
      return res.status(401).json({ 
        success: false, 
        message: 'Huidig wachtwoord is onjuist' 
      });
    }

    // Update wachtwoord
    user.password = newPassword;
    await user.save();

    res.json({
      success: true,
      message: 'Wachtwoord succesvol gewijzigd'
    });
  } catch (error) {
    next(error);
  }
};

/**
 * @desc    Gebruiker bijwerken (admin)
 * @route   PUT /api/users/:id
 * @access  Privé/Admin
 */
exports.updateUser = async (req, res, next) => {
  try {
    const { firstName, lastName, email, role, isVerified } = req.body;
    
    // Controleer of e-mailadres al in gebruik is door een andere gebruiker
    if (email) {
      const existingUser = await User.findOne({ email });
      if (existingUser && existingUser._id.toString() !== req.params.id) {
        return res.status(400).json({
          success: false,
          message: 'E-mailadres is al in gebruik'
        });
      }
    }

    const user = await User.findByIdAndUpdate(
      req.params.id, 
      { firstName, lastName, email, role, isVerified },
      { new: true, runValidators: true }
    ).select('-refreshTokens');

    if (!user) {
      return res.status(404).json({ 
        success: false, 
        message: 'Gebruiker niet gevonden' 
      });
    }

    res.json({
      success: true,
      data: user
    });
  } catch (error) {
    next(error);
  }
};

/**
 * @desc    Gebruiker verwijderen
 * @route   DELETE /api/users/:id
 * @access  Privé/Admin
 */
exports.deleteUser = async (req, res, next) => {
  try {
    // Herroep alle tokens van de gebruiker
    await revokeAllUserTokens(req.params.id, req.ip);
    
    const user = await User.findByIdAndDelete(req.params.id);

    if (!user) {
      return res.status(404).json({ 
        success: false, 
        message: 'Gebruiker niet gevonden' 
      });
    }

    res.json({
      success: true,
      message: 'Gebruiker succesvol verwijderd'
    });
  } catch (error) {
    next(error);
  }
};

/**
 * @desc    Account verwijderen (voor eigen account)
 * @route   DELETE /api/users/account
 * @access  Privé
 */
exports.deleteAccount = async (req, res, next) => {
  try {
    const userId = req.user.id;
    
    // Herroep alle tokens van de gebruiker
    await revokeAllUserTokens(userId, req.ip);
    
    const user = await User.findByIdAndDelete(userId);

    if (!user) {
      return res.status(404).json({ 
        success: false, 
        message: 'Gebruiker niet gevonden' 
      });
    }

    // Verwijder refresh token cookie
    res.clearCookie('refreshToken');

    res.json({
      success: true,
      message: 'Account succesvol verwijderd'
    });
  } catch (error) {
    next(error);
  }
}; 