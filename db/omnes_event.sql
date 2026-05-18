-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: May 18, 2026 at 11:53 AM
-- Server version: 8.4.7
-- PHP Version: 8.3.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `omnes_event`
--

-- --------------------------------------------------------

--
-- Table structure for table `associations`
--

DROP TABLE IF EXISTS `associations`;
CREATE TABLE IF NOT EXISTS `associations` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `nom` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_associations_nom` (`nom`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `associations`
--

INSERT INTO `associations` (`id`, `nom`, `created_at`) VALUES
(1, 'BDE', '2026-05-13 07:38:08'),
(2, 'BDS', '2026-05-13 07:38:08'),
(3, 'JEECE', '2026-05-13 07:38:08');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
CREATE TABLE IF NOT EXISTS `categories` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `nom` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_categories_nom` (`nom`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `nom`, `created_at`) VALUES
(1, 'Soirée', '2026-05-13 07:38:08'),
(2, 'Sport', '2026-05-13 07:38:08'),
(3, 'Culture', '2026-05-13 07:38:08');

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

DROP TABLE IF EXISTS `events`;
CREATE TABLE IF NOT EXISTS `events` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `organizer_id` bigint UNSIGNED NOT NULL,
  `association_id` bigint UNSIGNED DEFAULT NULL,
  `category_id` bigint UNSIGNED NOT NULL,
  `titre` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_event` datetime NOT NULL,
  `lieu` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `jauge_max` int UNSIGNED NOT NULL,
  `affiche_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_events_date` (`date_event`),
  KEY `idx_events_assoc` (`association_id`),
  KEY `idx_events_category` (`category_id`),
  KEY `fk_events_organizer` (`organizer_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`id`, `organizer_id`, `association_id`, `category_id`, `titre`, `description`, `date_event`, `lieu`, `jauge_max`, `affiche_path`, `created_at`, `updated_at`) VALUES
(1, 4, 2, 2, 'test', 'ceci est un test', '2026-08-19 10:30:00', '19 rue machin', 5, '726634_e7142e4b9d948553.jpg', '2026-05-15 18:20:19', NULL),
(2, 4, 1, 3, 'démo', 'ceci est une démo', '2192-05-15 01:15:00', 'AZERTYUI', 1000000, 'Screenshot-2025-09-22-191450_a18fcc1eb3fc4264.png', '2026-05-15 19:14:44', NULL),
(3, 4, 1, 1, '2 test', 'azerty', '2026-12-12 17:15:00', '19 Rue Salomon Reinach, Lyon', 1, NULL, '2026-05-16 09:00:54', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `reservations`
--

DROP TABLE IF EXISTS `reservations`;
CREATE TABLE IF NOT EXISTS `reservations` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `event_id` bigint UNSIGNED NOT NULL,
  `participant_id` bigint UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `presence_status` enum('present','absent','pending') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_reservation_event_participant` (`event_id`,`participant_id`),
  KEY `idx_reservations_event` (`event_id`),
  KEY `idx_reservations_participant` (`participant_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `reservations`
--

INSERT INTO `reservations` (`id`, `event_id`, `participant_id`, `created_at`, `presence_status`) VALUES
(1, 1, 2, '2026-05-15 18:35:42', 'pending'),
(2, 2, 4, '2026-05-15 19:15:17', 'pending'),
(3, 1, 4, '2026-05-15 20:00:50', 'pending'),
(4, 3, 4, '2026-05-16 09:00:56', 'pending');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `role` enum('admin','organisateur','participant') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'participant',
  `association_id` bigint UNSIGNED DEFAULT NULL,
  `nom` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `photo_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_organisateur_validated` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email` (`email`),
  KEY `idx_users_role` (`role`),
  KEY `idx_users_association` (`association_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `role`, `association_id`, `nom`, `email`, `password_hash`, `photo_path`, `is_organisateur_validated`, `created_at`) VALUES
(2, 'participant', NULL, 'Andine-Allard', 'samuel.allard2006@gmail.com', '$2y$10$iphaWWofpbF1o30ZKAPxFOsnrBSa/OitAeLSw3JP2sFf/vbDqjqhK', NULL, 0, '2026-05-15 14:34:12'),
(3, 'organisateur', NULL, 'Dupont', 'dupont@gmail.com', '$2y$10$YVlA79gMy8bWLVLGxY2g7.BelaCoMWswouCtM6tt/Ej8kElZkrbNS', NULL, 0, '2026-05-15 17:32:04'),
(4, 'organisateur', NULL, 'jesuis', 'je@gmail.com', '$2y$10$CeqwubsyZQoiIVVe3p0zUeRpa8mSKfioqBcakVlahH2Vj9R/i3M0u', NULL, 0, '2026-05-15 18:18:17'),
(5, 'organisateur', NULL, 'nel', 'nel@gmail.com', '$2y$10$VnjBeJVb8zT.1qDbHlF5dOXcfmSVebVWPD7h23NNRD8GVBbYKLesG', NULL, 0, '2026-05-18 11:02:36');

--
-- Constraints for dumped tables
--

--
-- Constraints for table `events`
--
ALTER TABLE `events`
  ADD CONSTRAINT `fk_events_association` FOREIGN KEY (`association_id`) REFERENCES `associations` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_events_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `fk_events_organizer` FOREIGN KEY (`organizer_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT;

--
-- Constraints for table `reservations`
--
ALTER TABLE `reservations`
  ADD CONSTRAINT `fk_reservations_event` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_reservations_participant` FOREIGN KEY (`participant_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_association` FOREIGN KEY (`association_id`) REFERENCES `associations` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
