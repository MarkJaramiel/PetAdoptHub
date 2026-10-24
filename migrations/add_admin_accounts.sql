CREATE TABLE IF NOT EXISTS admin_accounts (
    user_id INT PRIMARY KEY,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    display_name VARCHAR(100) NOT NULL,
    account_status ENUM('Pending', 'Approved', 'Rejected') NOT NULL DEFAULT 'Approved',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_admin_accounts_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

INSERT INTO admin_accounts (user_id, email, password_hash, display_name, account_status)
SELECT id, email, password, fullname, COALESCE(account_status, 'Approved')
FROM users
WHERE role = 'admin'
ON DUPLICATE KEY UPDATE
    email = VALUES(email),
    password_hash = VALUES(password_hash),
    display_name = VALUES(display_name),
    account_status = VALUES(account_status);