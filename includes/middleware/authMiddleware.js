const passport = require('passport');

/**
 * Bescherm routes die authenticatie vereisen
 */
exports.protect = (req, res, next) => {
  passport.authenticate('jwt', { session: false }, (err, user, info) => {
    if (err) {
      return next(err);
    }
    
    if (!user) {
      return res.status(401).json({ 
        success: false, 
        message: 'Niet geautoriseerd om deze pagina te bekijken' 
      });
    }
    
    // Voeg gebruiker toe aan request
    req.user = user;
    next();
  })(req, res, next);
};

/**
 * Verleen toegang aan alleen bepaalde rollen
 * @param {String[]} roles - Array van rollen die toegang mogen hebben
 */
exports.authorize = (roles) => {
  return (req, res, next) => {
    if (!req.user) {
      return res.status(401).json({ 
        success: false, 
        message: 'Niet geautoriseerd om deze pagina te bekijken' 
      });
    }
    
    if (!roles.includes(req.user.role)) {
      return res.status(403).json({ 
        success: false, 
        message: `Rol ${req.user.role} heeft geen toegang tot deze actie` 
      });
    }
    
    next();
  };
};

/**
 * Google OAuth authenticatie starten
 */
exports.googleAuth = passport.authenticate('google', { 
  scope: ['profile', 'email'],
  session: false 
});

/**
 * Google OAuth callback
 */
exports.googleCallback = passport.authenticate('google', { 
  session: false, 
  failureRedirect: `${process.env.FRONTEND_URL}/login?error=google-auth-failed` 
});