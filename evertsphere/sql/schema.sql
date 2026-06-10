-- EthioEvents database schema

CREATE DATABASE IF NOT EXISTS ethioevents DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE ethioevents;

-- Users
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  email VARCHAR(200) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('admin','organizer','cinema_manager','user') DEFAULT 'user',
  phone VARCHAR(50) DEFAULT NULL,
  city VARCHAR(100) DEFAULT NULL,
  organization_name VARCHAR(255) DEFAULT NULL,
  organization_license VARCHAR(255) DEFAULT NULL,
  cinema_name VARCHAR(255) DEFAULT NULL,
  cinema_license VARCHAR(255) DEFAULT NULL,
  license_approved TINYINT(1) DEFAULT 0,
  is_approved TINYINT(1) DEFAULT 0,
  theme_preference VARCHAR(32) DEFAULT '',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Events
CREATE TABLE events (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  slug VARCHAR(255) NOT NULL UNIQUE,
  description TEXT,
  category VARCHAR(100) NOT NULL,
  event_date DATE NOT NULL,
  event_time TIME DEFAULT NULL,
  venue VARCHAR(255) DEFAULT NULL,
  city VARCHAR(100) DEFAULT NULL,
  poster VARCHAR(255) DEFAULT NULL,
  price DECIMAL(10,2) DEFAULT 0.00,
  capacity INT DEFAULT 0,
  organizer_id INT DEFAULT NULL,
  status ENUM('draft','published','cancelled') DEFAULT 'draft',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (organizer_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Cinemas
CREATE TABLE cinemas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  city VARCHAR(100) DEFAULT NULL,
  address VARCHAR(255) DEFAULT NULL,
  latitude DECIMAL(10,8) DEFAULT NULL,
  longitude DECIMAL(11,8) DEFAULT NULL,
  contact VARCHAR(100) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Movies / showings
CREATE TABLE movies (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  cinema_id INT NOT NULL,
  show_date DATE NOT NULL,
  show_time TIME NOT NULL,
  genre VARCHAR(100) DEFAULT NULL,
  poster VARCHAR(255) DEFAULT NULL,
  rating DECIMAL(3,1) DEFAULT NULL,
  imdb_url VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (cinema_id) REFERENCES cinemas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Bookings
CREATE TABLE bookings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  event_id INT DEFAULT NULL,
  movie_id INT DEFAULT NULL,
  tickets_qty INT DEFAULT 1,
  total_amount DECIMAL(10,2) DEFAULT 0.00,
  booking_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  status ENUM('reserved','confirmed','cancelled') DEFAULT 'reserved',
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE SET NULL,
  FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seats (simple implementation)
CREATE TABLE seats (
  id INT AUTO_INCREMENT PRIMARY KEY,
  movie_id INT NOT NULL,
  seat_label VARCHAR(20) NOT NULL,
  status ENUM('available','booked') DEFAULT 'available',
  price DECIMAL(8,2) DEFAULT 0.00,
  FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Admin settings
CREATE TABLE admin_settings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  meta_key VARCHAR(100) NOT NULL UNIQUE,
  meta_value TEXT,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Email logs
CREATE TABLE IF NOT EXISTS email_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  recipient VARCHAR(255),
  subject VARCHAR(255),
  body MEDIUMTEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Indexes for searches
CREATE INDEX idx_events_city_date ON events(city, event_date);
CREATE INDEX idx_movies_cinema_date ON movies(cinema_id, show_date);

-- Additional optional tables introduced by migrations

-- cinema_managers mapping (allows multiple cinemas per manager)
CREATE TABLE IF NOT EXISTS cinema_managers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  cinema_id INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY ux_user_cinema (user_id, cinema_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (cinema_id) REFERENCES cinemas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Support tickets & refunds
CREATE TABLE IF NOT EXISTS support_tickets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  subject VARCHAR(255) NOT NULL,
  message TEXT NOT NULL,
  status ENUM('open','in-progress','resolved','closed') DEFAULT 'open',
  priority ENUM('low','medium','high','urgent') DEFAULT 'medium',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS refunds (
  id INT AUTO_INCREMENT PRIMARY KEY,
  booking_id INT NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  reason VARCHAR(255),
  status ENUM('pending','approved','rejected','processed') DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS venues (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  city VARCHAR(100) NOT NULL,
  capacity INT DEFAULT 0,
  contact VARCHAR(100),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS cities (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL UNIQUE,
  region VARCHAR(100),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Ensure users table has the additional columns added by migrations
ALTER TABLE users
  ADD COLUMN IF NOT EXISTS organization_name VARCHAR(255) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS organization_license VARCHAR(255) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS cinema_name VARCHAR(255) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS cinema_license VARCHAR(255) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS license_approved TINYINT(1) NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS is_approved TINYINT(1) NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS theme_preference VARCHAR(32) DEFAULT '';

-- Ensure movies table has rating and imdb_url
ALTER TABLE movies
  ADD COLUMN IF NOT EXISTS rating DECIMAL(3,1) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS imdb_url VARCHAR(255) DEFAULT NULL;

-- Optional indexes and helper entries
CREATE INDEX IF NOT EXISTS idx_events_city_date ON events(city, event_date);
CREATE INDEX IF NOT EXISTS idx_movies_cinema_date ON movies(cinema_id, show_date);

COMMIT;
