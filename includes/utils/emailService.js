const nodemailer = require('nodemailer');
require('dotenv').config();

// Configureer SMTP transporteur
const transporter = nodemailer.createTransport({
  host: process.env.SMTP_HOST,
  port: process.env.SMTP_PORT,
  secure: process.env.SMTP_PORT === '465', // true voor 465, false voor andere poorten
  auth: {
    user: process.env.SMTP_USER,
    pass: process.env.SMTP_PASSWORD,
  },
});

/**
 * Email verzenden
 * @param {Object} options - Email opties
 * @param {String} options.to - Ontvanger email
 * @param {String} options.subject - Email onderwerp
 * @param {String} options.html - HTML inhoud
 * @returns {Promise}
 */
const sendEmail = async (options) => {
  const mailOptions = {
    from: process.env.EMAIL_FROM,
    to: options.to,
    subject: options.subject,
    html: options.html,
  };

  try {
    const info = await transporter.sendMail(mailOptions);
    console.log(`Email verzonden: ${info.messageId}`);
    return info;
  } catch (error) {
    console.error(`Fout bij verzenden email: ${error.message}`);
    throw error;
  }
};

/**
 * Verstuur verificatie email
 * @param {String} to - Ontvanger email
 * @param {String} verificationToken - Token voor emailverificatie
 * @param {String} name - Naam van de gebruiker
 * @returns {Promise}
 */
const sendVerificationEmail = async (to, verificationToken, name) => {
  const verifyUrl = `${process.env.FRONTEND_URL}/verify-email?token=${verificationToken}`;
  
  const html = `
    <div style="max-width: 600px; margin: 0 auto; padding: 20px; font-family: Arial, sans-serif;">
      <div style="text-align: center; margin-bottom: 20px;">
        <img src="${process.env.FRONTEND_URL}/images/Logo.svg" alt="Slimmer met AI Logo" style="max-width: 100px;">
      </div>
      <div style="background-color: #f9f9f9; border-radius: 10px; padding: 20px; margin-bottom: 20px;">
        <h2 style="color: #333; margin-bottom: 15px;">Bevestig je e-mailadres</h2>
        <p style="color: #666; line-height: 1.5; margin-bottom: 20px;">
          Hallo ${name || 'daar'},
        </p>
        <p style="color: #666; line-height: 1.5; margin-bottom: 20px;">
          Bedankt voor je registratie bij Slimmer met AI. Klik op de onderstaande knop om je e-mailadres te verifiëren en je account te activeren.
        </p>
        <div style="text-align: center; margin: 30px 0;">
          <a href="${verifyUrl}" style="background-color: #5852f2; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold; display: inline-block;">Verifieer E-mailadres</a>
        </div>
        <p style="color: #666; line-height: 1.5; margin-bottom: 20px;">
          Als je niet op de knop kunt klikken, kopieer dan de onderstaande link en plak deze in je browser:
        </p>
        <p style="background-color: #f0f0f0; padding: 10px; border-radius: 5px; word-break: break-all;">
          ${verifyUrl}
        </p>
        <p style="color: #666; line-height: 1.5; margin-top: 30px;">
          Als je geen account hebt aangemaakt, kun je deze e-mail veilig negeren.
        </p>
      </div>
      <div style="text-align: center; color: #999; font-size: 12px;">
        <p>&copy; ${new Date().getFullYear()} Slimmer met AI. Alle rechten voorbehouden.</p>
      </div>
    </div>
  `;
  
  return sendEmail({
    to,
    subject: 'Verifieer je e-mailadres - Slimmer met AI',
    html
  });
};

/**
 * Verstuur welkomst email na verificatie
 * @param {String} to - Ontvanger email
 * @param {String} name - Naam van de gebruiker
 * @returns {Promise}
 */
const sendWelcomeEmail = async (to, name) => {
  const loginUrl = `${process.env.FRONTEND_URL}/login`;
  const dashboardUrl = `${process.env.FRONTEND_URL}/dashboard`;
  
  const html = `
    <div style="max-width: 600px; margin: 0 auto; padding: 20px; font-family: Arial, sans-serif;">
      <div style="text-align: center; margin-bottom: 20px;">
        <img src="${process.env.FRONTEND_URL}/images/Logo.svg" alt="Slimmer met AI Logo" style="max-width: 100px;">
      </div>
      <div style="background-color: #f9f9f9; border-radius: 10px; padding: 20px; margin-bottom: 20px;">
        <h2 style="color: #333; margin-bottom: 15px;">Welkom bij Slimmer met AI!</h2>
        <p style="color: #666; line-height: 1.5; margin-bottom: 20px;">
          Hallo ${name || 'daar'},
        </p>
        <p style="color: #666; line-height: 1.5; margin-bottom: 20px;">
          Geweldig nieuws! Je account is nu volledig geactiveerd en je kunt inloggen om alle functies van Slimmer met AI te gebruiken.
        </p>
        <p style="color: #666; line-height: 1.5; margin-bottom: 20px;">
          Hier zijn enkele dingen die je kunt doen:
        </p>
        <ul style="color: #666; line-height: 1.5; margin-bottom: 20px;">
          <li>Verken onze collectie AI-tools en prompts</li>
          <li>Volg onze e-learning modules om je AI-vaardigheden te verbeteren</li>
          <li>Sla je favoriete content op voor later gebruik</li>
          <li>Deel jouw ervaringen met onze community</li>
        </ul>
        <div style="text-align: center; margin: 30px 0;">
          <a href="${dashboardUrl}" style="background-color: #5852f2; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold; display: inline-block;">Ga naar mijn Dashboard</a>
        </div>
        <p style="color: #666; line-height: 1.5; margin-top: 30px;">
          We wensen je veel plezier en succes met het ontdekken van de mogelijkheden van AI!
        </p>
        <p style="color: #666; line-height: 1.5;">
          Met vriendelijke groet,<br>
          Het Slimmer met AI-team
        </p>
      </div>
      <div style="text-align: center; color: #999; font-size: 12px;">
        <p>&copy; ${new Date().getFullYear()} Slimmer met AI. Alle rechten voorbehouden.</p>
      </div>
    </div>
  `;
  
  return sendEmail({
    to,
    subject: 'Welkom bij Slimmer met AI!',
    html
  });
};

