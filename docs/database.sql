-- PiatMove Database Schema
-- Run this in phpMyAdmin after creating the `piatmove` database

CREATE DATABASE IF NOT EXISTS piatmove CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE piatmove;

-- --------------------------------------------------------
-- Table: users
-- Stores both passengers and drivers; role field differentiates them
-- --------------------------------------------------------
CREATE TABLE users (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100)  NOT NULL,
    email       VARCHAR(150)  NOT NULL UNIQUE,
    password    VARCHAR(255)  NOT NULL,
    phone       VARCHAR(20)   NOT NULL,
    role        ENUM('passenger', 'driver') NOT NULL DEFAULT 'passenger',
    status      ENUM('active', 'inactive')  NOT NULL DEFAULT 'active',
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- Table: driver_info
-- Extended profile for drivers; approval_status controls access
-- --------------------------------------------------------
CREATE TABLE driver_info (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id          INT UNSIGNED NOT NULL UNIQUE,
    license_no       VARCHAR(50)  NOT NULL,
    vehicle_no       VARCHAR(50)  NOT NULL,
    vehicle_type     VARCHAR(50)  NOT NULL,
    approval_status  ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    is_online        TINYINT(1)   NOT NULL DEFAULT 0,
    current_lat      DECIMAL(10, 7) NULL,
    current_lng      DECIMAL(10, 7) NULL,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_driver_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- Table: bookings
-- Full ride lifecycle from request to completion/cancellation
-- --------------------------------------------------------
CREATE TABLE bookings (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    passenger_id     INT UNSIGNED NOT NULL,
    driver_id        INT UNSIGNED NULL,
    pickup_address   VARCHAR(255) NOT NULL,
    pickup_lat       DECIMAL(10, 7) NOT NULL,
    pickup_lng       DECIMAL(10, 7) NOT NULL,
    dropoff_address  VARCHAR(255) NOT NULL,
    dropoff_lat      DECIMAL(10, 7) NOT NULL,
    dropoff_lng      DECIMAL(10, 7) NOT NULL,
    status           ENUM('pending', 'accepted', 'rejected', 'started', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
    fare             DECIMAL(10, 2) NULL,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_booking_passenger FOREIGN KEY (passenger_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_booking_driver    FOREIGN KEY (driver_id)    REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- Table: fcm_tokens
-- Firebase push notification tokens; one active token per user
-- --------------------------------------------------------
CREATE TABLE fcm_tokens (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NOT NULL UNIQUE,
    token       TEXT         NOT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_fcm_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- Table: admins
-- Separate accounts for the web admin panel (no JWT, uses sessions)
-- --------------------------------------------------------
CREATE TABLE admins (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,
    email       VARCHAR(150) NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- Indexes for common query patterns
-- --------------------------------------------------------
CREATE INDEX idx_bookings_passenger ON bookings(passenger_id);
CREATE INDEX idx_bookings_driver    ON bookings(driver_id);
CREATE INDEX idx_bookings_status    ON bookings(status);
CREATE INDEX idx_users_role         ON users(role);
CREATE INDEX idx_users_status       ON users(status);
