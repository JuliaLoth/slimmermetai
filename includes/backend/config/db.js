/**
 * Database configuratie bestand voor MySQL (gebruikt met Antagonist hosting)
 * Aangepast voor het gebruik van meerdere databases
 */

const mysql = require('mysql2/promise');
require('dotenv').config();

// Pool aanmaken voor users database verbindingen
const usersPool = mysql.createPool({
  host: process.env.DB_HOST || 'localhost',
  user: process.env.DB_USER || 'root',
  password: process.env.DB_PASSWORD || '',
  database: process.env.DB_NAME || 'slimmermetai',
  waitForConnections: true,
  connectionLimit: 10,
  queueLimit: 0
});

// Pool aanmaken voor sessions database verbindingen
const sessionsPool = mysql.createPool({
  host: process.env.SESSIONS_DB_HOST || 'localhost',
  user: process.env.SESSIONS_DB_USER || 'root',
  password: process.env.SESSIONS_DB_PASSWORD || '',
  database: process.env.SESSIONS_DB_NAME || 'slimmermetai_sessions',
  waitForConnections: true,
  connectionLimit: 10,
  queueLimit: 0
});

// Pool aanmaken voor login attempts database verbindingen
const loginAttemptsPool = mysql.createPool({
  host: process.env.LOGIN_ATTEMPTS_DB_HOST || 'localhost',
  user: process.env.LOGIN_ATTEMPTS_DB_USER || 'root',
  password: process.env.LOGIN_ATTEMPTS_DB_PASSWORD || '',
  database: process.env.LOGIN_ATTEMPTS_DB_NAME || 'slimmermetai_login_attempts',
  waitForConnections: true,
  connectionLimit: 10,
  queueLimit: 0
});

// Test users database connectie
const connectUsersDB = async () => {
  try {
    const connection = await usersPool.getConnection();
    console.log('Users database verbinding succesvol');
    connection.release();
    return true;
  } catch (error) {
    console.error('Users database verbinding mislukt:', error.message);
    return false;
  }
};

// Test sessions database connectie
const connectSessionsDB = async () => {
  try {
    const connection = await sessionsPool.getConnection();
    console.log('Sessions database verbinding succesvol');
    connection.release();
    return true;
  } catch (error) {
    console.error('Sessions database verbinding mislukt:', error.message);
    return false;
  }
};

// Test login attempts database connectie
const connectLoginAttemptsDB = async () => {
  try {
    const connection = await loginAttemptsPool.getConnection();
    console.log('Login attempts database verbinding succesvol');
    connection.release();
    return true;
  } catch (error) {
    console.error('Login attempts database verbinding mislukt:', error.message);
    return false;
  }
};

// Test alle database connecties
const connectDB = async () => {
  const usersConnected = await connectUsersDB();
  const sessionsConnected = await connectSessionsDB();
  const loginAttemptsConnected = await connectLoginAttemptsDB();
  return usersConnected && sessionsConnected && loginAttemptsConnected;
};

// Query uitvoeren functie voor users database (met parameters)
const queryUsers = async (sql, params = []) => {
  try {
    const [results] = await usersPool.execute(sql, params);
    return results;
  } catch (error) {
    console.error('Users database query fout:', error.message);
    throw error;
  }
};

// Query uitvoeren functie voor sessions database (met parameters)
const querySessions = async (sql, params = []) => {
  try {
    const [results] = await sessionsPool.execute(sql, params);
    return results;
  } catch (error) {
    console.error('Sessions database query fout:', error.message);
    throw error;
  }
};

// Query uitvoeren functie voor login attempts database (met parameters)
const queryLoginAttempts = async (sql, params = []) => {
  try {
    const [results] = await loginAttemptsPool.execute(sql, params);
    return results;
  } catch (error) {
    console.error('Login attempts database query fout:', error.message);
    throw error;
  }
};

// Een rij ophalen uit users database
const getUserRow = async (sql, params = []) => {
  const rows = await queryUsers(sql, params);
  return rows[0] || null;
};

// Een rij ophalen uit sessions database
const getSessionRow = async (sql, params = []) => {
  const rows = await querySessions(sql, params);
  return rows[0] || null;
};

// Een rij ophalen uit login attempts database
const getLoginAttemptRow = async (sql, params = []) => {
  const rows = await queryLoginAttempts(sql, params);
  return rows[0] || null;
};

// ID van laatst ingevoegde rij ophalen uit users database
const insertUserId = async (sql, params = []) => {
  try {
    const [result] = await usersPool.execute(sql, params);
    return result.insertId;
  } catch (error) {
    console.error('Users database insert fout:', error.message);
    throw error;
  }
};

// ID van laatst ingevoegde rij ophalen uit sessions database
const insertSessionId = async (sql, params = []) => {
  try {
    const [result] = await sessionsPool.execute(sql, params);
    return result.insertId;
  } catch (error) {
    console.error('Sessions database insert fout:', error.message);
    throw error;
  }
};

// ID van laatst ingevoegde rij ophalen uit login attempts database
const insertLoginAttemptId = async (sql, params = []) => {
  try {
    const [result] = await loginAttemptsPool.execute(sql, params);
    return result.insertId;
  } catch (error) {
    console.error('Login attempts database insert fout:', error.message);
    throw error;
  }
};

// Transactie uitvoeren in users database
const usersTransaction = async (callback) => {
  const connection = await usersPool.getConnection();
  
  try {
    await connection.beginTransaction();
    const result = await callback(connection);
    await connection.commit();
    return result;
  } catch (error) {
    await connection.rollback();
    throw error;
  } finally {
    connection.release();
  }
};

// Transactie uitvoeren in sessions database
const sessionsTransaction = async (callback) => {
  const connection = await sessionsPool.getConnection();
  
  try {
    await connection.beginTransaction();
    const result = await callback(connection);
    await connection.commit();
    return result;
  } catch (error) {
    await connection.rollback();
    throw error;
  } finally {
    connection.release();
  }
};

// Transactie uitvoeren in login attempts database
const loginAttemptsTransaction = async (callback) => {
  const connection = await loginAttemptsPool.getConnection();
  
  try {
    await connection.beginTransaction();
    const result = await callback(connection);
    await connection.commit();
    return result;
  } catch (error) {
    await connection.rollback();
    throw error;
  } finally {
    connection.release();
  }
};

module.exports = {
  connectDB,
  connectUsersDB,
  connectSessionsDB,
  connectLoginAttemptsDB,
  queryUsers,
  querySessions,
  queryLoginAttempts,
  getUserRow,
  getSessionRow,
  getLoginAttemptRow,
  insertUserId,
  insertSessionId,
  insertLoginAttemptId,
  usersTransaction,
  sessionsTransaction,
  loginAttemptsTransaction,
  usersPool,
  sessionsPool,
  loginAttemptsPool
}; 