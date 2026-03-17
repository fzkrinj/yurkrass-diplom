-- Схема БД для проекта «Юркрас»
-- База данных: yurkrass_db

CREATE DATABASE IF NOT EXISTS yurkrass_db
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE yurkrass_db;

-- Таблица пользователей

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  role ENUM('client', 'consultant', 'admin') NOT NULL DEFAULT 'client',
  full_name VARCHAR(255) NOT NULL,
  birth_date DATE NULL,
  phone VARCHAR(50) NULL,
  email VARCHAR(255) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Таблица услуг

CREATE TABLE IF NOT EXISTS services (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  description TEXT NULL,
  category VARCHAR(100) NULL,
  price_from DECIMAL(10,2) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Таблица заявок на консультацию

CREATE TABLE IF NOT EXISTS consultation_requests (
  id INT AUTO_INCREMENT PRIMARY KEY,
  client_id INT NULL,
  name VARCHAR(255) NULL,
  phone VARCHAR(50) NULL,
  email VARCHAR(255) NULL,
  service_id INT NULL,
  preferred_time VARCHAR(255) NULL,
  status ENUM('new', 'in_progress', 'completed', 'cancelled') NOT NULL DEFAULT 'new',
  comment TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_consultation_client
    FOREIGN KEY (client_id) REFERENCES users(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_consultation_service
    FOREIGN KEY (service_id) REFERENCES services(id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Таблица сессий чата

CREATE TABLE IF NOT EXISTS chat_sessions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  client_id INT NULL,
  status ENUM('bot', 'waiting_for_consultant', 'consultant_connected', 'closed') NOT NULL DEFAULT 'bot',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  closed_at DATETIME NULL,
  CONSTRAINT fk_chat_session_client
    FOREIGN KEY (client_id) REFERENCES users(id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Таблица сообщений чата

CREATE TABLE IF NOT EXISTS chat_messages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  session_id INT NOT NULL,
  sender_type ENUM('client', 'bot', 'consultant') NOT NULL,
  sender_id INT NULL,
  message_text TEXT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_chat_message_session
    FOREIGN KEY (session_id) REFERENCES chat_sessions(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_chat_message_sender
    FOREIGN KEY (sender_id) REFERENCES users(id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Таблица базы знаний чат-бота

CREATE TABLE IF NOT EXISTS bot_knowledge (
  id INT AUTO_INCREMENT PRIMARY KEY,
  question_pattern VARCHAR(255) NOT NULL,
  answer_text TEXT NOT NULL,
  related_service_id INT NULL,
  priority INT NOT NULL DEFAULT 0,
  CONSTRAINT fk_bot_knowledge_service
    FOREIGN KEY (related_service_id) REFERENCES services(id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

