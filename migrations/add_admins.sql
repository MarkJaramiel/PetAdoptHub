CREATE TABLE IF NOT EXISTS admins (
    user_id INT PRIMARY KEY,
    fullname VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_admins_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

INSERT INTO admins (user_id, fullname, email, password, created_at)
SELECT id, fullname, email, password, created_at
FROM users
WHERE role = 'admin'
ON DUPLICATE KEY UPDATE
    fullname = VALUES(fullname),
    email = VALUES(email),
    password = VALUES(password);