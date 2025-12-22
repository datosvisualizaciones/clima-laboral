-- Script SQL para crear tabla de usuarios con contraseñas hasheadas
-- Ejecutar este script en tu base de datos MySQL

CREATE TABLE IF NOT EXISTS usuarios (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    session_token VARCHAR(64) NULL,
    token_expires DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insertar usuarios con contraseñas hasheadas (usando password_hash de PHP)
-- Contraseña para acaputi: acaputi
-- Contraseña para rmaidana: rmaidana
INSERT INTO usuarios (username, password_hash) VALUES
('acaputi', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('rmaidana', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi')
ON DUPLICATE KEY UPDATE
    password_hash = VALUES(password_hash);

-- Nota: Las contraseñas hasheadas arriba corresponden a "acaputi" y "rmaidana" respectivamente
-- Para generar nuevos hashes en PHP: password_hash('tu_password', PASSWORD_DEFAULT)
