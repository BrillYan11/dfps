const mysql = require('mysql2/promise');

async function listDatabases() {
    const config = {
        host: 'localhost',
        user: 'root',
        password: '',
    };

    try {
        const connection = await mysql.createConnection(config);
        const [databases] = await connection.query('SHOW DATABASES');
        console.log('Available Databases:');
        databases.forEach(db => console.log(` - ${db.Database}`));
        await connection.end();
    } catch (err) {
        console.error('Error connecting to MySQL:', err);
    }
}

listDatabases();
