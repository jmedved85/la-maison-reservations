-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: mysql:3306
-- Generation Time: Mar 25, 2026 at 06:59 PM
-- Server version: 8.0.39
-- PHP Version: 8.3.30

SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `la_maison_reservations_test`
--

-- --------------------------------------------------------

--
-- Table structure for table `doctrine_migration_versions`
--

CREATE TABLE IF NOT EXISTS `doctrine_migration_versions` (
  `version` varchar(191) COLLATE utf8mb3_unicode_ci NOT NULL,
  `executed_at` datetime DEFAULT NULL,
  `execution_time` int DEFAULT NULL,
  PRIMARY KEY (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Dumping data for table `doctrine_migration_versions`
--

INSERT INTO `doctrine_migration_versions` (`version`, `executed_at`, `execution_time`) VALUES
('DoctrineMigrations\\Version20241122143523', '2026-03-18 16:38:44', 53),
('DoctrineMigrations\\Version20260316202308', '2026-03-18 16:38:44', 50),
('DoctrineMigrations\\Version20260317210416', '2026-03-18 16:38:44', 33),
('DoctrineMigrations\\Version20260318174419', '2026-03-18 17:45:47', 152);

-- --------------------------------------------------------

--
-- Table structure for table `messenger_messages`
--

CREATE TABLE IF NOT EXISTS `messenger_messages` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `body` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `headers` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue_name` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  `available_at` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  `delivered_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  PRIMARY KEY (`id`),
  KEY `IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750` (`queue_name`,`available_at`,`delivered_at`,`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reservation`
--

CREATE TABLE IF NOT EXISTS `reservation` (
  `id` int NOT NULL AUTO_INCREMENT,
  `reference_code` varchar(8) COLLATE utf8mb4_unicode_ci NOT NULL,
  `full_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reservation_date` date NOT NULL COMMENT '(DC2Type:date_immutable)',
  `time_slot` time NOT NULL COMMENT '(DC2Type:time_immutable)',
  `party_size` int NOT NULL,
  `special_requests` longtext COLLATE utf8mb4_unicode_ci,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reservation_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  `updated_at` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  `deleted_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_REFERENCE_CODE` (`reference_code`),
  KEY `IDX_RESERVATION_DATE` (`reservation_date`),
  KEY `IDX_STATUS` (`status`),
  KEY `IDX_DATE_TIME` (`reservation_date`,`time_slot`)
) ENGINE=InnoDB AUTO_INCREMENT=48 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------

--
-- Table structure for table `time_slot`
--

CREATE TABLE IF NOT EXISTS `time_slot` (
  `id` int NOT NULL AUTO_INCREMENT,
  `time` time NOT NULL COMMENT '(DC2Type:time_immutable)',
  `slot_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `min_capacity` int NOT NULL,
  `max_capacity` int NOT NULL,
  `description` longtext COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_TIME` (`time`),
  KEY `IDX_SLOT_TYPE` (`slot_type`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `time_slot`
--

INSERT INTO `time_slot` (`id`, `time`, `slot_type`, `min_capacity`, `max_capacity`, `description`, `is_active`) VALUES
(1, '12:00:00', 'regular', 1, 20, 'Regular dining slot', 1),
(2, '12:30:00', 'regular', 1, 20, 'Regular dining slot', 1),
(3, '13:00:00', 'regular', 1, 20, 'Regular dining slot', 1),
(4, '13:30:00', 'regular', 1, 20, 'Regular dining slot', 1),
(5, '14:00:00', 'regular', 1, 20, 'Regular dining slot', 1),
(6, '14:30:00', 'regular', 1, 20, 'Regular dining slot', 1),
(7, '15:00:00', 'regular', 1, 20, 'Regular dining slot', 1),
(8, '15:30:00', 'regular', 1, 20, 'Regular dining slot', 1),
(9, '16:00:00', 'regular', 1, 20, 'Regular dining slot', 1),
(10, '16:30:00', 'regular', 1, 20, 'Regular dining slot', 1),
(11, '17:00:00', 'regular', 1, 20, 'Regular dining slot', 1),
(12, '17:30:00', 'regular', 1, 20, 'Regular dining slot', 1),
(13, '18:00:00', 'regular', 1, 20, 'Regular dining slot', 1),
(14, '18:30:00', 'regular', 1, 20, 'Regular dining slot', 1),
(15, '19:00:00', 'regular', 1, 20, 'Regular dining slot', 1),
(16, '19:30:00', 'regular', 1, 20, 'Regular dining slot', 1),
(17, '20:00:00', 'regular', 1, 20, 'Regular dining slot', 1),
(18, '20:30:00', 'regular', 1, 20, 'Regular dining slot', 1),
(19, '21:00:00', 'regular', 1, 20, 'Regular dining slot', 1),
(20, '18:00:00', 'private_dining', 6, 12, 'Private dining room slot (Friday/Saturday only)', 1),
(21, '18:30:00', 'private_dining', 6, 12, 'Private dining room slot (Friday/Saturday only)', 1),
(22, '19:00:00', 'private_dining', 6, 12, 'Private dining room slot (Friday/Saturday only)', 1),
(23, '19:30:00', 'private_dining', 6, 12, 'Private dining room slot (Friday/Saturday only)', 1),
(24, '20:00:00', 'private_dining', 6, 12, 'Private dining room slot (Friday/Saturday only)', 1),
(25, '20:30:00', 'private_dining', 6, 12, 'Private dining room slot (Friday/Saturday only)', 1),
(26, '21:00:00', 'private_dining', 6, 12, 'Private dining room slot (Friday/Saturday only)', 1);

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE IF NOT EXISTS `user` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email` varchar(180) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `roles` json DEFAULT NULL,
  `first_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `active` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_IDENTIFIER_EMAIL` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SET FOREIGN_KEY_CHECKS=1;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
