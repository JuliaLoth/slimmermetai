const passport = require('passport');
const GoogleStrategy = require('passport-google-oauth20').Strategy;
const JwtStrategy = require('passport-jwt').Strategy;
const ExtractJwt = require('passport-jwt').ExtractJwt;
const User = require('../models/User');
require('dotenv').config();

// JWT configuratie opties
const jwtOptions = {
  jwtFromRequest: ExtractJwt.fromAuthHeaderAsBearerToken(),
  secretOrKey: process.env.JWT_SECRET
};

// Initialiseer passport authenticatie
const initializePassport = () => {
  
  // JWT Strategie voor tokens
  passport.use(
    new JwtStrategy(jwtOptions, async (payload, done) => {
      try {
        // Vind de gebruiker op basis van de ID in het JWT payload
        const user = await User.findById(payload.id);
        
        if (user) {
          return done(null, user);
        }
        
        return done(null, false);
      } catch (error) {
        return done(error, false);
      }
    })
  );
  
  // Google OAuth 2.0 Strategie
  passport.use(
    new GoogleStrategy({
      clientID: process.env.GOOGLE_CLIENT_ID,
      clientSecret: process.env.GOOGLE_CLIENT_SECRET,
      callbackURL: process.env.GOOGLE_CALLBACK_URL,
      scope: ['profile', 'email']
    },
    async (accessToken, refreshToken, profile, done) => {
      try {
        // Controleer of de gebruiker al bestaat in de database
        let user = await User.findOne({ email: profile.emails[0].value });
        
        if (user) {
          // Als de gebruiker al bestaat maar niet met Google is aangemeld
          if (!user.googleId) {
            user.googleId = profile.id;
            await user.save();
          }
        } else {
          // Maak een nieuwe gebruiker aan als deze nog niet bestaat
          user = await User.create({
            googleId: profile.id,
            email: profile.emails[0].value,
            firstName: profile.name.givenName || '',
            lastName: profile.name.familyName || '',
            profilePicture: profile.photos[0]?.value || '',
            isVerified: true // Google gebruikers zijn automatisch geverifieerd
          });
        }
        
        return done(null, user);
      } catch (error) {
        return done(error, false);
      }
    })
  );
  
  // Serialize gebruiker voor sessies
  passport.serializeUser((user, done) => {
    done(null, user.id);
  });
  
  // Deserialize gebruiker vanuit sessie
  passport.deserializeUser(async (id, done) => {
    try {
      const user = await User.findById(id);
      done(null, user);
    } catch (error) {
      done(error, null);
    }
  });
};

module.exports = initializePassport; 