/**
 * Verstuur wachtwoord reset email
 * @param {String} to - Ontvanger email
 * @param {String} resetToken - Token voor wachtwoord reset
 * @param {String} name - Naam van de gebruiker
 * @returns {Promise}
 */
const sendPasswordResetEmail = async (to, resetToken, name) => {
  const resetUrl = `${process.env.FRONTEND_URL}/reset-password?token=${resetToken}`;
  
  const html = `
    <div style="max-width: 600px; margin: 0 auto; padding: 20px; font-family: Arial, sans-serif;">
      <div style="text-align: center; margin-bottom: 20px;">
        <img src="${process.env.FRONTEND_URL}/images/Logo.svg" alt="Slimmer met AI Logo" style="max-width: 100px;">
      </div>
      <div style="background-color: #f9f9f9; border-radius: 10px; padding: 20px; margin-bottom: 20px;">
        <h2 style="color: #333; margin-bottom: 15px;">Wachtwoord resetten</h2>
        <p style="color: #666; line-height: 1.5; margin-bottom: 20px;">
          Hallo ${name || 'daar'},
        </p>
        <p style="color: #666; line-height: 1.5; margin-bottom: 20px;">
          Je ontvangt deze e-mail omdat je een verzoek hebt gedaan om je wachtwoord te resetten. Klik op de onderstaande knop om een nieuw wachtwoord in te stellen.
        </p>
        <div style="text-align: center; margin: 30px 0;">
          <a href="${resetUrl}" style="background-color: #5852f2; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold; display: inline-block;">Reset Wachtwoord</a>
        </div>
        <p style="color: #666; line-height: 1.5; margin-bottom: 20px;">
          Als je niet op de knop kunt klikken, kopieer dan de onderstaande link en plak deze in je browser:
        </p>
        <p style="background-color: #f0f0f0; padding: 10px; border-radius: 5px; word-break: break-all;">
          ${resetUrl}
        </p>
        <p style="color: #666; line-height: 1.5; margin-top: 30px;">
          Deze link is 1 uur geldig. Als je geen wachtwoord reset hebt aangevraagd, kun je deze e-mail veilig negeren.
        </p>
      </div>
      <div style="text-align: center; color: #999; font-size: 12px;">
        <p>&copy; ${new Date().getFullYear()} Slimmer met AI. Alle rechten voorbehouden.</p>
      </div>
    </div>
  `;
  
  return sendEmail({
    to,
    subject: 'Wachtwoord resetten - Slimmer met AI',
    html
  });
};

/**
 * Verstuur notificatie email bij aanmelding voor admin
 * @param {String} to - Admin email
 * @param {Object} userData - Gebruikersgegevens van nieuwe registratie
 * @returns {Promise}
 */
const sendNewUserNotificationEmail = async (to, userData) => {
  const adminUrl = `${process.env.FRONTEND_URL}/admin/users`;
  
  const html = `
    <div style="max-width: 600px; margin: 0 auto; padding: 20px; font-family: Arial, sans-serif;">
      <div style="text-align: center; margin-bottom: 20px;">
        <img src="${process.env.FRONTEND_URL}/images/Logo.svg" alt="Slimmer met AI Logo" style="max-width: 100px;">
      </div>
      <div style="background-color: #f9f9f9; border-radius: 10px; padding: 20px; margin-bottom: 20px;">
        <h2 style="color: #333; margin-bottom: 15px;">Nieuwe gebruiker geregistreerd</h2>
        <p style="color: #666; line-height: 1.5; margin-bottom: 20px;">
          Hallo Admin,
        </p>
        <p style="color: #666; line-height: 1.5; margin-bottom: 20px;">
          Er is een nieuwe gebruiker geregistreerd op Slimmer met AI.
        </p>
        <div style="background-color: #f0f0f0; padding: 15px; border-radius: 5px; margin: 20px 0;">
          <p style="margin: 5px 0;"><strong>Naam:</strong> ${userData.name || 'Niet opgegeven'}</p>
          <p style="margin: 5px 0;"><strong>E-mail:</strong> ${userData.email}</p>
          <p style="margin: 5px 0;"><strong>Tijdstip:</strong> ${new Date().toLocaleString('nl-NL')}</p>
        </div>
        <div style="text-align: center; margin: 30px 0;">
          <a href="${adminUrl}" style="background-color: #5852f2; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold; display: inline-block;">Ga naar Gebruikersbeheer</a>
        </div>
      </div>
      <div style="text-align: center; color: #999; font-size: 12px;">
        <p>&copy; ${new Date().getFullYear()} Slimmer met AI. Alle rechten voorbehouden.</p>
      </div>
    </div>
  `;
  
  return sendEmail({
    to,
    subject: 'Nieuwe gebruiker geregistreerd - Slimmer met AI',
    html
  });
};

module.exports = {
  sendEmail,
  sendVerificationEmail,
  sendWelcomeEmail,
  sendPasswordResetEmail,
  sendNewUserNotificationEmail
}; 