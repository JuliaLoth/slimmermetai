/**
 * CSRF Bescherming Middleware
 * 
 * Deze middleware implementeert CSRF bescherming voor de API.
 * Tokens worden gegenereerd en gevalideerd voor POST/PUT/DELETE requests.
 */

const crypto = require('crypto');

// Helper functie om CSRF tokens te genereren
const generateToken = () => {
  return crypto.randomBytes(32).toString('hex');
};

// Helper functie om CSRF token te valideren
const validateToken = (sessionToken, requestToken) => {
  if (!sessionToken || !requestToken) {
    return false;
  }
  return crypto.timingSafeEqual(
    Buffer.from(sessionToken, 'hex'), 
    Buffer.from(requestToken, 'hex')
  );
};

/**
 * Middleware om CSRF token te genereren en in de sessie op te slaan
 */
exports.generateCsrfToken = (req, res, next) => {
  // Alleen genereren als er nog geen token is of als er een nieuwe sessie is
  if (!req.session.csrfToken) {
    req.session.csrfToken = generateToken();
  }
  
  // Voeg het token toe aan res.locals voor gebruik in views/templates
  res.locals.csrfToken = req.session.csrfToken;
  
  // Voeg het token toe aan response headers
  res.setHeader('X-CSRF-Token', req.session.csrfToken);
  
  next();
};

/**
 * Middleware om CSRF token te valideren
 */
exports.validateCsrfToken = (req, res, next) => {
  // Sla validatie over voor niet-muterende methoden
  if (['GET', 'HEAD', 'OPTIONS'].includes(req.method)) {
    return next();
  }

  // Haal token op uit verschillende mogelijke locaties
  const token = 
    req.body._csrf || 
    req.query._csrf || 
    req.headers['x-csrf-token'] || 
    req.headers['x-xsrf-token'];

  if (!token) {
    return res.status(403).json({ 
      success: false, 
      message: 'CSRF token ontbreekt' 
    });
  }

  // Valideer token
  try {
    const sessionToken = req.session.csrfToken;
    
    if (!validateToken(sessionToken, token)) {
      return res.status(403).json({ 
        success: false, 
        message: 'Ongeldige CSRF token' 
      });
    }
    
    // Optional: regenerate token na succesvolle validatie voor extra veiligheid
    req.session.csrfToken = generateToken();
    res.setHeader('X-CSRF-Token', req.session.csrfToken);
    
    next();
  } catch (error) {
    console.error('CSRF validation error:', error);
    return res.status(403).json({ 
      success: false, 
      message: 'CSRF validatie fout' 
    });
  }
}; 