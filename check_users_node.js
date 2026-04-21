const mysql = require('mysql2/promise');

async function checkUsers() {
  const connection = await mysql.createConnection({
    host: 'localhost',
    user: 'root',
    password: '',
    database: 'dfps'
  });

  try {
    const [rows] = await connection.execute('SELECT id, first_name, last_name, phone, role FROM users LIMIT 10');
    console.log(JSON.stringify(rows, null, 2));
  } catch (err) {
    console.error(err);
  } finally {
    await connection.end();
  }
}

checkUsers();
