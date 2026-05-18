
CREATE DATABASE IF NOT EXISTS omnes_event
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE omnes_event;

CREATE TABLE IF NOT EXISTS associations (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  nom VARCHAR(150) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_associations_nom (nom)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS users (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  role ENUM('admin','organisateur','participant') NOT NULL DEFAULT 'participant',
  association_id BIGINT UNSIGNED NULL,
  nom VARCHAR(100) NOT NULL,
  email VARCHAR(190) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  photo_path VARCHAR(255) NULL,
  is_organisateur_validated TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_email (email),
  KEY idx_users_role (role),
  KEY idx_users_association (association_id),
  CONSTRAINT fk_users_association
    FOREIGN KEY (association_id) REFERENCES associations(id)
    ON DELETE SET NULL
) ENGINE=InnoDB;



CREATE TABLE IF NOT EXISTS categories (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  nom VARCHAR(60) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_categories_nom (nom)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS events (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  organizer_id BIGINT UNSIGNED NOT NULL,
  association_id BIGINT UNSIGNED NULL,
  category_id BIGINT UNSIGNED NOT NULL,
  titre VARCHAR(200) NOT NULL,
  description TEXT NOT NULL,
  date_event DATETIME NOT NULL,
  lieu VARCHAR(200) NOT NULL,
  jauge_max INT UNSIGNED NOT NULL,
  affiche_path VARCHAR(255) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  KEY idx_events_date (date_event),
  KEY idx_events_assoc (association_id),
  KEY idx_events_category (category_id),
  CONSTRAINT fk_events_organizer
    FOREIGN KEY (organizer_id) REFERENCES users(id)
    ON DELETE RESTRICT,
  CONSTRAINT fk_events_association
    FOREIGN KEY (association_id) REFERENCES associations(id)
    ON DELETE SET NULL,
  CONSTRAINT fk_events_category
    FOREIGN KEY (category_id) REFERENCES categories(id)
    ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS reservations (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  event_id BIGINT UNSIGNED NOT NULL,
  participant_id BIGINT UNSIGNED NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  -- null => pas encore validé/traité, 'present'/'absent' => lecture journée J
  presence_status ENUM('present','absent','pending') NOT NULL DEFAULT 'pending',
  UNIQUE KEY uq_reservation_event_participant (event_id, participant_id),
  PRIMARY KEY (id),
  KEY idx_reservations_event (event_id),
  KEY idx_reservations_participant (participant_id),
  CONSTRAINT fk_reservations_event
    FOREIGN KEY (event_id) REFERENCES events(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_reservations_participant
    FOREIGN KEY (participant_id) REFERENCES users(id)
    ON DELETE RESTRICT
) ENGINE=InnoDB;

INSERT INTO associations (nom) VALUES
  ('BDE'),('BDS'),('JEECE')
ON DUPLICATE KEY UPDATE nom = nom;

INSERT INTO categories (nom) VALUES
  ('Soirée'),('Sport'),('Culture')
ON DUPLICATE KEY UPDATE nom = nom;

