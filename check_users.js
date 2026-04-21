const mysql = require('mysql2/promise');

async function checkUsers() {
    const config = {
        host: 'localhost',
        user: 'root',
        password: '',
    };

    try {
        const connection = await mysql.createConnection(config);
        
        // Check for dfps_db
        const [databases] = await connection.query('SHOW DATABASES LIKE "dfps_db"');
        let dbName = 'dfps'; // Default
        if (databases.length > 0) {
            dbName = 'dfps_db';
        }
        console.log(`Using database: ${dbName}`);
        
        await connection.query(`USE ${dbName}`);
        
        const [rows] = await connection.query(`
            SELECT COUNT(*) as farmerCount 
            FROM users 
            WHERE role = 'FARMER' 
            AND phone IS NOT NULL 
            AND phone != ''
        `);
        
        console.log(`Count of users with role='FARMER' and non-empty 'phone': ${rows[0].farmerCount}`);
        
        await connection.end();
    } catch (err) {
        console.error('Error connecting to MySQL:', err);
    }
}

checkUsers();
