# Premium Barbershop System Documentation

Welcome to the Premium Barbershop system. This document provides essential information for buyers on how to access the system, set up the database, and understand the general project structure.

## Quick Start: Default Admin Credentials

When the system is deployed and runs for the first time, it automatically creates a default administrator account so you can log in and fully customize the application.

- **Admin Login URL:** `yourdomain.com/pages/login.html`
- **Username:** `admin`
- **Password:** `123456`

> **IMPORTANT SECURITY NOTICE:** Please change your password from the Admin Dashboard immediately after your first login to secure your system.

---

## Database Connection Setup

To connect the application to your MySQL/MariaDB database, you must configure the credentials in the main connection file.

**File Location:** `sistema/conexion.php`  
**Lines to Edit:** Lines 9 to 13

```php
// --- 1. DATABASE CREDENTIALS ---
$db_host     = "localhost"; // Line 9: The hostname or IP address of your DB server
$db_user     = "root";                  // Line 10: The database username
$db_password = "your_db_password"; // Line 11: The password for the DB user
$db_name     = "barberia_db";                 // Line 12: The exact name of your database
$db_port     = "3306";                   // Line 13: The port your database is running on
```

### Explanation of Connection Fields:
- **`$db_host`**: Where your database is hosted (e.g., `localhost` for XAMPP/WAMP, or a remote URL like `db.yourhost.com`).
- **`$db_user`**: The user account that has privileges to read and write to the database (usually `root` locally).
- **`$db_password`**: The secret password belonging to the database user.
- **`$db_name`**: The specific schema/database you created to store the barbershop tables.
- **`$db_port`**: The communication port for the database. Default is usually `3306`.

---

## General System Structure

The system is organized logically into the following main directories:

- **`assets/`**: Contains all static resources such as CSS styles, JavaScript logic files, and images used throughout the site.
- **`pages/`**: Contains the front-end HTML views. Key files include `login.html`, `admin.html` (Admin Dashboard), `staff.html` (Barber Portal), `booking.html`, `calendar.html`, and `confirm.html`.
- **`sistema/`**: The core PHP backend. It holds the API endpoints for fetching and saving data, the authentication logic (`auth.php`), and the database connection/auto-migration script (`conexion.php`).
- **`index.html`**: The main public landing page where customers can see your services and start booking.
- **`database.sql`**: The raw SQL script for manual database creation (see section below).

---

## Manual Database Setup (Fallback SQL Script)

The system features an **Auto-Migration Engine**. As soon as you enter the correct credentials in `conexion.php` and load the site in your browser, the system will automatically build all tables and insert default settings. 

However, if your specific hosting environment blocks automatic table creation, you can manually run the following SQL script in your database manager (such as phpMyAdmin) to create all tables and their required components.

```sql
-- ===================================================================
--  DATABASE — Premium Barbershop (MySQL / MariaDB)
-- ===================================================================
--
--  INSTRUCTIONS:
--  --------------
--  1. Open phpMyAdmin (http://localhost/phpmyadmin)
--  2. Create a database named "barberia_db" with collation utf8mb4_general_ci
--  3. Select that database → "SQL" tab → paste this content → execute
--
--  ENGINE: InnoDB (supports foreign keys and transactions)
--  CHARSET: utf8mb4 (supports emojis, accents, and special characters)
-- ===================================================================

-- Create the database (if it does not exist)
CREATE DATABASE IF NOT EXISTS `barberia_db`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_general_ci;

-- Select the database
USE `barberia_db`;

-- --- 1. Users Table (Barbers and Administrators) -------------------
CREATE TABLE IF NOT EXISTS `usuarios` (
    `id`            INT AUTO_INCREMENT PRIMARY KEY,
    `username`      VARCHAR(50) UNIQUE NOT NULL     COMMENT 'Username for login',
    `password_hash` VARCHAR(255) NOT NULL            COMMENT 'Password encrypted with password_hash()',
    `nombre`        VARCHAR(100) NOT NULL             COMMENT 'Real name of the barber',
    `apellido`      VARCHAR(100) NOT NULL             COMMENT 'Last name of the barber',
    `rol`           ENUM('admin', 'barbero') NOT NULL DEFAULT 'barbero' COMMENT 'User role',
    `especialidad`  VARCHAR(255) DEFAULT NULL         COMMENT 'E.g.: Fades & Classics',
    `imagen_url`    TEXT DEFAULT NULL                  COMMENT 'Relative path to the barber\'s photo',
    `activo`        TINYINT(1) NOT NULL DEFAULT 1     COMMENT '1 = active, 0 = inactive',
    `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --- 2. Services Table -------------------------------------------
