CREATE DATABASE IF NOT EXISTS guvi_app;
USE guvi_app;

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  full_name VARCHAR(120) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Sample user password: Pass@123 (generated via password_hash in PHP)
-- Replace the hash below using your own script before production.
INSERT INTO users (email, password_hash, full_name)
VALUES ('demo@guvi.in', '$2y$10$g/JPE2vjY.xfWMqafQfjs.q6Zh6A9lR75U5n6tMDN9fY2s8AU9U3i', 'Demo User')
ON DUPLICATE KEY UPDATE full_name = VALUES(full_name);