CREATE TABLE IF NOT EXISTS `servicios` (
    `id`            INT AUTO_INCREMENT PRIMARY KEY,
    `nombre`        VARCHAR(100) NOT NULL              COMMENT 'Name of the service (e.g.: Fade & Taper)',
    `descripcion`   TEXT DEFAULT NULL                   COMMENT 'Detailed description of the service',
    `precio`        DECIMAL(10,2) NOT NULL              COMMENT 'Price in local currency',
    `duracion_min`  INT NOT NULL                        COMMENT 'Estimated duration in minutes',
    `imagen_url`    TEXT DEFAULT NULL                   COMMENT 'Relative path to the service\'s image',
    `activo`        TINYINT(1) NOT NULL DEFAULT 1       COMMENT '1 = visible, 0 = hidden',
    `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --- 3. Appointments Table ---------------------------------------
CREATE TABLE IF NOT EXISTS `citas` (
    `id`                INT AUTO_INCREMENT PRIMARY KEY,
    `barbero_id`        INT NOT NULL                    COMMENT 'ID of the assigned barber',
    `servicio_id`       INT NOT NULL                    COMMENT 'ID of the requested service',
    `cliente_nombre`    VARCHAR(100) NOT NULL            COMMENT 'Full name of the client',
    `cliente_email`     VARCHAR(150) DEFAULT NULL        COMMENT 'Client email (optional)',
    `cliente_telefono`  VARCHAR(20) DEFAULT NULL         COMMENT 'Client phone / WhatsApp',
    `fecha`             DATE NOT NULL                    COMMENT 'Appointment date (YYYY-MM-DD)',
    `hora_inicio`       TIME NOT NULL                    COMMENT 'Start time (HH:MM:SS)',
    `hora_fin`          TIME NOT NULL                    COMMENT 'Calculated end time',
    `estado`            ENUM('pendiente','confirmada','en_silla','completada','cancelada','no_asistio')
                          NOT NULL DEFAULT 'pendiente'   COMMENT 'Current status of the appointment',
    `notas`             TEXT DEFAULT NULL                 COMMENT 'Additional notes from client or barber',
    `precio_total`      DECIMAL(10,2) DEFAULT 0.00       COMMENT 'Total price charged',
    `created_at`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    -- Foreign Keys (referential integrity)
    CONSTRAINT `fk_citas_barbero`  FOREIGN KEY (`barbero_id`)  REFERENCES `usuarios`(`id`)  ON DELETE CASCADE,
    CONSTRAINT `fk_citas_servicio` FOREIGN KEY (`servicio_id`) REFERENCES `servicios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --- 4. High Performance Indexes ---------------------------------
-- Optimizes most frequent queries (daily schedule, date searches)
CREATE INDEX `idx_citas_fecha`         ON `citas`(`fecha`);
CREATE INDEX `idx_citas_barbero_fecha` ON `citas`(`barbero_id`, `fecha`);
CREATE INDEX `idx_citas_estado`        ON `citas`(`estado`);

-- --- 5. Default Admin User ---------------------------------------
-- Password: 123456 (encrypted with password_hash in PHP)
-- IMPORTANT: Change this password immediately after your first login
INSERT INTO `usuarios` (`username`, `password_hash`, `nombre`, `apellido`, `rol`, `activo`)
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrador', 'General', 'admin', 1);
-- Note: The hash above corresponds to "123456" with bcrypt. Change in production.

```

---

## Important Notes for the Buyer

1. **Auto-Migration:** The file `sistema/conexion.php` acts as an auto-installer. It will silently create all tables and insert sample data (like the default admin and sample services) on its first successful run.
2. **Security:** A secure Back-Button defense and BFCache protection have been implemented natively. Navigating backward on mobile or desktop after logging in or out will safely redirect the user or destroy the session to protect sensitive administrative data.
3. **Multilingual Support:** The entire customer-facing interface, as well as the administrative panels, have been translated to English for seamless international deployment.
