-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 08, 2026 at 02:46 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `coupony`
--

-- --------------------------------------------------------

--
-- Table structure for table `addressables`
--

CREATE TABLE `addressables` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `address_id` bigint(20) UNSIGNED NOT NULL,
  `owner_type` varchar(255) NOT NULL,
  `owner_id` char(36) NOT NULL,
  `label` varchar(50) NOT NULL DEFAULT 'home',
  `is_default_shipping` tinyint(1) NOT NULL DEFAULT 0,
  `is_default_billing` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `addressables`
--

INSERT INTO `addressables` (`id`, `address_id`, `owner_type`, `owner_id`, `label`, `is_default_shipping`, `is_default_billing`, `created_at`, `updated_at`) VALUES
(7, 5, 'App\\Domain\\Store\\Models\\Store', 'bed4702c-de6b-4dbd-a9fc-663f405e09f7', 'home', 0, 0, '2026-02-08 00:14:45', '2026-02-08 00:14:45'),
(8, 6, 'App\\Domain\\Store\\Models\\Store', 'a382425d-15bb-4e35-9988-5a8f4f99fe72', 'home', 0, 0, '2026-02-08 00:17:55', '2026-02-08 00:17:55'),
(9, 7, 'App\\Domain\\Store\\Models\\Store', '6df8be66-bac3-4b9a-b51f-7c3c23fb8481', 'home', 0, 0, '2026-02-08 00:25:06', '2026-02-08 00:25:06'),
(10, 8, 'App\\Domain\\Store\\Models\\Store', '61df8a7d-2493-4911-b3ec-8f9d9fcb6e69', 'home', 0, 0, '2026-02-08 00:26:20', '2026-02-08 00:26:20'),
(11, 9, 'App\\Domain\\Store\\Models\\Store', '8083ad58-00c2-49f0-b282-7dcd39beb107', 'home', 0, 0, '2026-02-08 00:37:52', '2026-02-08 00:37:52'),
(12, 10, 'App\\Domain\\Store\\Models\\Store', 'da952527-786d-4073-b8b2-753b64dcc99b', 'home', 0, 0, '2026-02-08 00:38:20', '2026-02-08 00:38:20'),
(13, 11, 'App\\Domain\\Store\\Models\\Store', '219f77bc-bb40-4121-9959-df510c83f90a', 'home', 0, 0, '2026-02-08 00:38:58', '2026-02-08 00:38:58'),
(14, 12, 'App\\Domain\\Store\\Models\\Store', '2c45144b-9bcd-4b01-ace8-9133683cbf34', 'home', 0, 0, '2026-02-08 00:39:45', '2026-02-08 00:39:45'),
(15, 13, 'App\\Domain\\Store\\Models\\Store', '62984122-93ab-45df-bcb5-3a32258ab511', 'home', 0, 0, '2026-02-08 00:40:37', '2026-02-08 00:40:37'),
(16, 14, 'App\\Domain\\Store\\Models\\Store', 'd5b9c837-3f55-4ba1-9b9a-94e4f146ef42', 'home', 0, 0, '2026-02-08 00:41:00', '2026-02-08 00:41:00'),
(17, 15, 'App\\Domain\\Store\\Models\\Store', '7f239fef-820c-4d1b-8e13-f778e50568e5', 'home', 0, 0, '2026-02-08 00:45:38', '2026-02-08 00:45:38'),
(18, 16, 'App\\Domain\\Store\\Models\\Store', 'f3d9eca3-c7a3-4571-82d3-b76cf1d1f1d9', 'home', 0, 0, '2026-02-08 00:47:24', '2026-02-08 00:47:24'),
(19, 5, 'App\\Domain\\Store\\Models\\Store', '5e7ae24d-fe0c-4829-b893-4eff6ecb2791', 'branch', 0, 0, '2026-02-08 00:52:50', '2026-02-08 00:52:50'),
(20, 5, 'App\\Domain\\Store\\Models\\Store', '6881dd0d-87d1-4a19-b8b8-f6e02e7aec40', 'branch', 0, 0, '2026-02-08 01:02:23', '2026-02-08 01:02:23'),
(21, 5, 'App\\Domain\\Store\\Models\\Store', 'a9cea373-3add-4870-8d20-396244f0c458', 'branch', 0, 0, '2026-02-08 01:02:52', '2026-02-08 01:02:52'),
(22, 5, 'App\\Domain\\Store\\Models\\Store', 'f81b7249-08f9-430f-b06d-f54b5f3ebe4e', 'branch', 0, 0, '2026-02-08 01:04:38', '2026-02-08 01:04:38'),
(23, 5, 'App\\Domain\\Store\\Models\\Store', '55dbc156-4082-46dc-90cd-b543b4f18418', 'branch', 0, 0, '2026-02-08 01:06:59', '2026-02-08 01:06:59'),
(24, 5, 'App\\Domain\\Store\\Models\\Store', 'ecf29eb0-81ce-4a21-8174-15ce00d5d5b0', 'branch', 0, 0, '2026-02-08 01:10:43', '2026-02-08 01:10:43'),
(25, 5, 'App\\Domain\\Store\\Models\\Store', '74824326-6c95-4ff4-83c0-8ac729d97817', 'branch', 0, 0, '2026-02-08 01:16:00', '2026-02-08 01:16:00'),
(26, 5, 'App\\Domain\\Store\\Models\\Store', '39b79652-beef-4877-8bd2-058eab653f6f', 'branch', 0, 0, '2026-02-08 01:16:11', '2026-02-08 01:16:11'),
(27, 5, 'App\\Domain\\Store\\Models\\Store', 'ad61c349-7c87-42ec-9ab4-423f238fad87', 'branch', 0, 0, '2026-02-08 01:19:24', '2026-02-08 01:19:24'),
(28, 5, 'App\\Domain\\Store\\Models\\Store', '96d9c4fa-a5cb-424e-85dc-506842dc2979', 'branch', 0, 0, '2026-02-08 01:19:46', '2026-02-08 01:19:46'),
(29, 5, 'App\\Domain\\Store\\Models\\Store', '06e975df-9320-4b1a-af53-b5c8f6373b39', 'branch', 0, 0, '2026-02-08 01:20:45', '2026-02-08 01:20:45'),
(30, 5, 'App\\Domain\\Store\\Models\\Store', '468070bb-5b1b-49bf-8e43-2265c4be08ef', 'branch', 0, 0, '2026-02-08 01:21:59', '2026-02-08 01:21:59'),
(31, 5, 'App\\Domain\\Store\\Models\\Store', '85093754-da13-4d21-b53d-f76226b83e5e', 'branch', 0, 0, '2026-02-08 01:22:10', '2026-02-08 01:22:10'),
(32, 5, 'App\\Domain\\Store\\Models\\Store', '38dc83c7-7eed-4fe8-86ba-cab830e47b7f', 'branch', 0, 0, '2026-02-08 01:25:03', '2026-02-08 01:25:03'),
(33, 18, 'App\\Domain\\Store\\Models\\Store', '67d978be-f571-4ecf-b939-997daa4c16a6', 'branch', 0, 0, '2026-02-08 01:26:14', '2026-02-08 01:26:14'),
(34, 19, 'App\\Domain\\Store\\Models\\Store', 'eaf79b81-447a-4a7a-8a08-f26571f298b1', 'branch', 0, 0, '2026-02-08 01:28:08', '2026-02-08 01:28:08'),
(35, 20, 'App\\Domain\\Store\\Models\\Store', 'f8886dc8-36c1-4674-8aa3-cdf998cf32ce', 'branch', 0, 0, '2026-02-08 01:28:53', '2026-02-08 01:28:53'),
(36, 21, 'App\\Domain\\Store\\Models\\Store', 'bd5f6b10-1640-4fe0-98a3-00f2eebf48f6', 'branch', 0, 0, '2026-02-08 01:29:07', '2026-02-08 01:29:07'),
(37, 22, 'App\\Domain\\Store\\Models\\Store', 'fe50f29d-ddb5-4e6f-80e3-47cb34893a07', 'branch', 0, 0, '2026-02-08 01:29:12', '2026-02-08 01:29:12'),
(38, 23, 'App\\Domain\\Store\\Models\\Store', '99cc4e46-477f-49ae-8716-ec4ac94a83f3', 'branch', 0, 0, '2026-02-08 01:29:13', '2026-02-08 01:29:13'),
(39, 24, 'App\\Domain\\Store\\Models\\Store', '1209d5a1-5ffa-4f77-b073-99f7098ae404', 'branch', 0, 0, '2026-02-08 01:30:11', '2026-02-08 01:30:11'),
(40, 25, 'App\\Domain\\Store\\Models\\Store', '1d26abd4-7ab2-49e4-9feb-decb8767a1d9', 'branch', 0, 0, '2026-02-08 01:30:41', '2026-02-08 01:30:41'),
(41, 26, 'App\\Domain\\Store\\Models\\Store', '76428452-698b-4516-80ff-1f8223faf4e2', 'branch', 0, 0, '2026-02-08 01:31:23', '2026-02-08 01:31:23'),
(42, 27, 'App\\Domain\\Store\\Models\\Store', 'a9abfce0-b6b0-41d0-be5d-8ce27662bace', 'branch', 0, 0, '2026-02-08 01:31:46', '2026-02-08 01:31:46'),
(43, 28, 'App\\Domain\\Store\\Models\\Store', 'eb379eb9-994b-466e-95fb-ce9344b37311', 'branch', 0, 0, '2026-02-08 01:33:12', '2026-02-08 01:33:12'),
(44, 29, 'App\\Domain\\Store\\Models\\Store', 'e608bf43-4e36-4823-8fda-b46399ad160f', 'branch', 0, 0, '2026-02-08 01:33:32', '2026-02-08 01:33:32'),
(45, 30, 'App\\Domain\\Store\\Models\\Store', 'ee205576-2965-4202-b8a6-5f3f43c8e31a', 'branch', 0, 0, '2026-02-08 01:33:52', '2026-02-08 01:33:52'),
(46, 31, 'App\\Domain\\Store\\Models\\Store', '8820cda8-50d1-4617-b741-f91ae252d253', 'branch', 0, 0, '2026-02-08 01:33:58', '2026-02-08 01:33:58'),
(47, 32, 'App\\Domain\\Store\\Models\\Store', '22507230-3892-4e78-9681-81c0ec94d7d0', 'branch', 0, 0, '2026-02-08 01:34:13', '2026-02-08 01:34:13');

-- --------------------------------------------------------

--
-- Table structure for table `addresses`
--

CREATE TABLE `addresses` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `company` varchar(255) DEFAULT NULL,
  `address_line1` varchar(255) NOT NULL,
  `address_line2` varchar(255) DEFAULT NULL,
  `city` varchar(100) NOT NULL,
  `state_province` varchar(100) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `country_code` char(2) DEFAULT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `delivery_instructions` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `addresses`
--

INSERT INTO `addresses` (`id`, `first_name`, `last_name`, `company`, `address_line1`, `address_line2`, `city`, `state_province`, `postal_code`, `country_code`, `phone_number`, `latitude`, `longitude`, `delivery_instructions`, `created_at`, `updated_at`) VALUES
(5, NULL, NULL, NULL, 'gamal street', NULL, 'fayoum', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-08 00:14:45', '2026-02-08 00:14:45'),
(6, NULL, NULL, NULL, 'gamal street', NULL, 'fayoum', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-08 00:17:55', '2026-02-08 00:17:55'),
(7, NULL, NULL, NULL, 'gamal street', NULL, 'fayoum', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-08 00:25:06', '2026-02-08 00:25:06'),
(8, NULL, NULL, NULL, 'gamal street', NULL, 'fayoum', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-08 00:26:20', '2026-02-08 00:26:20'),
(9, NULL, NULL, NULL, 'gamal street', NULL, 'fayoum', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-08 00:37:52', '2026-02-08 00:37:52'),
(10, NULL, NULL, NULL, 'gamal street', NULL, 'fayoum', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-08 00:38:20', '2026-02-08 00:38:20'),
(11, NULL, NULL, NULL, 'gamal street', NULL, 'fayoum', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-08 00:38:58', '2026-02-08 00:38:58'),
(12, NULL, NULL, NULL, 'gamal street', NULL, 'fayoum', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-08 00:39:45', '2026-02-08 00:39:45'),
(13, NULL, NULL, NULL, 'gamal street', NULL, 'fayoum', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-08 00:40:37', '2026-02-08 00:40:37'),
(14, NULL, NULL, NULL, 'gamal street', NULL, 'fayoum', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-08 00:41:00', '2026-02-08 00:41:00'),
(15, NULL, NULL, NULL, 'gamal street', NULL, 'fayoum', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-08 00:45:38', '2026-02-08 00:45:38'),
(16, NULL, NULL, NULL, 'gamal street', NULL, 'fayoum', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-08 00:47:24', '2026-02-08 00:47:24'),
(18, NULL, NULL, NULL, 'gamal street', NULL, 'fayoum', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-08 01:26:14', '2026-02-08 01:26:14'),
(19, NULL, NULL, NULL, 'gamal street', NULL, 'fayoum', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-08 01:28:08', '2026-02-08 01:28:08'),
(20, NULL, NULL, NULL, 'gamal street', NULL, 'fayoum', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-08 01:28:53', '2026-02-08 01:28:53'),
(21, NULL, NULL, NULL, 'gamal street', NULL, 'fayoum', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-08 01:29:07', '2026-02-08 01:29:07'),
(22, NULL, NULL, NULL, 'gamal street', NULL, 'fayoum', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-08 01:29:12', '2026-02-08 01:29:12'),
(23, NULL, NULL, NULL, 'gamal street', NULL, 'fayoum', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-08 01:29:13', '2026-02-08 01:29:13'),
(24, NULL, NULL, NULL, 'gamal street', NULL, 'fayoum', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-08 01:30:11', '2026-02-08 01:30:11'),
(25, NULL, NULL, NULL, 'gamal street', NULL, 'fayoum', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-08 01:30:41', '2026-02-08 01:30:41'),
(26, NULL, NULL, NULL, 'gamal street', NULL, 'fayoum', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-08 01:31:23', '2026-02-08 01:31:23'),
(27, NULL, NULL, NULL, 'gamal street', NULL, 'fayoum', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-08 01:31:46', '2026-02-08 01:31:46'),
(28, NULL, NULL, NULL, 'gamal street', NULL, 'fayoum', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-08 01:33:12', '2026-02-08 01:33:12'),
(29, NULL, NULL, NULL, 'gamal street', NULL, 'fayoum', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-08 01:33:32', '2026-02-08 01:33:32'),
(30, NULL, NULL, NULL, 'gamal street', NULL, 'fayoum', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-08 01:33:52', '2026-02-08 01:33:52'),
(31, NULL, NULL, NULL, 'gamal street', NULL, 'fayoum', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-08 01:33:58', '2026-02-08 01:33:58'),
(32, NULL, NULL, NULL, 'gamal street', NULL, 'fayoum', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-08 01:34:13', '2026-02-08 01:34:13');

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `contact_us_customer`
--

CREATE TABLE `contact_us_customer` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `contact_us_customer`
--

INSERT INTO `contact_us_customer` (`id`, `name`, `email`, `subject`, `message`, `created_at`, `updated_at`) VALUES
(1, 'Ahmed Mostafa', 'email@gmail.com', 'my subject', 'my message', '2026-02-06 17:45:43', '2026-02-06 17:45:43'),
(2, 'Ahmed Mostafa', 'email@gmail.com', 'my subject', 'my message', '2026-02-07 16:25:58', '2026-02-07 16:25:58'),
(3, 'Ahmed Mostafa', 'email@gmail.com', 'my subject', 'my message', '2026-02-07 16:30:59', '2026-02-07 16:30:59');

-- --------------------------------------------------------

--
-- Table structure for table `contact_us_seller`
--

CREATE TABLE `contact_us_seller` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `store_name` varchar(255) NOT NULL,
  `phone_number` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `contact_us_seller`
--

INSERT INTO `contact_us_seller` (`id`, `store_name`, `phone_number`, `created_at`, `updated_at`) VALUES
(1, 'HM', '01025250321', '2026-02-06 17:46:22', '2026-02-06 17:46:22');

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `failed_jobs`
--

INSERT INTO `failed_jobs` (`id`, `uuid`, `connection`, `queue`, `payload`, `exception`, `failed_at`) VALUES
(1, '679e8900-17aa-4dd8-bf50-b19490aee545', 'database', 'default', '{\"uuid\":\"679e8900-17aa-4dd8-bf50-b19490aee545\",\"displayName\":\"App\\\\Domain\\\\Notification\\\\Events\\\\NotificationSent\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Broadcasting\\\\BroadcastEvent\",\"command\":\"O:38:\\\"Illuminate\\\\Broadcasting\\\\BroadcastEvent\\\":17:{s:5:\\\"event\\\";O:47:\\\"App\\\\Domain\\\\Notification\\\\Events\\\\NotificationSent\\\":2:{s:12:\\\"notification\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:43:\\\"App\\\\Domain\\\\Notification\\\\Models\\\\Notification\\\";s:2:\\\"id\\\";i:1;s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:4:\\\"user\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:27:\\\"App\\\\Domain\\\\User\\\\Models\\\\User\\\";s:2:\\\"id\\\";s:36:\\\"c69077f2-4064-4b7d-85f7-836d04afa7a3\\\";s:9:\\\"relations\\\";a:1:{i:0;s:7:\\\"profile\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}}s:5:\\\"tries\\\";N;s:7:\\\"timeout\\\";N;s:7:\\\"backoff\\\";N;s:13:\\\"maxExceptions\\\";N;s:23:\\\"deleteWhenMissingModels\\\";b:1;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:12:\\\"messageGroup\\\";N;s:12:\\\"deduplicator\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;}\"},\"createdAt\":1770482540,\"telescope_uuid\":\"a106424a-80ed-45bf-8308-8b6bc913f30e\",\"delay\":null}', 'Illuminate\\Broadcasting\\BroadcastException: Pusher error: <!DOCTYPE HTML PUBLIC \"-//IETF//DTD HTML 2.0//EN\">\n<html><head>\n<title>404 Not Found</title>\n</head><body>\n<h1>Not Found</h1>\n<p>The requested URL was not found on this server.</p>\n<hr>\n<address>Apache/2.4.58 (Win64) OpenSSL/3.1.3 PHP/8.2.12 Server at localhost Port 8080</address>\n</body></html>\n. in E:\\coupony-backend\\vendor\\laravel\\framework\\src\\Illuminate\\Broadcasting\\Broadcasters\\PusherBroadcaster.php:163\nStack trace:\n#0 E:\\coupony-backend\\vendor\\laravel\\framework\\src\\Illuminate\\Broadcasting\\BroadcastEvent.php(100): Illuminate\\Broadcasting\\Broadcasters\\PusherBroadcaster->broadcast(Object(Illuminate\\Support\\Collection), \'notification.se...\', Array)\n#1 E:\\coupony-backend\\vendor\\laravel\\framework\\src\\Illuminate\\Container\\BoundMethod.php(36): Illuminate\\Broadcasting\\BroadcastEvent->handle(Object(Illuminate\\Broadcasting\\BroadcastManager))\n#2 E:\\coupony-backend\\vendor\\laravel\\framework\\src\\Illuminate\\Container\\Util.php(43): Illuminate\\Container\\BoundMethod::Illuminate\\Container\\{closure}()\n#3 E:\\coupony-backend\\vendor\\laravel\\framework\\src\\Illuminate\\Container\\BoundMethod.php(96): Illuminate\\Container\\Util::unwrapIfClosure(Object(Closure))\n#4 E:\\coupony-backend\\vendor\\laravel\\framework\\src\\Illuminate\\Container\\BoundMethod.php(35): Illuminate\\Container\\BoundMethod::callBoundMethod(Object(Illuminate\\Foundation\\Application), Array, Object(Closure))\n#5 E:\\coupony-backend\\vendor\\laravel\\framework\\src\\Illuminate\\Container\\Container.php(799): Illuminate\\Container\\BoundMethod::call(Object(Illuminate\\Foundation\\Application), Array, Array, NULL)\n#6 E:\\coupony-backend\\vendor\\laravel\\framework\\src\\Illuminate\\Bus\\Dispatcher.php(129): Illuminate\\Container\\Container->call(Array)\n#7 E:\\coupony-backend\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php(180): Illuminate\\Bus\\Dispatcher->Illuminate\\Bus\\{closure}(Object(Illuminate\\Broadcasting\\BroadcastEvent))\n#8 E:\\coupony-backend\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php(137): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Broadcasting\\BroadcastEvent))\n#9 E:\\coupony-backend\\vendor\\laravel\\framework\\src\\Illuminate\\Bus\\Dispatcher.php(133): Illuminate\\Pipeline\\Pipeline->then(Object(Closure))\n#10 E:\\coupony-backend\\vendor\\laravel\\framework\\src\\Illuminate\\Queue\\CallQueuedHandler.php(134): Illuminate\\Bus\\Dispatcher->dispatchNow(Object(Illuminate\\Broadcasting\\BroadcastEvent), false)\n#11 E:\\coupony-backend\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php(180): Illuminate\\Queue\\CallQueuedHandler->Illuminate\\Queue\\{closure}(Object(Illuminate\\Broadcasting\\BroadcastEvent))\n#12 E:\\coupony-backend\\vendor\\laravel\\framework\\src\\Illuminate\\Pipeline\\Pipeline.php(137): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}(Object(Illuminate\\Broadcasting\\BroadcastEvent))\n#13 E:\\coupony-backend\\vendor\\laravel\\framework\\src\\Illuminate\\Queue\\CallQueuedHandler.php(127): Illuminate\\Pipeline\\Pipeline->then(Object(Closure))\n#14 E:\\coupony-backend\\vendor\\laravel\\framework\\src\\Illuminate\\Queue\\CallQueuedHandler.php(68): Illuminate\\Queue\\CallQueuedHandler->dispatchThroughMiddleware(Object(Illuminate\\Queue\\Jobs\\DatabaseJob), Object(Illuminate\\Broadcasting\\BroadcastEvent))\n#15 E:\\coupony-backend\\vendor\\laravel\\framework\\src\\Illuminate\\Queue\\Jobs\\Job.php(102): Illuminate\\Queue\\CallQueuedHandler->call(Object(Illuminate\\Queue\\Jobs\\DatabaseJob), Array)\n#16 E:\\coupony-backend\\vendor\\laravel\\framework\\src\\Illuminate\\Queue\\Worker.php(485): Illuminate\\Queue\\Jobs\\Job->fire()\n#17 E:\\coupony-backend\\vendor\\laravel\\framework\\src\\Illuminate\\Queue\\Worker.php(435): Illuminate\\Queue\\Worker->process(\'database\', Object(Illuminate\\Queue\\Jobs\\DatabaseJob), Object(Illuminate\\Queue\\WorkerOptions))\n#18 E:\\coupony-backend\\vendor\\laravel\\framework\\src\\Illuminate\\Queue\\Worker.php(201): Illuminate\\Queue\\Worker->runJob(Object(Illuminate\\Queue\\Jobs\\DatabaseJob), \'database\', Object(Illuminate\\Queue\\WorkerOptions))\n#19 E:\\coupony-backend\\vendor\\laravel\\framework\\src\\Illuminate\\Queue\\Console\\WorkCommand.php(148): Illuminate\\Queue\\Worker->daemon(\'database\', \'default\', Object(Illuminate\\Queue\\WorkerOptions))\n#20 E:\\coupony-backend\\vendor\\laravel\\framework\\src\\Illuminate\\Queue\\Console\\WorkCommand.php(131): Illuminate\\Queue\\Console\\WorkCommand->runWorker(\'database\', \'default\')\n#21 E:\\coupony-backend\\vendor\\laravel\\framework\\src\\Illuminate\\Container\\BoundMethod.php(36): Illuminate\\Queue\\Console\\WorkCommand->handle()\n#22 E:\\coupony-backend\\vendor\\laravel\\framework\\src\\Illuminate\\Container\\Util.php(43): Illuminate\\Container\\BoundMethod::Illuminate\\Container\\{closure}()\n#23 E:\\coupony-backend\\vendor\\laravel\\framework\\src\\Illuminate\\Container\\BoundMethod.php(96): Illuminate\\Container\\Util::unwrapIfClosure(Object(Closure))\n#24 E:\\coupony-backend\\vendor\\laravel\\framework\\src\\Illuminate\\Container\\BoundMethod.php(35): Illuminate\\Container\\BoundMethod::callBoundMethod(Object(Illuminate\\Foundation\\Application), Array, Object(Closure))\n#25 E:\\coupony-backend\\vendor\\laravel\\framework\\src\\Illuminate\\Container\\Container.php(799): Illuminate\\Container\\BoundMethod::call(Object(Illuminate\\Foundation\\Application), Array, Array, NULL)\n#26 E:\\coupony-backend\\vendor\\laravel\\framework\\src\\Illuminate\\Console\\Command.php(211): Illuminate\\Container\\Container->call(Array)\n#27 E:\\coupony-backend\\vendor\\symfony\\console\\Command\\Command.php(341): Illuminate\\Console\\Command->execute(Object(Symfony\\Component\\Console\\Input\\ArgvInput), Object(Illuminate\\Console\\OutputStyle))\n#28 E:\\coupony-backend\\vendor\\laravel\\framework\\src\\Illuminate\\Console\\Command.php(180): Symfony\\Component\\Console\\Command\\Command->run(Object(Symfony\\Component\\Console\\Input\\ArgvInput), Object(Illuminate\\Console\\OutputStyle))\n#29 E:\\coupony-backend\\vendor\\symfony\\console\\Application.php(1102): Illuminate\\Console\\Command->run(Object(Symfony\\Component\\Console\\Input\\ArgvInput), Object(Symfony\\Component\\Console\\Output\\ConsoleOutput))\n#30 E:\\coupony-backend\\vendor\\symfony\\console\\Application.php(356): Symfony\\Component\\Console\\Application->doRunCommand(Object(Illuminate\\Queue\\Console\\WorkCommand), Object(Symfony\\Component\\Console\\Input\\ArgvInput), Object(Symfony\\Component\\Console\\Output\\ConsoleOutput))\n#31 E:\\coupony-backend\\vendor\\symfony\\console\\Application.php(195): Symfony\\Component\\Console\\Application->doRun(Object(Symfony\\Component\\Console\\Input\\ArgvInput), Object(Symfony\\Component\\Console\\Output\\ConsoleOutput))\n#32 E:\\coupony-backend\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Console\\Kernel.php(198): Symfony\\Component\\Console\\Application->run(Object(Symfony\\Component\\Console\\Input\\ArgvInput), Object(Symfony\\Component\\Console\\Output\\ConsoleOutput))\n#33 E:\\coupony-backend\\vendor\\laravel\\framework\\src\\Illuminate\\Foundation\\Application.php(1235): Illuminate\\Foundation\\Console\\Kernel->handle(Object(Symfony\\Component\\Console\\Input\\ArgvInput), Object(Symfony\\Component\\Console\\Output\\ConsoleOutput))\n#34 E:\\coupony-backend\\artisan(16): Illuminate\\Foundation\\Application->handleCommand(Object(Symfony\\Component\\Console\\Input\\ArgvInput))\n#35 {main}', '2026-02-07 16:42:25');

-- --------------------------------------------------------

--
-- Table structure for table `interests`
--

CREATE TABLE `interests` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` char(36) NOT NULL,
  `interesting_offers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`interesting_offers`)),
  `budget` varchar(255) DEFAULT NULL,
  `shopping_style` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`shopping_style`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1),
(4, '2026_01_30_154542_create_personal_access_tokens_table', 1),
(5, '2026_01_30_203946_create_telescope_entries_table', 1),
(6, '2026_01_30_205540_create_permission_tables', 1),
(7, '2026_01_31_165326_create_notifications_table', 1),
(8, '2026_01_31_165450_create_otps_table', 1),
(9, '2026_01_31_165521_create_profiles_table', 1),
(10, '2026_01_31_215426_create_stores_table', 1),
(11, '2026_02_04_042658_create_user_roles_table', 1),
(12, '2026_02_04_043744_create_store_hours_table', 1),
(13, '2026_02_04_044452_create_store_followers_table', 1),
(14, '2026_02_05_180723_create_contact_us_customer_table', 1),
(15, '2026_02_05_180931_create_contact_us_seller_table', 1),
(16, '2026_02_05_211526_create_notify_me_table', 1),
(17, '2026_02_06_001507_create_interests_table', 1),
(18, '2026_02_07_232400_create_addresses_table', 2),
(19, '2026_02_07_232715_create_addressables_table', 2),
(20, '2026_02_08_002430_create_categories_table', 2),
(21, '2026_02_08_002836_create_store_categories_table', 2),
(22, '2026_02_08_002936_create_store_store_category_table', 2);

-- --------------------------------------------------------

--
-- Table structure for table `model_has_permissions`
--

CREATE TABLE `model_has_permissions` (
  `permission_id` bigint(20) UNSIGNED NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `model_has_roles`
--

CREATE TABLE `model_has_roles` (
  `role_id` bigint(20) UNSIGNED NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `model_has_roles`
--

INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`) VALUES
(1, 'App\\Domain\\User\\Models\\User', '193e51d4-5225-4cd0-8c54-5ea3b90a5539'),
(4, 'App\\Domain\\User\\Models\\User', '193e51d4-5225-4cd0-8c54-5ea3b90a5539'),
(4, 'App\\Domain\\User\\Models\\User', 'a496c4b6-719e-4aee-bb31-c7cb1063522e'),
(5, 'App\\Domain\\User\\Models\\User', 'c69077f2-4064-4b7d-85f7-836d04afa7a3');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` char(36) NOT NULL,
  `type` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`data`)),
  `channel` varchar(255) NOT NULL,
  `status` enum('pending','sent','failed','read') NOT NULL DEFAULT 'pending',
  `reference_type` varchar(50) DEFAULT NULL,
  `reference_id` char(36) DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `type`, `title`, `message`, `data`, `channel`, `status`, `reference_type`, `reference_id`, `sent_at`, `read_at`, `created_at`, `updated_at`) VALUES
(1, 'c69077f2-4064-4b7d-85f7-836d04afa7a3', 'otp_email', 'Verify Your Email', 'Your verification code is: 380582. Use this code to verify your email address. This code will expire in 10 minutes.', '{\"code\":\"380582\",\"purpose\":\"verify_email\",\"expires_at\":\"18:52\",\"expires_in_minutes\":-9.994399033333334}', 'email', 'sent', NULL, NULL, '2026-02-07 16:42:20', NULL, '2026-02-07 16:42:20', '2026-02-07 16:42:20');

-- --------------------------------------------------------

--
-- Table structure for table `notify_me`
--

CREATE TABLE `notify_me` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `email` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notify_me`
--

INSERT INTO `notify_me` (`id`, `email`, `created_at`, `updated_at`) VALUES
(1, 'email@gmail.com', '2026-02-06 17:51:46', '2026-02-06 17:51:46');

-- --------------------------------------------------------

--
-- Table structure for table `otps`
--

CREATE TABLE `otps` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` char(36) DEFAULT NULL,
  `phone_or_email` varchar(255) NOT NULL,
  `otp_hash` varchar(255) NOT NULL,
  `purpose` varchar(255) NOT NULL,
  `channel` varchar(255) NOT NULL,
  `status` enum('pending','verified','expired','blocked') NOT NULL DEFAULT 'pending',
  `attempts` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `max_attempts` tinyint(3) UNSIGNED NOT NULL DEFAULT 3,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `otps`
--

INSERT INTO `otps` (`id`, `user_id`, `phone_or_email`, `otp_hash`, `purpose`, `channel`, `status`, `attempts`, `max_attempts`, `expires_at`, `used_at`, `created_at`, `updated_at`) VALUES
(1, 'c69077f2-4064-4b7d-85f7-836d04afa7a3', 'ahmedmostafabusiness3@gmail.com', 'c16b9b8e398c479f71df9de8cce5d80f87f353434a0436e18213d0aa13198092', 'verify_email', 'email', 'pending', 0, 3, '2026-02-07 16:52:20', NULL, '2026-02-07 16:42:20', '2026-02-07 16:42:20');

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES
(1, 'store:create', 'sanctum', '2026-02-06 17:12:11', '2026-02-06 17:12:11'),
(2, 'store:manage', 'sanctum', '2026-02-06 17:12:11', '2026-02-06 17:12:11'),
(3, 'product:create', 'sanctum', '2026-02-06 17:12:11', '2026-02-06 17:12:11'),
(4, 'product:update', 'sanctum', '2026-02-06 17:12:11', '2026-02-06 17:12:11'),
(5, 'product:delete', 'sanctum', '2026-02-06 17:12:11', '2026-02-06 17:12:11'),
(6, 'order:view', 'sanctum', '2026-02-06 17:12:11', '2026-02-06 17:12:11'),
(7, 'order:update', 'sanctum', '2026-02-06 17:12:11', '2026-02-06 17:12:11'),
(8, 'product:read', 'sanctum', '2026-02-06 17:12:11', '2026-02-06 17:12:11'),
(9, 'order:create', 'sanctum', '2026-02-06 17:12:11', '2026-02-06 17:12:11'),
(10, 'order:read', 'sanctum', '2026-02-06 17:12:11', '2026-02-06 17:12:11'),
(11, 'cart:manage', 'sanctum', '2026-02-06 17:12:11', '2026-02-06 17:12:11'),
(12, 'profile:update', 'sanctum', '2026-02-06 17:12:11', '2026-02-06 17:12:11');

-- --------------------------------------------------------

--
-- Table structure for table `personal_access_tokens`
--

CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` char(36) NOT NULL,
  `name` text NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `personal_access_tokens`
--

INSERT INTO `personal_access_tokens` (`id`, `tokenable_type`, `tokenable_id`, `name`, `token`, `abilities`, `last_used_at`, `expires_at`, `created_at`, `updated_at`) VALUES
(1, 'App\\Domain\\User\\Models\\User', 'a496c4b6-719e-4aee-bb31-c7cb1063522e', 'PostmanRuntime/7.51.1', '0537b8964a1128a004b22e122f2fe4f756e9420bcc89b6e08d53886928936869', '[\"*\"]', '2026-02-06 17:53:05', '2026-02-06 18:16:53', '2026-02-06 17:16:53', '2026-02-06 17:53:05'),
(7, 'App\\Domain\\User\\Models\\User', 'a496c4b6-719e-4aee-bb31-c7cb1063522e', 'PostmanRuntime/7.51.1', '143a9e748a01df6a2683ef2e65c9f2bf0b91fc6853f43b3c188eb744fd3eda92', '[\"*\"]', '2026-02-07 23:30:29', '2026-02-08 00:12:41', '2026-02-07 23:12:41', '2026-02-07 23:30:29'),
(9, 'App\\Domain\\User\\Models\\User', '193e51d4-5225-4cd0-8c54-5ea3b90a5539', 'web-token', '91cf8c56bb659805d033e30f381b5ad7240ff9a21798537fde127249d1cee08f', '[\"*\"]', '2026-02-08 01:34:13', '2026-02-08 02:02:11', '2026-02-08 01:02:11', '2026-02-08 01:34:13');

-- --------------------------------------------------------

--
-- Table structure for table `profiles`
--

CREATE TABLE `profiles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` char(36) NOT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('male','female') DEFAULT NULL,
  `avatar_url` text DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `profiles`
--

INSERT INTO `profiles` (`id`, `user_id`, `first_name`, `last_name`, `date_of_birth`, `gender`, `avatar_url`, `bio`, `created_at`, `updated_at`) VALUES
(1, 'a496c4b6-719e-4aee-bb31-c7cb1063522e', 'Coupony', 'App', NULL, 'male', 'http://coupony.test/users/avatars/default.svg', NULL, '2026-02-06 17:12:11', '2026-02-06 17:12:11'),
(2, '193e51d4-5225-4cd0-8c54-5ea3b90a5539', 'Ahmed', 'Mostafa', NULL, 'male', 'http://coupony.test/users/avatars/default.svg', NULL, '2026-02-06 17:25:19', '2026-02-06 17:25:19'),
(3, 'c69077f2-4064-4b7d-85f7-836d04afa7a3', 'Ahmed', 'Mostafa', NULL, 'male', 'http://coupony.test/users/avatars/default.svg', NULL, '2026-02-07 16:42:20', '2026-02-07 16:42:20');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES
(1, 'seller', 'sanctum', '2026-02-06 17:12:11', '2026-02-06 17:12:11'),
(2, 'store_manager', 'sanctum', '2026-02-06 17:12:11', '2026-02-06 17:12:11'),
(3, 'store_staff', 'sanctum', '2026-02-06 17:12:11', '2026-02-06 17:12:11'),
(4, 'admin', 'sanctum', '2026-02-06 17:12:11', '2026-02-06 17:12:11'),
(5, 'customer', 'sanctum', '2026-02-06 17:12:11', '2026-02-06 17:12:11');

-- --------------------------------------------------------

--
-- Table structure for table `role_has_permissions`
--

CREATE TABLE `role_has_permissions` (
  `permission_id` bigint(20) UNSIGNED NOT NULL,
  `role_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `role_has_permissions`
--

INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES
(1, 1),
(2, 1),
(2, 2),
(3, 1),
(3, 2),
(3, 3),
(4, 1),
(4, 2),
(5, 1),
(6, 1),
(6, 2),
(6, 3),
(7, 1),
(7, 2),
(7, 3),
(8, 5),
(9, 5),
(10, 5),
(11, 5),
(12, 5);

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` char(36) NOT NULL,
  `user_id` char(36) DEFAULT NULL,
  `token` varchar(255) NOT NULL,
  `user_agent` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `payload` varchar(255) DEFAULT NULL,
  `device_type` varchar(255) DEFAULT NULL,
  `last_activity` int(10) UNSIGNED NOT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `verified_at` timestamp NULL DEFAULT NULL,
  `revoked_at` timestamp NULL DEFAULT NULL,
  `revoked_reason` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `token`, `user_agent`, `ip_address`, `payload`, `device_type`, `last_activity`, `expires_at`, `verified_at`, `revoked_at`, `revoked_reason`, `created_at`, `updated_at`) VALUES
('293996fe-77b7-479f-a745-49d0f5ae3aba', 'a496c4b6-719e-4aee-bb31-c7cb1063522e', 'a93192186da85422d6c9eb95d29423f4f2938a30e442e351c1030033ed7d5f11', 'PostmanRuntime/7.51.1', '127.0.0.1', NULL, 'desktop', 1770398213, '2026-02-06 18:16:53', NULL, NULL, NULL, '2026-02-06 17:16:53', '2026-02-06 17:16:53'),
('3a228342-5907-482b-9579-d21a9e88b2ed', '193e51d4-5225-4cd0-8c54-5ea3b90a5539', '7e8bea3cc2e65b8bbd44fbef7d0f10c1702ee1a1ecfad236babbcf6c9027db18', 'PostmanRuntime/7.51.1', '127.0.0.1', NULL, 'desktop', 1770400483, '2026-02-06 17:54:55', NULL, NULL, NULL, '2026-02-06 17:54:43', '2026-02-06 17:54:55'),
('550582e1-a180-487c-b0d5-cd935e78f860', '193e51d4-5225-4cd0-8c54-5ea3b90a5539', '4b88c5459fbdfebccf37c81145090239f7a5812578d4144c4902d4d74f67949d', 'PostmanRuntime/7.51.1', '127.0.0.1', NULL, 'desktop', 1770400709, '2026-02-06 18:58:29', NULL, NULL, NULL, '2026-02-06 17:58:29', '2026-02-06 17:58:29'),
('8183ac20-f00a-40e7-97d2-6c026ce38784', 'a496c4b6-719e-4aee-bb31-c7cb1063522e', '1c9e9a513a0612018e0f5032c7981d15173c7b9cd3145fd070dd8110c5341935', 'PostmanRuntime/7.51.1', '127.0.0.1', NULL, 'desktop', 1770505961, '2026-02-08 00:12:41', NULL, NULL, NULL, '2026-02-07 23:12:41', '2026-02-07 23:12:41'),
('9625292f-24b3-4b4d-b721-60011a41e49b', '193e51d4-5225-4cd0-8c54-5ea3b90a5539', '6e7ba03c02651736c4583cedf170ffac27756e7c188c1aad0ebf1a2f3587f65d', 'PostmanRuntime/7.51.1', '127.0.0.1', NULL, 'desktop', 1770409681, '2026-02-06 21:28:01', NULL, NULL, NULL, '2026-02-06 20:28:01', '2026-02-06 20:28:01'),
('9c264961-1aa8-46b3-b494-00afe6d64c98', '193e51d4-5225-4cd0-8c54-5ea3b90a5539', '903f2d5cacc9249f3249cc8362d7ae6f83788f8aa1175c4724ae19bedd05f568', 'PostmanRuntime/7.51.1', '127.0.0.1', NULL, 'desktop', 1770400456, '2026-02-06 17:54:55', NULL, NULL, NULL, '2026-02-06 17:54:16', '2026-02-06 17:54:55'),
('acbf1bbe-bb7c-465f-ba2d-95bf18d970a4', '193e51d4-5225-4cd0-8c54-5ea3b90a5539', 'a1ddc675ea5c3b06e9538d504f1a4facd8b01f5ec612899808ed7fe3ea4be601', 'PostmanRuntime/7.51.1', '127.0.0.1', NULL, 'desktop', 1770400522, '2026-02-06 18:55:22', NULL, NULL, NULL, '2026-02-06 17:55:22', '2026-02-06 17:55:22'),
('d8813516-1ea5-44d7-a772-308a0b8a030b', '193e51d4-5225-4cd0-8c54-5ea3b90a5539', '8278133c9fb0e51c9a2dff0d5ff06c3a483e044ce9c4e4967f83ec5021ac411f', 'PostmanRuntime/7.51.1', '127.0.0.1', NULL, 'desktop', 1770508685, '2026-02-08 00:58:05', NULL, NULL, NULL, '2026-02-07 23:58:05', '2026-02-07 23:58:05');

-- --------------------------------------------------------

--
-- Table structure for table `stores`
--

CREATE TABLE `stores` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `owner_user_id` char(36) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `logo_url` text DEFAULT NULL,
  `banner_url` text DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `tax_id` varchar(100) DEFAULT NULL,
  `commission_rate` decimal(5,4) NOT NULL DEFAULT 0.1500,
  `status` enum('pending','active','suspended','closed') NOT NULL DEFAULT 'pending',
  `subscription_tier` enum('free','basic','premium','enterprise') NOT NULL DEFAULT 'free',
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `verified_at` timestamp NULL DEFAULT NULL,
  `total_sales` decimal(15,2) NOT NULL DEFAULT 0.00,
  `rating_avg` decimal(3,2) NOT NULL DEFAULT 0.00,
  `rating_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `shard_key` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `stores`
--

INSERT INTO `stores` (`id`, `owner_user_id`, `name`, `description`, `logo_url`, `banner_url`, `email`, `phone`, `tax_id`, `commission_rate`, `status`, `subscription_tier`, `is_verified`, `verified_at`, `total_sales`, `rating_avg`, `rating_count`, `shard_key`, `created_at`, `updated_at`, `deleted_at`) VALUES
('06e975df-9320-4b1a-af53-b5c8f6373b39', '193e51d4-5225-4cd0-8c54-5ea3b90a5539', 'HM', NULL, NULL, NULL, 'lofylofy56@gmail.com', '01025250321', NULL, 0.1500, 'pending', 'free', 0, NULL, 0.00, 0.00, 0, NULL, '2026-02-08 01:20:45', '2026-02-08 01:20:45', NULL),
('1209d5a1-5ffa-4f77-b073-99f7098ae404', '193e51d4-5225-4cd0-8c54-5ea3b90a5539', 'HM', NULL, NULL, NULL, 'lofylofy56@gmail.com', '01025250321', NULL, 0.1500, 'pending', 'free', 0, NULL, 0.00, 0.00, 0, NULL, '2026-02-08 01:30:11', '2026-02-08 01:30:11', NULL),
('1d26abd4-7ab2-49e4-9feb-decb8767a1d9', '193e51d4-5225-4cd0-8c54-5ea3b90a5539', 'HM', NULL, NULL, NULL, 'lofylofy56@gmail.com', '01025250321', NULL, 0.1500, 'pending', 'free', 0, NULL, 0.00, 0.00, 0, NULL, '2026-02-08 01:30:41', '2026-02-08 01:30:41', NULL),
('219f77bc-bb40-4121-9959-df510c83f90a', '193e51d4-5225-4cd0-8c54-5ea3b90a5539', 'HM', NULL, NULL, NULL, 'lofylofy56@gmail.com', '01025250321', NULL, 0.1500, 'pending', 'free', 0, NULL, 0.00, 0.00, 0, NULL, '2026-02-08 00:38:58', '2026-02-08 00:38:58', NULL),
('22507230-3892-4e78-9681-81c0ec94d7d0', '193e51d4-5225-4cd0-8c54-5ea3b90a5539', 'HM', NULL, NULL, NULL, 'lofylofy56@gmail.com', '01025250321', NULL, 0.1500, 'pending', 'free', 0, NULL, 0.00, 0.00, 0, NULL, '2026-02-08 01:34:13', '2026-02-08 01:34:13', NULL),
('2c45144b-9bcd-4b01-ace8-9133683cbf34', '193e51d4-5225-4cd0-8c54-5ea3b90a5539', 'HM', NULL, NULL, NULL, 'lofylofy56@gmail.com', '01025250321', NULL, 0.1500, 'pending', 'free', 0, NULL, 0.00, 0.00, 0, NULL, '2026-02-08 00:39:45', '2026-02-08 00:39:45', NULL),
('38dc83c7-7eed-4fe8-86ba-cab830e47b7f', '193e51d4-5225-4cd0-8c54-5ea3b90a5539', 'HM', NULL, NULL, NULL, 'lofylofy56@gmail.com', '01025250321', NULL, 0.1500, 'pending', 'free', 0, NULL, 0.00, 0.00, 0, NULL, '2026-02-08 01:25:03', '2026-02-08 01:25:03', NULL),
('39b79652-beef-4877-8bd2-058eab653f6f', '193e51d4-5225-4cd0-8c54-5ea3b90a5539', 'HM', NULL, NULL, NULL, 'lofylofy56@gmail.com', '01025250321', NULL, 0.1500, 'pending', 'free', 0, NULL, 0.00, 0.00, 0, NULL, '2026-02-08 01:16:11', '2026-02-08 01:16:11', NULL),
('468070bb-5b1b-49bf-8e43-2265c4be08ef', '193e51d4-5225-4cd0-8c54-5ea3b90a5539', 'HM', NULL, NULL, NULL, 'lofylofy56@gmail.com', '01025250321', NULL, 0.1500, 'pending', 'free', 0, NULL, 0.00, 0.00, 0, NULL, '2026-02-08 01:21:59', '2026-02-08 01:21:59', NULL),
('55dbc156-4082-46dc-90cd-b543b4f18418', '193e51d4-5225-4cd0-8c54-5ea3b90a5539', 'HM', NULL, NULL, NULL, 'lofylofy56@gmail.com', '01025250321', NULL, 0.1500, 'pending', 'free', 0, NULL, 0.00, 0.00, 0, NULL, '2026-02-08 01:06:59', '2026-02-08 01:06:59', NULL),
('5e7ae24d-fe0c-4829-b893-4eff6ecb2791', '193e51d4-5225-4cd0-8c54-5ea3b90a5539', 'HM', NULL, NULL, NULL, 'lofylofy56@gmail.com', '01025250321', NULL, 0.1500, 'pending', 'free', 0, NULL, 0.00, 0.00, 0, NULL, '2026-02-08 00:52:50', '2026-02-08 00:52:50', NULL),
('61df8a7d-2493-4911-b3ec-8f9d9fcb6e69', '193e51d4-5225-4cd0-8c54-5ea3b90a5539', 'HM', NULL, NULL, NULL, 'lofylofy56@gmail.com', '01025250321', NULL, 0.1500, 'pending', 'free', 0, NULL, 0.00, 0.00, 0, NULL, '2026-02-08 00:26:20', '2026-02-08 00:26:20', NULL),
('62984122-93ab-45df-bcb5-3a32258ab511', '193e51d4-5225-4cd0-8c54-5ea3b90a5539', 'HM', NULL, NULL, NULL, 'lofylofy56@gmail.com', '01025250321', NULL, 0.1500, 'pending', 'free', 0, NULL, 0.00, 0.00, 0, NULL, '2026-02-08 00:40:37', '2026-02-08 00:40:37', NULL),
('67d978be-f571-4ecf-b939-997daa4c16a6', '193e51d4-5225-4cd0-8c54-5ea3b90a5539', 'HM', NULL, NULL, NULL, 'lofylofy56@gmail.com', '01025250321', NULL, 0.1500, 'pending', 'free', 0, NULL, 0.00, 0.00, 0, NULL, '2026-02-08 01:26:14', '2026-02-08 01:26:14', NULL),
('6881dd0d-87d1-4a19-b8b8-f6e02e7aec40', '193e51d4-5225-4cd0-8c54-5ea3b90a5539', 'HM', NULL, NULL, NULL, 'lofylofy56@gmail.com', '01025250321', NULL, 0.1500, 'pending', 'free', 0, NULL, 0.00, 0.00, 0, NULL, '2026-02-08 01:02:23', '2026-02-08 01:02:23', NULL),
('6df8be66-bac3-4b9a-b51f-7c3c23fb8481', '193e51d4-5225-4cd0-8c54-5ea3b90a5539', 'HM', NULL, NULL, NULL, 'lofylofy56@gmail.com', '01025250321', NULL, 0.1500, 'pending', 'free', 0, NULL, 0.00, 0.00, 0, NULL, '2026-02-08 00:25:06', '2026-02-08 00:25:06', NULL),
('74824326-6c95-4ff4-83c0-8ac729d97817', '193e51d4-5225-4cd0-8c54-5ea3b90a5539', 'HM', NULL, NULL, NULL, 'lofylofy56@gmail.com', '01025250321', NULL, 0.1500, 'pending', 'free', 0, NULL, 0.00, 0.00, 0, NULL, '2026-02-08 01:16:00', '2026-02-08 01:16:00', NULL),
('76428452-698b-4516-80ff-1f8223faf4e2', '193e51d4-5225-4cd0-8c54-5ea3b90a5539', 'HM', NULL, NULL, NULL, 'lofylofy56@gmail.com', '01025250321', NULL, 0.1500, 'pending', 'free', 0, NULL, 0.00, 0.00, 0, NULL, '2026-02-08 01:31:23', '2026-02-08 01:31:23', NULL),
('7f239fef-820c-4d1b-8e13-f778e50568e5', '193e51d4-5225-4cd0-8c54-5ea3b90a5539', 'HM', NULL, NULL, NULL, 'lofylofy56@gmail.com', '01025250321', NULL, 0.1500, 'pending', 'free', 0, NULL, 0.00, 0.00, 0, NULL, '2026-02-08 00:45:38', '2026-02-08 00:45:38', NULL),
('8083ad58-00c2-49f0-b282-7dcd39beb107', '193e51d4-5225-4cd0-8c54-5ea3b90a5539', 'HM', NULL, NULL, NULL, 'lofylofy56@gmail.com', '01025250321', NULL, 0.1500, 'pending', 'free', 0, NULL, 0.00, 0.00, 0, NULL, '2026-02-08 00:37:52', '2026-02-08 00:37:52', NULL),
('85093754-da13-4d21-b53d-f76226b83e5e', '193e51d4-5225-4cd0-8c54-5ea3b90a5539', 'HM', NULL, NULL, NULL, 'lofylofy56@gmail.com', '01025250321', NULL, 0.1500, 'pending', 'free', 0, NULL, 0.00, 0.00, 0, NULL, '2026-02-08 01:22:10', '2026-02-08 01:22:10', NULL),
('8820cda8-50d1-4617-b741-f91ae252d253', '193e51d4-5225-4cd0-8c54-5ea3b90a5539', 'HM', NULL, NULL, NULL, 'lofylofy56@gmail.com', '01025250321', NULL, 0.1500, 'pending', 'free', 0, NULL, 0.00, 0.00, 0, NULL, '2026-02-08 01:33:58', '2026-02-08 01:33:58', NULL),
('96d9c4fa-a5cb-424e-85dc-506842dc2979', '193e51d4-5225-4cd0-8c54-5ea3b90a5539', 'HM', NULL, NULL, NULL, 'lofylofy56@gmail.com', '01025250321', NULL, 0.1500, 'pending', 'free', 0, NULL, 0.00, 0.00, 0, NULL, '2026-02-08 01:19:46', '2026-02-08 01:19:46', NULL),
('99cc4e46-477f-49ae-8716-ec4ac94a83f3', '193e51d4-5225-4cd0-8c54-5ea3b90a5539', 'HM', NULL, NULL, NULL, 'lofylofy56@gmail.com', '01025250321', NULL, 0.1500, 'pending', 'free', 0, NULL, 0.00, 0.00, 0, NULL, '2026-02-08 01:29:13', '2026-02-08 01:29:13', NULL),
('a382425d-15bb-4e35-9988-5a8f4f99fe72', '193e51d4-5225-4cd0-8c54-5ea3b90a5539', 'HM', NULL, NULL, NULL, 'lofylofy56@gmail.com', '01025250321', NULL, 0.1500, 'pending', 'free', 0, NULL, 0.00, 0.00, 0, NULL, '2026-02-08 00:17:55', '2026-02-08 00:17:55', NULL),
('a9abfce0-b6b0-41d0-be5d-8ce27662bace', '193e51d4-5225-4cd0-8c54-5ea3b90a5539', 'HM', NULL, NULL, NULL, 'lofylofy56@gmail.com', '01025250321', NULL, 0.1500, 'pending', 'free', 0, NULL, 0.00, 0.00, 0, NULL, '2026-02-08 01:31:46', '2026-02-08 01:31:46', NULL),
('a9cea373-3add-4870-8d20-396244f0c458', '193e51d4-5225-4cd0-8c54-5ea3b90a5539', 'HM', NULL, NULL, NULL, 'lofylofy56@gmail.com', '01025250321', NULL, 0.1500, 'pending', 'free', 0, NULL, 0.00, 0.00, 0, NULL, '2026-02-08 01:02:52', '2026-02-08 01:02:52', NULL),
('ad61c349-7c87-42ec-9ab4-423f238fad87', '193e51d4-5225-4cd0-8c54-5ea3b90a5539', 'HM', NULL, NULL, NULL, 'lofylofy56@gmail.com', '01025250321', NULL, 0.1500, 'pending', 'free', 0, NULL, 0.00, 0.00, 0, NULL, '2026-02-08 01:19:24', '2026-02-08 01:19:24', NULL),
('bd5f6b10-1640-4fe0-98a3-00f2eebf48f6', '193e51d4-5225-4cd0-8c54-5ea3b90a5539', 'HM', NULL, NULL, NULL, 'lofylofy56@gmail.com', '01025250321', NULL, 0.1500, 'pending', 'free', 0, NULL, 0.00, 0.00, 0, NULL, '2026-02-08 01:29:07', '2026-02-08 01:29:07', NULL),
('bed4702c-de6b-4dbd-a9fc-663f405e09f7', '193e51d4-5225-4cd0-8c54-5ea3b90a5539', 'HM', NULL, NULL, NULL, 'lofylofy56@gmail.com', '01025250321', NULL, 0.1500, 'pending', 'free', 0, NULL, 0.00, 0.00, 0, NULL, '2026-02-08 00:14:45', '2026-02-08 00:14:45', NULL),
('d5b9c837-3f55-4ba1-9b9a-94e4f146ef42', '193e51d4-5225-4cd0-8c54-5ea3b90a5539', 'HM', NULL, NULL, NULL, 'lofylofy56@gmail.com', '01025250321', NULL, 0.1500, 'pending', 'free', 0, NULL, 0.00, 0.00, 0, NULL, '2026-02-08 00:41:00', '2026-02-08 00:41:00', NULL),
('da952527-786d-4073-b8b2-753b64dcc99b', '193e51d4-5225-4cd0-8c54-5ea3b90a5539', 'HM', NULL, NULL, NULL, 'lofylofy56@gmail.com', '01025250321', NULL, 0.1500, 'pending', 'free', 0, NULL, 0.00, 0.00, 0, NULL, '2026-02-08 00:38:20', '2026-02-08 00:38:20', NULL),
('e608bf43-4e36-4823-8fda-b46399ad160f', '193e51d4-5225-4cd0-8c54-5ea3b90a5539', 'HM', NULL, NULL, NULL, 'lofylofy56@gmail.com', '01025250321', NULL, 0.1500, 'pending', 'free', 0, NULL, 0.00, 0.00, 0, NULL, '2026-02-08 01:33:32', '2026-02-08 01:33:32', NULL),
('eaf79b81-447a-4a7a-8a08-f26571f298b1', '193e51d4-5225-4cd0-8c54-5ea3b90a5539', 'HM', NULL, NULL, NULL, 'lofylofy56@gmail.com', '01025250321', NULL, 0.1500, 'pending', 'free', 0, NULL, 0.00, 0.00, 0, NULL, '2026-02-08 01:28:08', '2026-02-08 01:28:08', NULL),
('eb379eb9-994b-466e-95fb-ce9344b37311', '193e51d4-5225-4cd0-8c54-5ea3b90a5539', 'HM', NULL, NULL, NULL, 'lofylofy56@gmail.com', '01025250321', NULL, 0.1500, 'pending', 'free', 0, NULL, 0.00, 0.00, 0, NULL, '2026-02-08 01:33:12', '2026-02-08 01:33:12', NULL),
('ecf29eb0-81ce-4a21-8174-15ce00d5d5b0', '193e51d4-5225-4cd0-8c54-5ea3b90a5539', 'HM', NULL, NULL, NULL, 'lofylofy56@gmail.com', '01025250321', NULL, 0.1500, 'pending', 'free', 0, NULL, 0.00, 0.00, 0, NULL, '2026-02-08 01:10:43', '2026-02-08 01:10:43', NULL),
('ee205576-2965-4202-b8a6-5f3f43c8e31a', '193e51d4-5225-4cd0-8c54-5ea3b90a5539', 'HM', NULL, NULL, NULL, 'lofylofy56@gmail.com', '01025250321', NULL, 0.1500, 'pending', 'free', 0, NULL, 0.00, 0.00, 0, NULL, '2026-02-08 01:33:52', '2026-02-08 01:33:52', NULL),
('f3d9eca3-c7a3-4571-82d3-b76cf1d1f1d9', '193e51d4-5225-4cd0-8c54-5ea3b90a5539', 'HM', NULL, NULL, NULL, 'lofylofy56@gmail.com', '01025250321', NULL, 0.1500, 'pending', 'free', 0, NULL, 0.00, 0.00, 0, NULL, '2026-02-08 00:47:24', '2026-02-08 00:47:24', NULL),
('f81b7249-08f9-430f-b06d-f54b5f3ebe4e', '193e51d4-5225-4cd0-8c54-5ea3b90a5539', 'HM', NULL, NULL, NULL, 'lofylofy56@gmail.com', '01025250321', NULL, 0.1500, 'pending', 'free', 0, NULL, 0.00, 0.00, 0, NULL, '2026-02-08 01:04:38', '2026-02-08 01:04:38', NULL),
('f8886dc8-36c1-4674-8aa3-cdf998cf32ce', '193e51d4-5225-4cd0-8c54-5ea3b90a5539', 'HM', NULL, NULL, NULL, 'lofylofy56@gmail.com', '01025250321', NULL, 0.1500, 'pending', 'free', 0, NULL, 0.00, 0.00, 0, NULL, '2026-02-08 01:28:53', '2026-02-08 01:28:53', NULL),
('fe50f29d-ddb5-4e6f-80e3-47cb34893a07', '193e51d4-5225-4cd0-8c54-5ea3b90a5539', 'HM', NULL, NULL, NULL, 'lofylofy56@gmail.com', '01025250321', NULL, 0.1500, 'pending', 'free', 0, NULL, 0.00, 0.00, 0, NULL, '2026-02-08 01:29:12', '2026-02-08 01:29:12', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `store_categories`
--

CREATE TABLE `store_categories` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `store_categories`
--

INSERT INTO `store_categories` (`id`, `name`, `is_active`, `created_at`, `updated_at`) VALUES
(2, 'restaurant', 1, '2026-02-07 23:20:27', '2026-02-07 23:20:27'),
(3, 'cafe', 0, '2026-02-07 23:21:20', '2026-02-07 23:21:20');

-- --------------------------------------------------------

--
-- Table structure for table `store_followers`
--

CREATE TABLE `store_followers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` char(36) NOT NULL,
  `store_id` char(36) NOT NULL,
  `notification_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `followed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `store_hours`
--

CREATE TABLE `store_hours` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `store_id` char(36) NOT NULL,
  `day_of_week` tinyint(3) UNSIGNED NOT NULL,
  `open_time` time NOT NULL,
  `close_time` time NOT NULL,
  `is_closed` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `store_hours`
--

INSERT INTO `store_hours` (`id`, `store_id`, `day_of_week`, `open_time`, `close_time`, `is_closed`, `created_at`, `updated_at`) VALUES
(1, 'bed4702c-de6b-4dbd-a9fc-663f405e09f7', 1, '09:00:00', '17:00:00', 0, '2026-02-08 00:14:45', '2026-02-08 00:14:45'),
(2, 'bed4702c-de6b-4dbd-a9fc-663f405e09f7', 2, '09:00:00', '17:00:00', 0, '2026-02-08 00:14:45', '2026-02-08 00:14:45'),
(3, 'bed4702c-de6b-4dbd-a9fc-663f405e09f7', 3, '09:00:00', '17:00:00', 0, '2026-02-08 00:14:45', '2026-02-08 00:14:45'),
(4, 'bed4702c-de6b-4dbd-a9fc-663f405e09f7', 4, '09:00:00', '17:00:00', 0, '2026-02-08 00:14:45', '2026-02-08 00:14:45'),
(5, 'bed4702c-de6b-4dbd-a9fc-663f405e09f7', 5, '09:00:00', '17:00:00', 0, '2026-02-08 00:14:45', '2026-02-08 00:14:45'),
(6, 'bed4702c-de6b-4dbd-a9fc-663f405e09f7', 6, '09:00:00', '17:00:00', 1, '2026-02-08 00:14:45', '2026-02-08 00:14:45'),
(7, 'bed4702c-de6b-4dbd-a9fc-663f405e09f7', 0, '09:00:00', '17:00:00', 1, '2026-02-08 00:14:45', '2026-02-08 00:14:45'),
(8, 'a382425d-15bb-4e35-9988-5a8f4f99fe72', 1, '09:00:00', '17:00:00', 0, '2026-02-08 00:17:55', '2026-02-08 00:17:55'),
(9, 'a382425d-15bb-4e35-9988-5a8f4f99fe72', 2, '09:00:00', '17:00:00', 0, '2026-02-08 00:17:55', '2026-02-08 00:17:55'),
(10, 'a382425d-15bb-4e35-9988-5a8f4f99fe72', 3, '09:00:00', '17:00:00', 0, '2026-02-08 00:17:55', '2026-02-08 00:17:55'),
(11, 'a382425d-15bb-4e35-9988-5a8f4f99fe72', 4, '09:00:00', '17:00:00', 0, '2026-02-08 00:17:55', '2026-02-08 00:17:55'),
(12, 'a382425d-15bb-4e35-9988-5a8f4f99fe72', 5, '09:00:00', '17:00:00', 0, '2026-02-08 00:17:55', '2026-02-08 00:17:55'),
(13, 'a382425d-15bb-4e35-9988-5a8f4f99fe72', 6, '09:00:00', '17:00:00', 1, '2026-02-08 00:17:55', '2026-02-08 00:17:55'),
(14, 'a382425d-15bb-4e35-9988-5a8f4f99fe72', 0, '09:00:00', '17:00:00', 1, '2026-02-08 00:17:55', '2026-02-08 00:17:55'),
(15, '6df8be66-bac3-4b9a-b51f-7c3c23fb8481', 1, '09:00:00', '17:00:00', 0, '2026-02-08 00:25:06', '2026-02-08 00:25:06'),
(16, '6df8be66-bac3-4b9a-b51f-7c3c23fb8481', 2, '09:00:00', '17:00:00', 0, '2026-02-08 00:25:06', '2026-02-08 00:25:06'),
(17, '6df8be66-bac3-4b9a-b51f-7c3c23fb8481', 3, '09:00:00', '17:00:00', 0, '2026-02-08 00:25:06', '2026-02-08 00:25:06'),
(18, '6df8be66-bac3-4b9a-b51f-7c3c23fb8481', 4, '09:00:00', '17:00:00', 0, '2026-02-08 00:25:06', '2026-02-08 00:25:06'),
(19, '6df8be66-bac3-4b9a-b51f-7c3c23fb8481', 5, '09:00:00', '17:00:00', 0, '2026-02-08 00:25:06', '2026-02-08 00:25:06'),
(20, '6df8be66-bac3-4b9a-b51f-7c3c23fb8481', 6, '09:00:00', '17:00:00', 1, '2026-02-08 00:25:06', '2026-02-08 00:25:06'),
(21, '6df8be66-bac3-4b9a-b51f-7c3c23fb8481', 0, '09:00:00', '17:00:00', 1, '2026-02-08 00:25:06', '2026-02-08 00:25:06'),
(22, '61df8a7d-2493-4911-b3ec-8f9d9fcb6e69', 1, '09:00:00', '17:00:00', 0, '2026-02-08 00:26:20', '2026-02-08 00:26:20'),
(23, '61df8a7d-2493-4911-b3ec-8f9d9fcb6e69', 2, '09:00:00', '17:00:00', 0, '2026-02-08 00:26:20', '2026-02-08 00:26:20'),
(24, '61df8a7d-2493-4911-b3ec-8f9d9fcb6e69', 3, '09:00:00', '17:00:00', 0, '2026-02-08 00:26:20', '2026-02-08 00:26:20'),
(25, '61df8a7d-2493-4911-b3ec-8f9d9fcb6e69', 4, '09:00:00', '17:00:00', 0, '2026-02-08 00:26:20', '2026-02-08 00:26:20'),
(26, '61df8a7d-2493-4911-b3ec-8f9d9fcb6e69', 5, '09:00:00', '17:00:00', 0, '2026-02-08 00:26:20', '2026-02-08 00:26:20'),
(27, '61df8a7d-2493-4911-b3ec-8f9d9fcb6e69', 6, '09:00:00', '17:00:00', 1, '2026-02-08 00:26:20', '2026-02-08 00:26:20'),
(28, '61df8a7d-2493-4911-b3ec-8f9d9fcb6e69', 0, '09:00:00', '17:00:00', 1, '2026-02-08 00:26:20', '2026-02-08 00:26:20'),
(29, '8083ad58-00c2-49f0-b282-7dcd39beb107', 1, '09:00:00', '17:00:00', 0, '2026-02-08 00:37:52', '2026-02-08 00:37:52'),
(30, '8083ad58-00c2-49f0-b282-7dcd39beb107', 2, '09:00:00', '17:00:00', 0, '2026-02-08 00:37:52', '2026-02-08 00:37:52'),
(31, '8083ad58-00c2-49f0-b282-7dcd39beb107', 3, '09:00:00', '17:00:00', 0, '2026-02-08 00:37:52', '2026-02-08 00:37:52'),
(32, '8083ad58-00c2-49f0-b282-7dcd39beb107', 4, '09:00:00', '17:00:00', 0, '2026-02-08 00:37:52', '2026-02-08 00:37:52'),
(33, '8083ad58-00c2-49f0-b282-7dcd39beb107', 5, '09:00:00', '17:00:00', 0, '2026-02-08 00:37:52', '2026-02-08 00:37:52'),
(34, '8083ad58-00c2-49f0-b282-7dcd39beb107', 6, '09:00:00', '17:00:00', 1, '2026-02-08 00:37:52', '2026-02-08 00:37:52'),
(35, '8083ad58-00c2-49f0-b282-7dcd39beb107', 0, '09:00:00', '17:00:00', 1, '2026-02-08 00:37:52', '2026-02-08 00:37:52'),
(36, 'da952527-786d-4073-b8b2-753b64dcc99b', 1, '09:00:00', '17:00:00', 0, '2026-02-08 00:38:20', '2026-02-08 00:38:20'),
(37, 'da952527-786d-4073-b8b2-753b64dcc99b', 2, '09:00:00', '17:00:00', 0, '2026-02-08 00:38:20', '2026-02-08 00:38:20'),
(38, 'da952527-786d-4073-b8b2-753b64dcc99b', 3, '09:00:00', '17:00:00', 0, '2026-02-08 00:38:20', '2026-02-08 00:38:20'),
(39, 'da952527-786d-4073-b8b2-753b64dcc99b', 4, '09:00:00', '17:00:00', 0, '2026-02-08 00:38:20', '2026-02-08 00:38:20'),
(40, 'da952527-786d-4073-b8b2-753b64dcc99b', 5, '09:00:00', '17:00:00', 0, '2026-02-08 00:38:20', '2026-02-08 00:38:20'),
(41, 'da952527-786d-4073-b8b2-753b64dcc99b', 6, '09:00:00', '17:00:00', 1, '2026-02-08 00:38:20', '2026-02-08 00:38:20'),
(42, 'da952527-786d-4073-b8b2-753b64dcc99b', 0, '09:00:00', '17:00:00', 1, '2026-02-08 00:38:20', '2026-02-08 00:38:20'),
(43, '219f77bc-bb40-4121-9959-df510c83f90a', 1, '09:00:00', '17:00:00', 0, '2026-02-08 00:38:58', '2026-02-08 00:38:58'),
(44, '219f77bc-bb40-4121-9959-df510c83f90a', 2, '09:00:00', '17:00:00', 0, '2026-02-08 00:38:58', '2026-02-08 00:38:58'),
(45, '219f77bc-bb40-4121-9959-df510c83f90a', 3, '09:00:00', '17:00:00', 0, '2026-02-08 00:38:58', '2026-02-08 00:38:58'),
(46, '219f77bc-bb40-4121-9959-df510c83f90a', 4, '09:00:00', '17:00:00', 0, '2026-02-08 00:38:58', '2026-02-08 00:38:58'),
(47, '219f77bc-bb40-4121-9959-df510c83f90a', 5, '09:00:00', '17:00:00', 0, '2026-02-08 00:38:58', '2026-02-08 00:38:58'),
(48, '219f77bc-bb40-4121-9959-df510c83f90a', 6, '09:00:00', '17:00:00', 1, '2026-02-08 00:38:58', '2026-02-08 00:38:58'),
(49, '219f77bc-bb40-4121-9959-df510c83f90a', 0, '09:00:00', '17:00:00', 1, '2026-02-08 00:38:58', '2026-02-08 00:38:58'),
(50, '2c45144b-9bcd-4b01-ace8-9133683cbf34', 1, '09:00:00', '17:00:00', 0, '2026-02-08 00:39:45', '2026-02-08 00:39:45'),
(51, '2c45144b-9bcd-4b01-ace8-9133683cbf34', 2, '09:00:00', '17:00:00', 0, '2026-02-08 00:39:45', '2026-02-08 00:39:45'),
(52, '2c45144b-9bcd-4b01-ace8-9133683cbf34', 3, '09:00:00', '17:00:00', 0, '2026-02-08 00:39:45', '2026-02-08 00:39:45'),
(53, '2c45144b-9bcd-4b01-ace8-9133683cbf34', 4, '09:00:00', '17:00:00', 0, '2026-02-08 00:39:45', '2026-02-08 00:39:45'),
(54, '2c45144b-9bcd-4b01-ace8-9133683cbf34', 5, '09:00:00', '17:00:00', 0, '2026-02-08 00:39:45', '2026-02-08 00:39:45'),
(55, '2c45144b-9bcd-4b01-ace8-9133683cbf34', 6, '09:00:00', '17:00:00', 1, '2026-02-08 00:39:45', '2026-02-08 00:39:45'),
(56, '2c45144b-9bcd-4b01-ace8-9133683cbf34', 0, '09:00:00', '17:00:00', 1, '2026-02-08 00:39:45', '2026-02-08 00:39:45'),
(57, '62984122-93ab-45df-bcb5-3a32258ab511', 1, '09:00:00', '17:00:00', 0, '2026-02-08 00:40:37', '2026-02-08 00:40:37'),
(58, '62984122-93ab-45df-bcb5-3a32258ab511', 2, '09:00:00', '17:00:00', 0, '2026-02-08 00:40:37', '2026-02-08 00:40:37'),
(59, '62984122-93ab-45df-bcb5-3a32258ab511', 3, '09:00:00', '17:00:00', 0, '2026-02-08 00:40:37', '2026-02-08 00:40:37'),
(60, '62984122-93ab-45df-bcb5-3a32258ab511', 4, '09:00:00', '17:00:00', 0, '2026-02-08 00:40:37', '2026-02-08 00:40:37'),
(61, '62984122-93ab-45df-bcb5-3a32258ab511', 5, '09:00:00', '17:00:00', 0, '2026-02-08 00:40:37', '2026-02-08 00:40:37'),
(62, '62984122-93ab-45df-bcb5-3a32258ab511', 6, '09:00:00', '17:00:00', 1, '2026-02-08 00:40:37', '2026-02-08 00:40:37'),
(63, '62984122-93ab-45df-bcb5-3a32258ab511', 0, '09:00:00', '17:00:00', 1, '2026-02-08 00:40:37', '2026-02-08 00:40:37'),
(64, 'd5b9c837-3f55-4ba1-9b9a-94e4f146ef42', 1, '09:00:00', '17:00:00', 0, '2026-02-08 00:41:00', '2026-02-08 00:41:00'),
(65, 'd5b9c837-3f55-4ba1-9b9a-94e4f146ef42', 2, '09:00:00', '17:00:00', 0, '2026-02-08 00:41:00', '2026-02-08 00:41:00'),
(66, 'd5b9c837-3f55-4ba1-9b9a-94e4f146ef42', 3, '09:00:00', '17:00:00', 0, '2026-02-08 00:41:00', '2026-02-08 00:41:00'),
(67, 'd5b9c837-3f55-4ba1-9b9a-94e4f146ef42', 4, '09:00:00', '17:00:00', 0, '2026-02-08 00:41:00', '2026-02-08 00:41:00'),
(68, 'd5b9c837-3f55-4ba1-9b9a-94e4f146ef42', 5, '09:00:00', '17:00:00', 0, '2026-02-08 00:41:00', '2026-02-08 00:41:00'),
(69, 'd5b9c837-3f55-4ba1-9b9a-94e4f146ef42', 6, '09:00:00', '17:00:00', 1, '2026-02-08 00:41:00', '2026-02-08 00:41:00'),
(70, 'd5b9c837-3f55-4ba1-9b9a-94e4f146ef42', 0, '09:00:00', '17:00:00', 1, '2026-02-08 00:41:00', '2026-02-08 00:41:00'),
(71, '7f239fef-820c-4d1b-8e13-f778e50568e5', 1, '09:00:00', '17:00:00', 0, '2026-02-08 00:45:38', '2026-02-08 00:45:38'),
(72, '7f239fef-820c-4d1b-8e13-f778e50568e5', 2, '09:00:00', '17:00:00', 0, '2026-02-08 00:45:38', '2026-02-08 00:45:38'),
(73, '7f239fef-820c-4d1b-8e13-f778e50568e5', 3, '09:00:00', '17:00:00', 0, '2026-02-08 00:45:38', '2026-02-08 00:45:38'),
(74, '7f239fef-820c-4d1b-8e13-f778e50568e5', 4, '09:00:00', '17:00:00', 0, '2026-02-08 00:45:38', '2026-02-08 00:45:38'),
(75, '7f239fef-820c-4d1b-8e13-f778e50568e5', 5, '09:00:00', '17:00:00', 0, '2026-02-08 00:45:38', '2026-02-08 00:45:38'),
(76, '7f239fef-820c-4d1b-8e13-f778e50568e5', 6, '09:00:00', '17:00:00', 1, '2026-02-08 00:45:38', '2026-02-08 00:45:38'),
(77, '7f239fef-820c-4d1b-8e13-f778e50568e5', 0, '09:00:00', '17:00:00', 1, '2026-02-08 00:45:38', '2026-02-08 00:45:38'),
(78, 'f3d9eca3-c7a3-4571-82d3-b76cf1d1f1d9', 1, '09:00:00', '17:00:00', 0, '2026-02-08 00:47:24', '2026-02-08 00:47:24'),
(79, 'f3d9eca3-c7a3-4571-82d3-b76cf1d1f1d9', 2, '09:00:00', '17:00:00', 0, '2026-02-08 00:47:24', '2026-02-08 00:47:24'),
(80, 'f3d9eca3-c7a3-4571-82d3-b76cf1d1f1d9', 3, '09:00:00', '17:00:00', 0, '2026-02-08 00:47:24', '2026-02-08 00:47:24'),
(81, 'f3d9eca3-c7a3-4571-82d3-b76cf1d1f1d9', 4, '09:00:00', '17:00:00', 0, '2026-02-08 00:47:24', '2026-02-08 00:47:24'),
(82, 'f3d9eca3-c7a3-4571-82d3-b76cf1d1f1d9', 5, '09:00:00', '17:00:00', 0, '2026-02-08 00:47:24', '2026-02-08 00:47:24'),
(83, 'f3d9eca3-c7a3-4571-82d3-b76cf1d1f1d9', 6, '09:00:00', '17:00:00', 1, '2026-02-08 00:47:24', '2026-02-08 00:47:24'),
(84, 'f3d9eca3-c7a3-4571-82d3-b76cf1d1f1d9', 0, '09:00:00', '17:00:00', 1, '2026-02-08 00:47:24', '2026-02-08 00:47:24'),
(85, '5e7ae24d-fe0c-4829-b893-4eff6ecb2791', 1, '09:00:00', '17:00:00', 0, '2026-02-08 00:52:50', '2026-02-08 00:52:50'),
(86, '5e7ae24d-fe0c-4829-b893-4eff6ecb2791', 2, '09:00:00', '17:00:00', 0, '2026-02-08 00:52:50', '2026-02-08 00:52:50'),
(87, '5e7ae24d-fe0c-4829-b893-4eff6ecb2791', 3, '09:00:00', '17:00:00', 0, '2026-02-08 00:52:50', '2026-02-08 00:52:50'),
(88, '5e7ae24d-fe0c-4829-b893-4eff6ecb2791', 4, '09:00:00', '17:00:00', 0, '2026-02-08 00:52:50', '2026-02-08 00:52:50'),
(89, '5e7ae24d-fe0c-4829-b893-4eff6ecb2791', 5, '09:00:00', '17:00:00', 0, '2026-02-08 00:52:50', '2026-02-08 00:52:50'),
(90, '5e7ae24d-fe0c-4829-b893-4eff6ecb2791', 6, '09:00:00', '17:00:00', 1, '2026-02-08 00:52:50', '2026-02-08 00:52:50'),
(91, '5e7ae24d-fe0c-4829-b893-4eff6ecb2791', 0, '09:00:00', '17:00:00', 1, '2026-02-08 00:52:50', '2026-02-08 00:52:50'),
(92, '6881dd0d-87d1-4a19-b8b8-f6e02e7aec40', 1, '09:00:00', '17:00:00', 0, '2026-02-08 01:02:23', '2026-02-08 01:02:23'),
(93, '6881dd0d-87d1-4a19-b8b8-f6e02e7aec40', 2, '09:00:00', '17:00:00', 0, '2026-02-08 01:02:23', '2026-02-08 01:02:23'),
(94, '6881dd0d-87d1-4a19-b8b8-f6e02e7aec40', 3, '09:00:00', '17:00:00', 0, '2026-02-08 01:02:23', '2026-02-08 01:02:23'),
(95, '6881dd0d-87d1-4a19-b8b8-f6e02e7aec40', 4, '09:00:00', '17:00:00', 0, '2026-02-08 01:02:23', '2026-02-08 01:02:23'),
(96, '6881dd0d-87d1-4a19-b8b8-f6e02e7aec40', 5, '09:00:00', '17:00:00', 0, '2026-02-08 01:02:23', '2026-02-08 01:02:23'),
(97, '6881dd0d-87d1-4a19-b8b8-f6e02e7aec40', 6, '09:00:00', '17:00:00', 1, '2026-02-08 01:02:23', '2026-02-08 01:02:23'),
(98, '6881dd0d-87d1-4a19-b8b8-f6e02e7aec40', 0, '09:00:00', '17:00:00', 1, '2026-02-08 01:02:23', '2026-02-08 01:02:23'),
(99, 'a9cea373-3add-4870-8d20-396244f0c458', 1, '09:00:00', '17:00:00', 0, '2026-02-08 01:02:52', '2026-02-08 01:02:52'),
(100, 'a9cea373-3add-4870-8d20-396244f0c458', 2, '09:00:00', '17:00:00', 0, '2026-02-08 01:02:52', '2026-02-08 01:02:52'),
(101, 'a9cea373-3add-4870-8d20-396244f0c458', 3, '09:00:00', '17:00:00', 0, '2026-02-08 01:02:52', '2026-02-08 01:02:52'),
(102, 'a9cea373-3add-4870-8d20-396244f0c458', 4, '09:00:00', '17:00:00', 0, '2026-02-08 01:02:52', '2026-02-08 01:02:52'),
(103, 'a9cea373-3add-4870-8d20-396244f0c458', 5, '09:00:00', '17:00:00', 0, '2026-02-08 01:02:52', '2026-02-08 01:02:52'),
(104, 'a9cea373-3add-4870-8d20-396244f0c458', 6, '09:00:00', '17:00:00', 1, '2026-02-08 01:02:52', '2026-02-08 01:02:52'),
(105, 'a9cea373-3add-4870-8d20-396244f0c458', 0, '09:00:00', '17:00:00', 1, '2026-02-08 01:02:52', '2026-02-08 01:02:52'),
(106, 'f81b7249-08f9-430f-b06d-f54b5f3ebe4e', 1, '09:00:00', '17:00:00', 0, '2026-02-08 01:04:38', '2026-02-08 01:04:38'),
(107, 'f81b7249-08f9-430f-b06d-f54b5f3ebe4e', 2, '09:00:00', '17:00:00', 0, '2026-02-08 01:04:38', '2026-02-08 01:04:38'),
(108, 'f81b7249-08f9-430f-b06d-f54b5f3ebe4e', 3, '09:00:00', '17:00:00', 0, '2026-02-08 01:04:38', '2026-02-08 01:04:38'),
(109, 'f81b7249-08f9-430f-b06d-f54b5f3ebe4e', 4, '09:00:00', '17:00:00', 0, '2026-02-08 01:04:38', '2026-02-08 01:04:38'),
(110, 'f81b7249-08f9-430f-b06d-f54b5f3ebe4e', 5, '09:00:00', '17:00:00', 0, '2026-02-08 01:04:38', '2026-02-08 01:04:38'),
(111, 'f81b7249-08f9-430f-b06d-f54b5f3ebe4e', 6, '09:00:00', '17:00:00', 1, '2026-02-08 01:04:38', '2026-02-08 01:04:38'),
(112, 'f81b7249-08f9-430f-b06d-f54b5f3ebe4e', 0, '09:00:00', '17:00:00', 1, '2026-02-08 01:04:38', '2026-02-08 01:04:38'),
(113, '55dbc156-4082-46dc-90cd-b543b4f18418', 1, '09:00:00', '17:00:00', 0, '2026-02-08 01:06:59', '2026-02-08 01:06:59'),
(114, '55dbc156-4082-46dc-90cd-b543b4f18418', 2, '09:00:00', '17:00:00', 0, '2026-02-08 01:06:59', '2026-02-08 01:06:59'),
(115, '55dbc156-4082-46dc-90cd-b543b4f18418', 3, '09:00:00', '17:00:00', 0, '2026-02-08 01:06:59', '2026-02-08 01:06:59'),
(116, '55dbc156-4082-46dc-90cd-b543b4f18418', 4, '09:00:00', '17:00:00', 0, '2026-02-08 01:06:59', '2026-02-08 01:06:59'),
(117, '55dbc156-4082-46dc-90cd-b543b4f18418', 5, '09:00:00', '17:00:00', 0, '2026-02-08 01:06:59', '2026-02-08 01:06:59'),
(118, '55dbc156-4082-46dc-90cd-b543b4f18418', 6, '09:00:00', '17:00:00', 1, '2026-02-08 01:06:59', '2026-02-08 01:06:59'),
(119, '55dbc156-4082-46dc-90cd-b543b4f18418', 0, '09:00:00', '17:00:00', 1, '2026-02-08 01:06:59', '2026-02-08 01:06:59'),
(120, 'ecf29eb0-81ce-4a21-8174-15ce00d5d5b0', 1, '09:00:00', '17:00:00', 0, '2026-02-08 01:10:43', '2026-02-08 01:10:43'),
(121, 'ecf29eb0-81ce-4a21-8174-15ce00d5d5b0', 2, '09:00:00', '17:00:00', 0, '2026-02-08 01:10:43', '2026-02-08 01:10:43'),
(122, 'ecf29eb0-81ce-4a21-8174-15ce00d5d5b0', 3, '09:00:00', '17:00:00', 0, '2026-02-08 01:10:43', '2026-02-08 01:10:43'),
(123, 'ecf29eb0-81ce-4a21-8174-15ce00d5d5b0', 4, '09:00:00', '17:00:00', 0, '2026-02-08 01:10:43', '2026-02-08 01:10:43'),
(124, 'ecf29eb0-81ce-4a21-8174-15ce00d5d5b0', 5, '09:00:00', '17:00:00', 0, '2026-02-08 01:10:43', '2026-02-08 01:10:43'),
(125, 'ecf29eb0-81ce-4a21-8174-15ce00d5d5b0', 6, '09:00:00', '17:00:00', 1, '2026-02-08 01:10:43', '2026-02-08 01:10:43'),
(126, 'ecf29eb0-81ce-4a21-8174-15ce00d5d5b0', 0, '09:00:00', '17:00:00', 1, '2026-02-08 01:10:43', '2026-02-08 01:10:43'),
(127, '74824326-6c95-4ff4-83c0-8ac729d97817', 1, '09:00:00', '17:00:00', 0, '2026-02-08 01:16:00', '2026-02-08 01:16:00'),
(128, '74824326-6c95-4ff4-83c0-8ac729d97817', 2, '09:00:00', '17:00:00', 0, '2026-02-08 01:16:00', '2026-02-08 01:16:00'),
(129, '74824326-6c95-4ff4-83c0-8ac729d97817', 3, '09:00:00', '17:00:00', 0, '2026-02-08 01:16:00', '2026-02-08 01:16:00'),
(130, '74824326-6c95-4ff4-83c0-8ac729d97817', 4, '09:00:00', '17:00:00', 0, '2026-02-08 01:16:00', '2026-02-08 01:16:00'),
(131, '74824326-6c95-4ff4-83c0-8ac729d97817', 5, '09:00:00', '17:00:00', 0, '2026-02-08 01:16:00', '2026-02-08 01:16:00'),
(132, '74824326-6c95-4ff4-83c0-8ac729d97817', 6, '09:00:00', '17:00:00', 1, '2026-02-08 01:16:00', '2026-02-08 01:16:00'),
(133, '74824326-6c95-4ff4-83c0-8ac729d97817', 0, '09:00:00', '17:00:00', 1, '2026-02-08 01:16:00', '2026-02-08 01:16:00'),
(134, '39b79652-beef-4877-8bd2-058eab653f6f', 1, '09:00:00', '17:00:00', 0, '2026-02-08 01:16:11', '2026-02-08 01:16:11'),
(135, '39b79652-beef-4877-8bd2-058eab653f6f', 2, '09:00:00', '17:00:00', 0, '2026-02-08 01:16:11', '2026-02-08 01:16:11'),
(136, '39b79652-beef-4877-8bd2-058eab653f6f', 3, '09:00:00', '17:00:00', 0, '2026-02-08 01:16:11', '2026-02-08 01:16:11'),
(137, '39b79652-beef-4877-8bd2-058eab653f6f', 4, '09:00:00', '17:00:00', 0, '2026-02-08 01:16:11', '2026-02-08 01:16:11'),
(138, '39b79652-beef-4877-8bd2-058eab653f6f', 5, '09:00:00', '17:00:00', 0, '2026-02-08 01:16:11', '2026-02-08 01:16:11'),
(139, '39b79652-beef-4877-8bd2-058eab653f6f', 6, '09:00:00', '17:00:00', 1, '2026-02-08 01:16:11', '2026-02-08 01:16:11'),
(140, '39b79652-beef-4877-8bd2-058eab653f6f', 0, '09:00:00', '17:00:00', 1, '2026-02-08 01:16:11', '2026-02-08 01:16:11'),
(141, 'ad61c349-7c87-42ec-9ab4-423f238fad87', 1, '09:00:00', '17:00:00', 0, '2026-02-08 01:19:24', '2026-02-08 01:19:24'),
(142, 'ad61c349-7c87-42ec-9ab4-423f238fad87', 2, '09:00:00', '17:00:00', 0, '2026-02-08 01:19:24', '2026-02-08 01:19:24'),
(143, 'ad61c349-7c87-42ec-9ab4-423f238fad87', 3, '09:00:00', '17:00:00', 0, '2026-02-08 01:19:24', '2026-02-08 01:19:24'),
(144, 'ad61c349-7c87-42ec-9ab4-423f238fad87', 4, '09:00:00', '17:00:00', 0, '2026-02-08 01:19:24', '2026-02-08 01:19:24'),
(145, 'ad61c349-7c87-42ec-9ab4-423f238fad87', 5, '09:00:00', '17:00:00', 0, '2026-02-08 01:19:24', '2026-02-08 01:19:24'),
(146, 'ad61c349-7c87-42ec-9ab4-423f238fad87', 6, '09:00:00', '17:00:00', 1, '2026-02-08 01:19:24', '2026-02-08 01:19:24'),
(147, 'ad61c349-7c87-42ec-9ab4-423f238fad87', 0, '09:00:00', '17:00:00', 1, '2026-02-08 01:19:24', '2026-02-08 01:19:24'),
(148, '96d9c4fa-a5cb-424e-85dc-506842dc2979', 1, '09:00:00', '17:00:00', 0, '2026-02-08 01:19:46', '2026-02-08 01:19:46'),
(149, '96d9c4fa-a5cb-424e-85dc-506842dc2979', 2, '09:00:00', '17:00:00', 0, '2026-02-08 01:19:46', '2026-02-08 01:19:46'),
(150, '96d9c4fa-a5cb-424e-85dc-506842dc2979', 3, '09:00:00', '17:00:00', 0, '2026-02-08 01:19:46', '2026-02-08 01:19:46'),
(151, '96d9c4fa-a5cb-424e-85dc-506842dc2979', 4, '09:00:00', '17:00:00', 0, '2026-02-08 01:19:46', '2026-02-08 01:19:46'),
(152, '96d9c4fa-a5cb-424e-85dc-506842dc2979', 5, '09:00:00', '17:00:00', 0, '2026-02-08 01:19:46', '2026-02-08 01:19:46'),
(153, '96d9c4fa-a5cb-424e-85dc-506842dc2979', 6, '09:00:00', '17:00:00', 1, '2026-02-08 01:19:46', '2026-02-08 01:19:46'),
(154, '96d9c4fa-a5cb-424e-85dc-506842dc2979', 0, '09:00:00', '17:00:00', 1, '2026-02-08 01:19:46', '2026-02-08 01:19:46'),
(155, '06e975df-9320-4b1a-af53-b5c8f6373b39', 1, '09:00:00', '17:00:00', 0, '2026-02-08 01:20:45', '2026-02-08 01:20:45'),
(156, '06e975df-9320-4b1a-af53-b5c8f6373b39', 2, '09:00:00', '17:00:00', 0, '2026-02-08 01:20:45', '2026-02-08 01:20:45'),
(157, '06e975df-9320-4b1a-af53-b5c8f6373b39', 3, '09:00:00', '17:00:00', 0, '2026-02-08 01:20:45', '2026-02-08 01:20:45'),
(158, '06e975df-9320-4b1a-af53-b5c8f6373b39', 4, '09:00:00', '17:00:00', 0, '2026-02-08 01:20:45', '2026-02-08 01:20:45'),
(159, '06e975df-9320-4b1a-af53-b5c8f6373b39', 5, '09:00:00', '17:00:00', 0, '2026-02-08 01:20:45', '2026-02-08 01:20:45'),
(160, '06e975df-9320-4b1a-af53-b5c8f6373b39', 6, '09:00:00', '17:00:00', 1, '2026-02-08 01:20:45', '2026-02-08 01:20:45'),
(161, '06e975df-9320-4b1a-af53-b5c8f6373b39', 0, '09:00:00', '17:00:00', 1, '2026-02-08 01:20:45', '2026-02-08 01:20:45'),
(162, '468070bb-5b1b-49bf-8e43-2265c4be08ef', 1, '09:00:00', '17:00:00', 0, '2026-02-08 01:21:59', '2026-02-08 01:21:59'),
(163, '468070bb-5b1b-49bf-8e43-2265c4be08ef', 2, '09:00:00', '17:00:00', 0, '2026-02-08 01:21:59', '2026-02-08 01:21:59'),
(164, '468070bb-5b1b-49bf-8e43-2265c4be08ef', 3, '09:00:00', '17:00:00', 0, '2026-02-08 01:21:59', '2026-02-08 01:21:59'),
(165, '468070bb-5b1b-49bf-8e43-2265c4be08ef', 4, '09:00:00', '17:00:00', 0, '2026-02-08 01:21:59', '2026-02-08 01:21:59'),
(166, '468070bb-5b1b-49bf-8e43-2265c4be08ef', 5, '09:00:00', '17:00:00', 0, '2026-02-08 01:21:59', '2026-02-08 01:21:59'),
(167, '468070bb-5b1b-49bf-8e43-2265c4be08ef', 6, '09:00:00', '17:00:00', 1, '2026-02-08 01:21:59', '2026-02-08 01:21:59'),
(168, '468070bb-5b1b-49bf-8e43-2265c4be08ef', 0, '09:00:00', '17:00:00', 1, '2026-02-08 01:21:59', '2026-02-08 01:21:59'),
(169, '85093754-da13-4d21-b53d-f76226b83e5e', 1, '09:00:00', '17:00:00', 0, '2026-02-08 01:22:10', '2026-02-08 01:22:10'),
(170, '85093754-da13-4d21-b53d-f76226b83e5e', 2, '09:00:00', '17:00:00', 0, '2026-02-08 01:22:10', '2026-02-08 01:22:10'),
(171, '85093754-da13-4d21-b53d-f76226b83e5e', 3, '09:00:00', '17:00:00', 0, '2026-02-08 01:22:10', '2026-02-08 01:22:10'),
(172, '85093754-da13-4d21-b53d-f76226b83e5e', 4, '09:00:00', '17:00:00', 0, '2026-02-08 01:22:10', '2026-02-08 01:22:10'),
(173, '85093754-da13-4d21-b53d-f76226b83e5e', 5, '09:00:00', '17:00:00', 0, '2026-02-08 01:22:10', '2026-02-08 01:22:10'),
(174, '85093754-da13-4d21-b53d-f76226b83e5e', 6, '09:00:00', '17:00:00', 1, '2026-02-08 01:22:10', '2026-02-08 01:22:10'),
(175, '85093754-da13-4d21-b53d-f76226b83e5e', 0, '09:00:00', '17:00:00', 1, '2026-02-08 01:22:10', '2026-02-08 01:22:10'),
(176, '38dc83c7-7eed-4fe8-86ba-cab830e47b7f', 1, '09:00:00', '17:00:00', 0, '2026-02-08 01:25:03', '2026-02-08 01:25:03'),
(177, '38dc83c7-7eed-4fe8-86ba-cab830e47b7f', 2, '09:00:00', '17:00:00', 0, '2026-02-08 01:25:03', '2026-02-08 01:25:03'),
(178, '38dc83c7-7eed-4fe8-86ba-cab830e47b7f', 3, '09:00:00', '17:00:00', 0, '2026-02-08 01:25:03', '2026-02-08 01:25:03'),
(179, '38dc83c7-7eed-4fe8-86ba-cab830e47b7f', 4, '09:00:00', '17:00:00', 0, '2026-02-08 01:25:03', '2026-02-08 01:25:03'),
(180, '38dc83c7-7eed-4fe8-86ba-cab830e47b7f', 5, '09:00:00', '17:00:00', 0, '2026-02-08 01:25:03', '2026-02-08 01:25:03'),
(181, '38dc83c7-7eed-4fe8-86ba-cab830e47b7f', 6, '09:00:00', '17:00:00', 1, '2026-02-08 01:25:03', '2026-02-08 01:25:03'),
(182, '38dc83c7-7eed-4fe8-86ba-cab830e47b7f', 0, '09:00:00', '17:00:00', 1, '2026-02-08 01:25:03', '2026-02-08 01:25:03'),
(183, '67d978be-f571-4ecf-b939-997daa4c16a6', 1, '09:00:00', '17:00:00', 0, '2026-02-08 01:26:14', '2026-02-08 01:26:14'),
(184, '67d978be-f571-4ecf-b939-997daa4c16a6', 2, '09:00:00', '17:00:00', 0, '2026-02-08 01:26:14', '2026-02-08 01:26:14'),
(185, '67d978be-f571-4ecf-b939-997daa4c16a6', 3, '09:00:00', '17:00:00', 0, '2026-02-08 01:26:14', '2026-02-08 01:26:14'),
(186, '67d978be-f571-4ecf-b939-997daa4c16a6', 4, '09:00:00', '17:00:00', 0, '2026-02-08 01:26:14', '2026-02-08 01:26:14'),
(187, '67d978be-f571-4ecf-b939-997daa4c16a6', 5, '09:00:00', '17:00:00', 0, '2026-02-08 01:26:14', '2026-02-08 01:26:14'),
(188, '67d978be-f571-4ecf-b939-997daa4c16a6', 6, '09:00:00', '17:00:00', 1, '2026-02-08 01:26:14', '2026-02-08 01:26:14'),
(189, '67d978be-f571-4ecf-b939-997daa4c16a6', 0, '09:00:00', '17:00:00', 1, '2026-02-08 01:26:14', '2026-02-08 01:26:14'),
(190, 'eaf79b81-447a-4a7a-8a08-f26571f298b1', 1, '09:00:00', '17:00:00', 0, '2026-02-08 01:28:08', '2026-02-08 01:28:08'),
(191, 'eaf79b81-447a-4a7a-8a08-f26571f298b1', 2, '09:00:00', '17:00:00', 0, '2026-02-08 01:28:08', '2026-02-08 01:28:08'),
(192, 'eaf79b81-447a-4a7a-8a08-f26571f298b1', 3, '09:00:00', '17:00:00', 0, '2026-02-08 01:28:08', '2026-02-08 01:28:08'),
(193, 'eaf79b81-447a-4a7a-8a08-f26571f298b1', 4, '09:00:00', '17:00:00', 0, '2026-02-08 01:28:08', '2026-02-08 01:28:08'),
(194, 'eaf79b81-447a-4a7a-8a08-f26571f298b1', 5, '09:00:00', '17:00:00', 0, '2026-02-08 01:28:08', '2026-02-08 01:28:08'),
(195, 'eaf79b81-447a-4a7a-8a08-f26571f298b1', 6, '09:00:00', '17:00:00', 1, '2026-02-08 01:28:08', '2026-02-08 01:28:08'),
(196, 'eaf79b81-447a-4a7a-8a08-f26571f298b1', 0, '09:00:00', '17:00:00', 1, '2026-02-08 01:28:08', '2026-02-08 01:28:08'),
(197, 'f8886dc8-36c1-4674-8aa3-cdf998cf32ce', 1, '09:00:00', '17:00:00', 0, '2026-02-08 01:28:53', '2026-02-08 01:28:53'),
(198, 'f8886dc8-36c1-4674-8aa3-cdf998cf32ce', 2, '09:00:00', '17:00:00', 0, '2026-02-08 01:28:53', '2026-02-08 01:28:53'),
(199, 'f8886dc8-36c1-4674-8aa3-cdf998cf32ce', 3, '09:00:00', '17:00:00', 0, '2026-02-08 01:28:53', '2026-02-08 01:28:53'),
(200, 'f8886dc8-36c1-4674-8aa3-cdf998cf32ce', 4, '09:00:00', '17:00:00', 0, '2026-02-08 01:28:53', '2026-02-08 01:28:53'),
(201, 'f8886dc8-36c1-4674-8aa3-cdf998cf32ce', 5, '09:00:00', '17:00:00', 0, '2026-02-08 01:28:53', '2026-02-08 01:28:53'),
(202, 'f8886dc8-36c1-4674-8aa3-cdf998cf32ce', 6, '09:00:00', '17:00:00', 1, '2026-02-08 01:28:53', '2026-02-08 01:28:53'),
(203, 'f8886dc8-36c1-4674-8aa3-cdf998cf32ce', 0, '09:00:00', '17:00:00', 1, '2026-02-08 01:28:53', '2026-02-08 01:28:53'),
(204, 'bd5f6b10-1640-4fe0-98a3-00f2eebf48f6', 1, '09:00:00', '17:00:00', 0, '2026-02-08 01:29:07', '2026-02-08 01:29:07'),
(205, 'bd5f6b10-1640-4fe0-98a3-00f2eebf48f6', 2, '09:00:00', '17:00:00', 0, '2026-02-08 01:29:07', '2026-02-08 01:29:07'),
(206, 'bd5f6b10-1640-4fe0-98a3-00f2eebf48f6', 3, '09:00:00', '17:00:00', 0, '2026-02-08 01:29:07', '2026-02-08 01:29:07'),
(207, 'bd5f6b10-1640-4fe0-98a3-00f2eebf48f6', 4, '09:00:00', '17:00:00', 0, '2026-02-08 01:29:07', '2026-02-08 01:29:07'),
(208, 'bd5f6b10-1640-4fe0-98a3-00f2eebf48f6', 5, '09:00:00', '17:00:00', 0, '2026-02-08 01:29:07', '2026-02-08 01:29:07'),
(209, 'bd5f6b10-1640-4fe0-98a3-00f2eebf48f6', 6, '09:00:00', '17:00:00', 1, '2026-02-08 01:29:07', '2026-02-08 01:29:07'),
(210, 'bd5f6b10-1640-4fe0-98a3-00f2eebf48f6', 0, '09:00:00', '17:00:00', 1, '2026-02-08 01:29:07', '2026-02-08 01:29:07'),
(211, 'fe50f29d-ddb5-4e6f-80e3-47cb34893a07', 1, '09:00:00', '17:00:00', 0, '2026-02-08 01:29:12', '2026-02-08 01:29:12'),
(212, 'fe50f29d-ddb5-4e6f-80e3-47cb34893a07', 2, '09:00:00', '17:00:00', 0, '2026-02-08 01:29:12', '2026-02-08 01:29:12'),
(213, 'fe50f29d-ddb5-4e6f-80e3-47cb34893a07', 3, '09:00:00', '17:00:00', 0, '2026-02-08 01:29:12', '2026-02-08 01:29:12'),
(214, 'fe50f29d-ddb5-4e6f-80e3-47cb34893a07', 4, '09:00:00', '17:00:00', 0, '2026-02-08 01:29:12', '2026-02-08 01:29:12'),
(215, 'fe50f29d-ddb5-4e6f-80e3-47cb34893a07', 5, '09:00:00', '17:00:00', 0, '2026-02-08 01:29:12', '2026-02-08 01:29:12'),
(216, 'fe50f29d-ddb5-4e6f-80e3-47cb34893a07', 6, '09:00:00', '17:00:00', 1, '2026-02-08 01:29:12', '2026-02-08 01:29:12'),
(217, 'fe50f29d-ddb5-4e6f-80e3-47cb34893a07', 0, '09:00:00', '17:00:00', 1, '2026-02-08 01:29:12', '2026-02-08 01:29:12'),
(218, '99cc4e46-477f-49ae-8716-ec4ac94a83f3', 1, '09:00:00', '17:00:00', 0, '2026-02-08 01:29:13', '2026-02-08 01:29:13'),
(219, '99cc4e46-477f-49ae-8716-ec4ac94a83f3', 2, '09:00:00', '17:00:00', 0, '2026-02-08 01:29:13', '2026-02-08 01:29:13'),
(220, '99cc4e46-477f-49ae-8716-ec4ac94a83f3', 3, '09:00:00', '17:00:00', 0, '2026-02-08 01:29:13', '2026-02-08 01:29:13'),
(221, '99cc4e46-477f-49ae-8716-ec4ac94a83f3', 4, '09:00:00', '17:00:00', 0, '2026-02-08 01:29:13', '2026-02-08 01:29:13'),
(222, '99cc4e46-477f-49ae-8716-ec4ac94a83f3', 5, '09:00:00', '17:00:00', 0, '2026-02-08 01:29:13', '2026-02-08 01:29:13'),
(223, '99cc4e46-477f-49ae-8716-ec4ac94a83f3', 6, '09:00:00', '17:00:00', 1, '2026-02-08 01:29:13', '2026-02-08 01:29:13'),
(224, '99cc4e46-477f-49ae-8716-ec4ac94a83f3', 0, '09:00:00', '17:00:00', 1, '2026-02-08 01:29:13', '2026-02-08 01:29:13'),
(225, '1209d5a1-5ffa-4f77-b073-99f7098ae404', 1, '09:00:00', '17:00:00', 0, '2026-02-08 01:30:11', '2026-02-08 01:30:11'),
(226, '1209d5a1-5ffa-4f77-b073-99f7098ae404', 2, '09:00:00', '17:00:00', 0, '2026-02-08 01:30:11', '2026-02-08 01:30:11'),
(227, '1209d5a1-5ffa-4f77-b073-99f7098ae404', 3, '09:00:00', '17:00:00', 0, '2026-02-08 01:30:11', '2026-02-08 01:30:11'),
(228, '1209d5a1-5ffa-4f77-b073-99f7098ae404', 4, '09:00:00', '17:00:00', 0, '2026-02-08 01:30:11', '2026-02-08 01:30:11'),
(229, '1209d5a1-5ffa-4f77-b073-99f7098ae404', 5, '09:00:00', '17:00:00', 0, '2026-02-08 01:30:11', '2026-02-08 01:30:11'),
(230, '1209d5a1-5ffa-4f77-b073-99f7098ae404', 6, '09:00:00', '17:00:00', 1, '2026-02-08 01:30:11', '2026-02-08 01:30:11'),
(231, '1209d5a1-5ffa-4f77-b073-99f7098ae404', 0, '09:00:00', '17:00:00', 1, '2026-02-08 01:30:11', '2026-02-08 01:30:11'),
(232, '1d26abd4-7ab2-49e4-9feb-decb8767a1d9', 1, '09:00:00', '17:00:00', 0, '2026-02-08 01:30:41', '2026-02-08 01:30:41'),
(233, '1d26abd4-7ab2-49e4-9feb-decb8767a1d9', 2, '09:00:00', '17:00:00', 0, '2026-02-08 01:30:41', '2026-02-08 01:30:41'),
(234, '1d26abd4-7ab2-49e4-9feb-decb8767a1d9', 3, '09:00:00', '17:00:00', 0, '2026-02-08 01:30:41', '2026-02-08 01:30:41'),
(235, '1d26abd4-7ab2-49e4-9feb-decb8767a1d9', 4, '09:00:00', '17:00:00', 0, '2026-02-08 01:30:41', '2026-02-08 01:30:41'),
(236, '1d26abd4-7ab2-49e4-9feb-decb8767a1d9', 5, '09:00:00', '17:00:00', 0, '2026-02-08 01:30:41', '2026-02-08 01:30:41'),
(237, '1d26abd4-7ab2-49e4-9feb-decb8767a1d9', 6, '09:00:00', '17:00:00', 1, '2026-02-08 01:30:41', '2026-02-08 01:30:41'),
(238, '1d26abd4-7ab2-49e4-9feb-decb8767a1d9', 0, '09:00:00', '17:00:00', 1, '2026-02-08 01:30:41', '2026-02-08 01:30:41'),
(239, '76428452-698b-4516-80ff-1f8223faf4e2', 1, '09:00:00', '17:00:00', 0, '2026-02-08 01:31:23', '2026-02-08 01:31:23'),
(240, '76428452-698b-4516-80ff-1f8223faf4e2', 2, '09:00:00', '17:00:00', 0, '2026-02-08 01:31:23', '2026-02-08 01:31:23'),
(241, '76428452-698b-4516-80ff-1f8223faf4e2', 3, '09:00:00', '17:00:00', 0, '2026-02-08 01:31:23', '2026-02-08 01:31:23'),
(242, '76428452-698b-4516-80ff-1f8223faf4e2', 4, '09:00:00', '17:00:00', 0, '2026-02-08 01:31:23', '2026-02-08 01:31:23'),
(243, '76428452-698b-4516-80ff-1f8223faf4e2', 5, '09:00:00', '17:00:00', 0, '2026-02-08 01:31:23', '2026-02-08 01:31:23'),
(244, '76428452-698b-4516-80ff-1f8223faf4e2', 6, '09:00:00', '17:00:00', 1, '2026-02-08 01:31:23', '2026-02-08 01:31:23'),
(245, '76428452-698b-4516-80ff-1f8223faf4e2', 0, '09:00:00', '17:00:00', 1, '2026-02-08 01:31:23', '2026-02-08 01:31:23'),
(246, 'a9abfce0-b6b0-41d0-be5d-8ce27662bace', 1, '09:00:00', '17:00:00', 0, '2026-02-08 01:31:46', '2026-02-08 01:31:46'),
(247, 'a9abfce0-b6b0-41d0-be5d-8ce27662bace', 2, '09:00:00', '17:00:00', 0, '2026-02-08 01:31:46', '2026-02-08 01:31:46'),
(248, 'a9abfce0-b6b0-41d0-be5d-8ce27662bace', 3, '09:00:00', '17:00:00', 0, '2026-02-08 01:31:46', '2026-02-08 01:31:46'),
(249, 'a9abfce0-b6b0-41d0-be5d-8ce27662bace', 4, '09:00:00', '17:00:00', 0, '2026-02-08 01:31:46', '2026-02-08 01:31:46'),
(250, 'a9abfce0-b6b0-41d0-be5d-8ce27662bace', 5, '09:00:00', '17:00:00', 0, '2026-02-08 01:31:46', '2026-02-08 01:31:46'),
(251, 'a9abfce0-b6b0-41d0-be5d-8ce27662bace', 6, '09:00:00', '17:00:00', 1, '2026-02-08 01:31:46', '2026-02-08 01:31:46'),
(252, 'a9abfce0-b6b0-41d0-be5d-8ce27662bace', 0, '09:00:00', '17:00:00', 1, '2026-02-08 01:31:46', '2026-02-08 01:31:46'),
(253, 'eb379eb9-994b-466e-95fb-ce9344b37311', 1, '09:00:00', '17:00:00', 0, '2026-02-08 01:33:12', '2026-02-08 01:33:12'),
(254, 'eb379eb9-994b-466e-95fb-ce9344b37311', 2, '09:00:00', '17:00:00', 0, '2026-02-08 01:33:12', '2026-02-08 01:33:12'),
(255, 'eb379eb9-994b-466e-95fb-ce9344b37311', 3, '09:00:00', '17:00:00', 0, '2026-02-08 01:33:12', '2026-02-08 01:33:12'),
(256, 'eb379eb9-994b-466e-95fb-ce9344b37311', 4, '09:00:00', '17:00:00', 0, '2026-02-08 01:33:12', '2026-02-08 01:33:12'),
(257, 'eb379eb9-994b-466e-95fb-ce9344b37311', 5, '09:00:00', '17:00:00', 0, '2026-02-08 01:33:12', '2026-02-08 01:33:12'),
(258, 'eb379eb9-994b-466e-95fb-ce9344b37311', 6, '09:00:00', '17:00:00', 1, '2026-02-08 01:33:12', '2026-02-08 01:33:12'),
(259, 'eb379eb9-994b-466e-95fb-ce9344b37311', 0, '09:00:00', '17:00:00', 1, '2026-02-08 01:33:12', '2026-02-08 01:33:12'),
(260, 'e608bf43-4e36-4823-8fda-b46399ad160f', 1, '09:00:00', '17:00:00', 0, '2026-02-08 01:33:33', '2026-02-08 01:33:33'),
(261, 'e608bf43-4e36-4823-8fda-b46399ad160f', 2, '09:00:00', '17:00:00', 0, '2026-02-08 01:33:33', '2026-02-08 01:33:33'),
(262, 'e608bf43-4e36-4823-8fda-b46399ad160f', 3, '09:00:00', '17:00:00', 0, '2026-02-08 01:33:33', '2026-02-08 01:33:33'),
(263, 'e608bf43-4e36-4823-8fda-b46399ad160f', 4, '09:00:00', '17:00:00', 0, '2026-02-08 01:33:33', '2026-02-08 01:33:33'),
(264, 'e608bf43-4e36-4823-8fda-b46399ad160f', 5, '09:00:00', '17:00:00', 0, '2026-02-08 01:33:33', '2026-02-08 01:33:33'),
(265, 'e608bf43-4e36-4823-8fda-b46399ad160f', 6, '09:00:00', '17:00:00', 1, '2026-02-08 01:33:33', '2026-02-08 01:33:33'),
(266, 'e608bf43-4e36-4823-8fda-b46399ad160f', 0, '09:00:00', '17:00:00', 1, '2026-02-08 01:33:33', '2026-02-08 01:33:33'),
(267, 'ee205576-2965-4202-b8a6-5f3f43c8e31a', 1, '09:00:00', '17:00:00', 0, '2026-02-08 01:33:52', '2026-02-08 01:33:52'),
(268, 'ee205576-2965-4202-b8a6-5f3f43c8e31a', 2, '09:00:00', '17:00:00', 0, '2026-02-08 01:33:52', '2026-02-08 01:33:52'),
(269, 'ee205576-2965-4202-b8a6-5f3f43c8e31a', 3, '09:00:00', '17:00:00', 0, '2026-02-08 01:33:52', '2026-02-08 01:33:52'),
(270, 'ee205576-2965-4202-b8a6-5f3f43c8e31a', 4, '09:00:00', '17:00:00', 0, '2026-02-08 01:33:52', '2026-02-08 01:33:52'),
(271, 'ee205576-2965-4202-b8a6-5f3f43c8e31a', 5, '09:00:00', '17:00:00', 0, '2026-02-08 01:33:52', '2026-02-08 01:33:52'),
(272, 'ee205576-2965-4202-b8a6-5f3f43c8e31a', 6, '09:00:00', '17:00:00', 1, '2026-02-08 01:33:52', '2026-02-08 01:33:52'),
(273, 'ee205576-2965-4202-b8a6-5f3f43c8e31a', 0, '09:00:00', '17:00:00', 1, '2026-02-08 01:33:52', '2026-02-08 01:33:52'),
(274, '8820cda8-50d1-4617-b741-f91ae252d253', 1, '09:00:00', '17:00:00', 0, '2026-02-08 01:33:58', '2026-02-08 01:33:58'),
(275, '8820cda8-50d1-4617-b741-f91ae252d253', 2, '09:00:00', '17:00:00', 0, '2026-02-08 01:33:58', '2026-02-08 01:33:58'),
(276, '8820cda8-50d1-4617-b741-f91ae252d253', 3, '09:00:00', '17:00:00', 0, '2026-02-08 01:33:58', '2026-02-08 01:33:58'),
(277, '8820cda8-50d1-4617-b741-f91ae252d253', 4, '09:00:00', '17:00:00', 0, '2026-02-08 01:33:58', '2026-02-08 01:33:58'),
(278, '8820cda8-50d1-4617-b741-f91ae252d253', 5, '09:00:00', '17:00:00', 0, '2026-02-08 01:33:58', '2026-02-08 01:33:58'),
(279, '8820cda8-50d1-4617-b741-f91ae252d253', 6, '09:00:00', '17:00:00', 1, '2026-02-08 01:33:58', '2026-02-08 01:33:58'),
(280, '8820cda8-50d1-4617-b741-f91ae252d253', 0, '09:00:00', '17:00:00', 1, '2026-02-08 01:33:58', '2026-02-08 01:33:58'),
(281, '22507230-3892-4e78-9681-81c0ec94d7d0', 1, '09:00:00', '17:00:00', 0, '2026-02-08 01:34:13', '2026-02-08 01:34:13'),
(282, '22507230-3892-4e78-9681-81c0ec94d7d0', 2, '09:00:00', '17:00:00', 0, '2026-02-08 01:34:13', '2026-02-08 01:34:13'),
(283, '22507230-3892-4e78-9681-81c0ec94d7d0', 3, '09:00:00', '17:00:00', 0, '2026-02-08 01:34:13', '2026-02-08 01:34:13'),
(284, '22507230-3892-4e78-9681-81c0ec94d7d0', 4, '09:00:00', '17:00:00', 0, '2026-02-08 01:34:13', '2026-02-08 01:34:13'),
(285, '22507230-3892-4e78-9681-81c0ec94d7d0', 5, '09:00:00', '17:00:00', 0, '2026-02-08 01:34:13', '2026-02-08 01:34:13'),
(286, '22507230-3892-4e78-9681-81c0ec94d7d0', 6, '09:00:00', '17:00:00', 1, '2026-02-08 01:34:13', '2026-02-08 01:34:13'),
(287, '22507230-3892-4e78-9681-81c0ec94d7d0', 0, '09:00:00', '17:00:00', 1, '2026-02-08 01:34:13', '2026-02-08 01:34:13');

-- --------------------------------------------------------

--
-- Table structure for table `store_store_category`
--

CREATE TABLE `store_store_category` (
  `store_id` char(36) NOT NULL,
  `store_category_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `store_store_category`
--

INSERT INTO `store_store_category` (`store_id`, `store_category_id`) VALUES
('06e975df-9320-4b1a-af53-b5c8f6373b39', 2),
('1209d5a1-5ffa-4f77-b073-99f7098ae404', 2),
('1d26abd4-7ab2-49e4-9feb-decb8767a1d9', 2),
('219f77bc-bb40-4121-9959-df510c83f90a', 2),
('22507230-3892-4e78-9681-81c0ec94d7d0', 2),
('2c45144b-9bcd-4b01-ace8-9133683cbf34', 2),
('38dc83c7-7eed-4fe8-86ba-cab830e47b7f', 2),
('39b79652-beef-4877-8bd2-058eab653f6f', 2),
('468070bb-5b1b-49bf-8e43-2265c4be08ef', 2),
('55dbc156-4082-46dc-90cd-b543b4f18418', 2),
('5e7ae24d-fe0c-4829-b893-4eff6ecb2791', 2),
('61df8a7d-2493-4911-b3ec-8f9d9fcb6e69', 2),
('62984122-93ab-45df-bcb5-3a32258ab511', 2),
('67d978be-f571-4ecf-b939-997daa4c16a6', 2),
('6881dd0d-87d1-4a19-b8b8-f6e02e7aec40', 2),
('6df8be66-bac3-4b9a-b51f-7c3c23fb8481', 2),
('74824326-6c95-4ff4-83c0-8ac729d97817', 2),
('76428452-698b-4516-80ff-1f8223faf4e2', 2),
('7f239fef-820c-4d1b-8e13-f778e50568e5', 2),
('8083ad58-00c2-49f0-b282-7dcd39beb107', 2),
('85093754-da13-4d21-b53d-f76226b83e5e', 2),
('8820cda8-50d1-4617-b741-f91ae252d253', 2),
('96d9c4fa-a5cb-424e-85dc-506842dc2979', 2),
('99cc4e46-477f-49ae-8716-ec4ac94a83f3', 2),
('a382425d-15bb-4e35-9988-5a8f4f99fe72', 2),
('a9abfce0-b6b0-41d0-be5d-8ce27662bace', 2),
('a9cea373-3add-4870-8d20-396244f0c458', 2),
('ad61c349-7c87-42ec-9ab4-423f238fad87', 2),
('bd5f6b10-1640-4fe0-98a3-00f2eebf48f6', 2),
('bed4702c-de6b-4dbd-a9fc-663f405e09f7', 2),
('d5b9c837-3f55-4ba1-9b9a-94e4f146ef42', 2),
('da952527-786d-4073-b8b2-753b64dcc99b', 2),
('e608bf43-4e36-4823-8fda-b46399ad160f', 2),
('eaf79b81-447a-4a7a-8a08-f26571f298b1', 2),
('eb379eb9-994b-466e-95fb-ce9344b37311', 2),
('ecf29eb0-81ce-4a21-8174-15ce00d5d5b0', 2),
('ee205576-2965-4202-b8a6-5f3f43c8e31a', 2),
('f3d9eca3-c7a3-4571-82d3-b76cf1d1f1d9', 2),
('f81b7249-08f9-430f-b06d-f54b5f3ebe4e', 2),
('f8886dc8-36c1-4674-8aa3-cdf998cf32ce', 2),
('fe50f29d-ddb5-4e6f-80e3-47cb34893a07', 2);

-- --------------------------------------------------------

--
-- Table structure for table `store_verifications`
--

CREATE TABLE `store_verifications` (
  `id` char(36) NOT NULL,
  `store_id` char(36) NOT NULL,
  `document_type` varchar(255) NOT NULL,
  `document_path` varchar(255) NOT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `reviewed_by` char(36) DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `store_verifications`
--

INSERT INTO `store_verifications` (`id`, `store_id`, `document_type`, `document_path`, `status`, `reviewed_by`, `reviewed_at`, `rejection_reason`, `created_at`, `updated_at`) VALUES
('01ca8a66-a177-4bc4-95b6-b4dfeadbfb08', '6881dd0d-87d1-4a19-b8b8-f6e02e7aec40', 'id_card_front', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/id_card_front/IaU05sPRmI8KwGmIO5HkdluTESOL7tQ30bTsvYjb.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 01:02:23', '2026-02-08 01:02:23'),
('02053cbb-21d4-4822-8c20-237f9fd17597', '85093754-da13-4d21-b53d-f76226b83e5e', 'id_card_front', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/id_card_front/IxJ6kEhvUn5RW5WDmt9CUOXu6PxIZYa1mV0wPTfI.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 01:22:10', '2026-02-08 01:22:10'),
('027e7bb5-0306-47e5-98c8-81ba00633a80', 'a382425d-15bb-4e35-9988-5a8f4f99fe72', 'commercial_register', 'stores/verifications/IJAiPL7uzAm1LqYBLEjTgyfl3LI2orNJj0yQaqVE.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 00:17:55', '2026-02-08 00:17:55'),
('03fd18ea-3499-4fc9-8a15-a85f78e7f333', '6df8be66-bac3-4b9a-b51f-7c3c23fb8481', 'id_card_back', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539HM/id_card_back/SnLgMopr6PT0VVZvSG1ZTIMgCSKO3HvMal2Fo2fW.pdf', 'pending', NULL, NULL, NULL, '2026-02-08 00:25:06', '2026-02-08 00:25:06'),
('04485db9-4cf2-43d0-bd8f-5623bda2845a', 'eb379eb9-994b-466e-95fb-ce9344b37311', 'tax_card', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/tax_card/pW4Yldq4H1MS0BiAmSz8IWUPMM7RpxYPTyj51Q2s.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 01:33:12', '2026-02-08 01:33:12'),
('071f4d3f-b04d-4ca3-a769-885a7d2f009a', 'f3d9eca3-c7a3-4571-82d3-b76cf1d1f1d9', 'id_card_front', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/id_card_front/TdTuXL7J4zk7gjbF9qZijYV4eRSMK34Py6lXmiN9.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 00:47:24', '2026-02-08 00:47:24'),
('074f41ea-1b4e-425d-95f7-7092f34fd951', '62984122-93ab-45df-bcb5-3a32258ab511', 'commercial_register', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/commercial_register/ThYe0VbpOOZtnvY15BwpvzMWhG3drm4nJzcezAls.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 00:40:37', '2026-02-08 00:40:37'),
('0914f526-87a6-4220-aaa2-56ab18e79c17', 'f8886dc8-36c1-4674-8aa3-cdf998cf32ce', 'id_card_front', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/id_card_front/4vC9hOVdotofSb6ttelAniGyMtY9Fmx2FInGUXNE.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 01:28:53', '2026-02-08 01:28:53'),
('0bdd3f0b-a559-40a2-a38a-f36e2ec0d17d', '6881dd0d-87d1-4a19-b8b8-f6e02e7aec40', 'tax_card', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/tax_card/wDtMZwie9khQd7VzdB38jCURbTnc5yZRyzIvh0mX.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 01:02:23', '2026-02-08 01:02:23'),
('0bf06741-e55e-4890-8a6f-00633827daac', '6df8be66-bac3-4b9a-b51f-7c3c23fb8481', 'id_card_front', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539HM/id_card_front/UsHlhBwplOTAzteIurCcRus7zFFeJZbuNl3E5Fm5.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 00:25:06', '2026-02-08 00:25:06'),
('0c31287e-fc42-43e8-8add-84d7dd9c2368', '85093754-da13-4d21-b53d-f76226b83e5e', 'commercial_register', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/commercial_register/XwJGcCY4OEEbOVijvJLw4PgelonNs8v7hhwGbrl5.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 01:22:10', '2026-02-08 01:22:10'),
('0d8107bd-548d-43ec-9efa-d303d44e22b5', '2c45144b-9bcd-4b01-ace8-9133683cbf34', 'id_card_back', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/id_card_back/C1jpTpEo290xmyCbIrHQDOPQZ9C0vPDpQZILuOKp.pdf', 'pending', NULL, NULL, NULL, '2026-02-08 00:39:45', '2026-02-08 00:39:45'),
('1021f083-bb62-4ccf-a534-720b4d52191c', '219f77bc-bb40-4121-9959-df510c83f90a', 'id_card_back', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/id_card_back/TBKZNSdzBRjGwOWfRpD3unYLWIAe59dVbaPxKiCp.pdf', 'pending', NULL, NULL, NULL, '2026-02-08 00:38:58', '2026-02-08 00:38:58'),
('11381aba-7d5a-4208-b21d-eb3bdfe9f354', 'f81b7249-08f9-430f-b06d-f54b5f3ebe4e', 'id_card_front', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/id_card_front/IXXyTnbcpWysXutHc401kcWgPzEA3AjUROPrxq1P.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 01:04:38', '2026-02-08 01:04:38'),
('130748cb-c7e5-43f1-a2f0-e874af3ee245', 'da952527-786d-4073-b8b2-753b64dcc99b', 'tax_card', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/tax_card/Otqpr2YfixFyWzQEHTJc2IYXSlGrQUWvinHzuSE1.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 00:38:20', '2026-02-08 00:38:20'),
('134a50af-f880-43c2-b087-ace5fb06bf1b', '39b79652-beef-4877-8bd2-058eab653f6f', 'tax_card', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/tax_card/mwfa3zZB66Cf51JMWCEIEbECe0MekhTBHUOZte0h.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 01:16:11', '2026-02-08 01:16:11'),
('178164b0-b88c-42b4-adb3-e570c1851171', 'f8886dc8-36c1-4674-8aa3-cdf998cf32ce', 'tax_card', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/tax_card/1wNffKQSjuvYHRbJumV9JSuYgpQfYQxlua3BT6or.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 01:28:53', '2026-02-08 01:28:53'),
('18184e2d-2471-4b30-8300-4e48114a1952', '76428452-698b-4516-80ff-1f8223faf4e2', 'id_card_back', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/id_card_back/W1lRVb6GF5XPr8EqFGMjqysIaH4ZIalfeIIt9gTp.pdf', 'pending', NULL, NULL, NULL, '2026-02-08 01:31:23', '2026-02-08 01:31:23'),
('1b7b44e2-c928-47d3-8847-fbba38f63b02', '2c45144b-9bcd-4b01-ace8-9133683cbf34', 'id_card_front', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/id_card_front/94FPeqwMvC6fKpmnW1JTWdPcFDOeQfA62UHmdOhU.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 00:39:45', '2026-02-08 00:39:45'),
('1d192836-939d-4f36-b1e6-a3a6231b76ad', '6881dd0d-87d1-4a19-b8b8-f6e02e7aec40', 'id_card_back', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/id_card_back/0BXLrZ3L6LCcL1PLyrY7kORHd12Eudt6WOYISzUB.pdf', 'pending', NULL, NULL, NULL, '2026-02-08 01:02:23', '2026-02-08 01:02:23'),
('1d3da00b-ecca-48d2-853d-8d41bf247de5', '219f77bc-bb40-4121-9959-df510c83f90a', 'id_card_front', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/id_card_front/Ekzf6tAiGcugj69Sf4y31EFSeURUqCtqtJXdtnuA.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 00:38:58', '2026-02-08 00:38:58'),
('207d5eaa-1ef7-4f96-b0c4-97189a9e1885', 'da952527-786d-4073-b8b2-753b64dcc99b', 'id_card_front', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/id_card_front/XmgX9Bzgsf3rGugnD3tcw2avWkYXCKag7smV1Jkh.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 00:38:20', '2026-02-08 00:38:20'),
('20abd24b-3404-4600-94d5-6ca72d6247cf', '99cc4e46-477f-49ae-8716-ec4ac94a83f3', 'tax_card', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/tax_card/fn7ugGCU1DMxa83PPtb21R9mRBDekh4bVVtzAtMX.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 01:29:13', '2026-02-08 01:29:13'),
('2129251a-eceb-42d9-9a38-f5f4f34c684f', '7f239fef-820c-4d1b-8e13-f778e50568e5', 'commercial_register', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/commercial_register/D3BUJ8NSDqiZoGtaVWriHeY6uo4jUMwH4WXGTmyS.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 00:45:38', '2026-02-08 00:45:38'),
('22684c5e-cafe-4bb1-bd02-ac75a50314c4', 'eaf79b81-447a-4a7a-8a08-f26571f298b1', 'id_card_back', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/id_card_back/xh1FcciKB44EUpgokBk5BnUMkEdGkXXrR08dyvHU.pdf', 'pending', NULL, NULL, NULL, '2026-02-08 01:28:08', '2026-02-08 01:28:08'),
('23b97aab-7bae-4566-9a80-8a4b175f1b79', 'e608bf43-4e36-4823-8fda-b46399ad160f', 'commercial_register', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/commercial_register/9UA44c42FDwM87R7hi4bNVcJlHlqROZlmhOqs32N.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 01:33:32', '2026-02-08 01:33:32'),
('25a3d96b-7dc1-4e0b-b381-6f58a17ea344', '6881dd0d-87d1-4a19-b8b8-f6e02e7aec40', 'commercial_register', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/commercial_register/rgAtxCDwJRWiXYnAffzQOIrBBHu1bWcTd7OuBNJB.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 01:02:23', '2026-02-08 01:02:23'),
('260453fa-f732-494f-ac0d-137879223a37', '62984122-93ab-45df-bcb5-3a32258ab511', 'id_card_back', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/id_card_back/oT0h6vbmiYjBz00bST3HcwRGzSvdwc5e6LxzH5Q6.pdf', 'pending', NULL, NULL, NULL, '2026-02-08 00:40:37', '2026-02-08 00:40:37'),
('28624273-6991-43cc-a33a-02a384f26601', 'ee205576-2965-4202-b8a6-5f3f43c8e31a', 'id_card_back', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/id_card_back/HqEjC3xhAXFVjBoqGc1jZxUj1BOj2a6y7r5JNTPc.pdf', 'pending', NULL, NULL, NULL, '2026-02-08 01:33:52', '2026-02-08 01:33:52'),
('29a4337f-76eb-4277-920c-7640e844a2c8', 'bed4702c-de6b-4dbd-a9fc-663f405e09f7', 'commercial_register', 'stores/verifications/ykP7VV0fyCeLkAYOmlrHSswCh8cK5OqPmvjMQv9h.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 00:14:45', '2026-02-08 00:14:45'),
('2a878c80-abc0-4275-b1d7-e06c8f4638d7', 'e608bf43-4e36-4823-8fda-b46399ad160f', 'tax_card', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/tax_card/kGKLSdCs3huWMKlQH6UWLE83RO5OPGo3rsSo8SKa.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 01:33:32', '2026-02-08 01:33:32'),
('2acec9d5-27a8-4ddf-b98e-7e5c9dc6f929', '1d26abd4-7ab2-49e4-9feb-decb8767a1d9', 'commercial_register', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/commercial_register/YoarzKajOULzITcyco6K6S8Szv9DsdP6C0ybLW0k.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 01:30:41', '2026-02-08 01:30:41'),
('2cf656c6-2819-48cf-a68b-9df67d839b56', '8083ad58-00c2-49f0-b282-7dcd39beb107', 'id_card_front', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/id_card_front/wdUstr41s8t4cxEXJVeTt1nIptTzxm79WT9cFgGW.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 00:37:52', '2026-02-08 00:37:52'),
('2e7cfc78-d462-4a74-9b5d-c37f8d02c1dd', '8820cda8-50d1-4617-b741-f91ae252d253', 'tax_card', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/tax_card/6WWU3mptHdUhmp5wMY7Vjl46GFadH1xPnQRsU5Xm.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 01:33:58', '2026-02-08 01:33:58'),
('2f5f8ce3-19d3-4029-a4b6-a76cc7d44a4f', 'f81b7249-08f9-430f-b06d-f54b5f3ebe4e', 'commercial_register', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/commercial_register/kEmAJTGFJUeLbDqsKwTgF9NU3IA7Dt3MQ5VnQwLu.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 01:04:38', '2026-02-08 01:04:38'),
('3701fedd-f271-4e77-b72e-249b55f245f7', 'da952527-786d-4073-b8b2-753b64dcc99b', 'commercial_register', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/commercial_register/v00FBxq4T7lcg26CnRozKPKPAxSEF84zH7Za9mCd.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 00:38:20', '2026-02-08 00:38:20'),
('3a0e51e6-2175-4a69-a9da-04af3d9364ef', 'ad61c349-7c87-42ec-9ab4-423f238fad87', 'id_card_back', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/id_card_back/PaMiXptyPIU56K05s80lAHiPN3QBqz9yOYbnwCFl.pdf', 'pending', NULL, NULL, NULL, '2026-02-08 01:19:24', '2026-02-08 01:19:24'),
('3b8f86d3-16ad-4620-b5a6-2e3c0d49eb1d', 'f3d9eca3-c7a3-4571-82d3-b76cf1d1f1d9', 'tax_card', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/tax_card/ibDXqACTezco6WvVX62bAc4qAegF5HkX0pkO9okm.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 00:47:24', '2026-02-08 00:47:24'),
('3ba300e9-700f-4b61-972e-e5518f217d45', 'f8886dc8-36c1-4674-8aa3-cdf998cf32ce', 'commercial_register', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/commercial_register/gRw2iJn1Ds8UdW9Z51KxHEFxurr4eUCMGAAt6qAG.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 01:28:53', '2026-02-08 01:28:53'),
('3c9ad5cd-9b55-4ab3-88d8-84e1c9d5eaf9', '468070bb-5b1b-49bf-8e43-2265c4be08ef', 'id_card_front', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/id_card_front/RLiWqCiHuMK2UstZDFcvY9UfqHjhDSaksz64hFy2.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 01:21:59', '2026-02-08 01:21:59'),
('3f06a21d-e981-4601-96f3-fffba39dc116', '74824326-6c95-4ff4-83c0-8ac729d97817', 'commercial_register', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/commercial_register/7wYjAxCX204TP65yyq1LzZAigf7RxHcq1Aa4X4jQ.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 01:16:00', '2026-02-08 01:16:00'),
('4034901f-dec2-406d-b14a-2a7b9ddf82ce', 'fe50f29d-ddb5-4e6f-80e3-47cb34893a07', 'id_card_back', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/id_card_back/Dx4mbbLbP2oq8KfKrzH9x1y0j21jbxibVFowFAT6.pdf', 'pending', NULL, NULL, NULL, '2026-02-08 01:29:12', '2026-02-08 01:29:12'),
('411cb5c5-1e9a-4329-8722-18ccac540136', '74824326-6c95-4ff4-83c0-8ac729d97817', 'id_card_front', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/id_card_front/0ds9cVC7DGWqUhNvNomYBUakWJ1VKUkiLCLwUJLF.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 01:16:00', '2026-02-08 01:16:00'),
('4191e496-67dc-4eae-a12e-382996db55d2', '39b79652-beef-4877-8bd2-058eab653f6f', 'id_card_front', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/id_card_front/UA3ANFmg9YeKZXr41vV28DpxSk9J6kPf4BYG4WlI.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 01:16:11', '2026-02-08 01:16:11'),
('42d5bdbc-2925-49c3-bc45-3fe52929d469', '39b79652-beef-4877-8bd2-058eab653f6f', 'commercial_register', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/commercial_register/fpD8IAOP8MSAdsGoNfOLxxW07eL5Cza6HTUnoIP8.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 01:16:11', '2026-02-08 01:16:11'),
('44e0df0b-8f2e-4c33-9666-56ef7aa51304', 'f8886dc8-36c1-4674-8aa3-cdf998cf32ce', 'id_card_back', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/id_card_back/B2voeIubzn2M2wTBSkHA7CxRK1qVEIHUjSYRYVb5.pdf', 'pending', NULL, NULL, NULL, '2026-02-08 01:28:53', '2026-02-08 01:28:53'),
('452115fd-633f-4d25-933f-5fc974942ee0', '22507230-3892-4e78-9681-81c0ec94d7d0', 'id_card_back', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/id_card_back/FeZRE2w5rzebIxxyD2iQRDMPvkQKbgOclQOg5yZ0.pdf', 'pending', NULL, NULL, NULL, '2026-02-08 01:34:13', '2026-02-08 01:34:13'),
('49d225c0-e617-4da0-aea8-4e535f3dff0f', 'bd5f6b10-1640-4fe0-98a3-00f2eebf48f6', 'id_card_back', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/id_card_back/c45ExncRERSTFeWujGXLXaiIAxeSLkUzceVQqy0Z.pdf', 'pending', NULL, NULL, NULL, '2026-02-08 01:29:07', '2026-02-08 01:29:07'),
('4af55eff-8fcc-4a3e-be6a-b55b9075a195', '8820cda8-50d1-4617-b741-f91ae252d253', 'id_card_front', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/id_card_front/9YatHIHa7fLRpXyWMc33PVy1YU5urcQFuGZgTxZk.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 01:33:58', '2026-02-08 01:33:58'),
('4c47301f-4ee3-4ed3-a35e-bed921ec6ff0', '38dc83c7-7eed-4fe8-86ba-cab830e47b7f', 'id_card_front', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/id_card_front/zGXhNwJ4LvrWJ0yl2kEGBnPWyZkAEDaxQi7J51d9.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 01:25:03', '2026-02-08 01:25:03'),
('4cbf0155-33c8-4f39-b10a-2753031940e2', '62984122-93ab-45df-bcb5-3a32258ab511', 'tax_card', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/tax_card/7OJ21SjbWn1MesLjjkS3KYH1z86iOSEDGQfOl5kV.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 00:40:37', '2026-02-08 00:40:37'),
('4d806e8d-5df6-40ee-95d4-ad2f95d1c53f', '5e7ae24d-fe0c-4829-b893-4eff6ecb2791', 'commercial_register', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/commercial_register/ryWvHRZoxIzDwzIOlJfbqoWghaLSsKcHgl2dUOVK.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 00:52:50', '2026-02-08 00:52:50'),
('4f934f0d-5faf-4eaa-b511-1c0b6d540f7f', '38dc83c7-7eed-4fe8-86ba-cab830e47b7f', 'id_card_back', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/id_card_back/Crtbj5d9ud9HfqeCqczD3ptZD0pmSiY1iEVmQicV.pdf', 'pending', NULL, NULL, NULL, '2026-02-08 01:25:03', '2026-02-08 01:25:03'),
('53dba797-5f5f-473d-b4e4-0852692d7472', '38dc83c7-7eed-4fe8-86ba-cab830e47b7f', 'commercial_register', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/commercial_register/sE6BNEYvAyKif5PUbj5M5aIAfCaxTtghHd0jGpBi.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 01:25:03', '2026-02-08 01:25:03'),
('5530043a-e286-45ec-a2bf-6141c420c474', '1d26abd4-7ab2-49e4-9feb-decb8767a1d9', 'tax_card', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/tax_card/dGbc7690PVC9TK2lCxpkbBYRoCT2FAcycQLYDIjV.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 01:30:41', '2026-02-08 01:30:41'),
('56b1ea0a-6dfb-4142-a8af-d34a58da9fe9', 'bed4702c-de6b-4dbd-a9fc-663f405e09f7', 'id_card_back', 'stores/verifications/cwWRQQl7hlsWcHIqdbOaPimBq0lGosdktFb0wrvc.pdf', 'pending', NULL, NULL, NULL, '2026-02-08 00:14:45', '2026-02-08 00:14:45'),
('56d89137-daa8-4cea-b36a-92c2713a542c', '1209d5a1-5ffa-4f77-b073-99f7098ae404', 'id_card_front', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/id_card_front/xAlKHUdcP678KJ2gVqBlsyHYGqRVXW7pvDeD2Fjn.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 01:30:11', '2026-02-08 01:30:11'),
('57731e0f-67d2-41a9-ac1e-69bacfbed991', 'f3d9eca3-c7a3-4571-82d3-b76cf1d1f1d9', 'id_card_back', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/id_card_back/7S1BX3FrgSCET7dGZEkuLWYpCNCSKlkZ2iNZDsXO.pdf', 'pending', NULL, NULL, NULL, '2026-02-08 00:47:24', '2026-02-08 00:47:24'),
('59f5b31e-a548-4634-8513-4dc90c54cec2', '1209d5a1-5ffa-4f77-b073-99f7098ae404', 'id_card_back', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/id_card_back/yayzdhduP2uSPCQFy0LINhBEwe8kAa2Tdu3KZE3i.pdf', 'pending', NULL, NULL, NULL, '2026-02-08 01:30:11', '2026-02-08 01:30:11'),
('5af320c6-7cc4-48cb-9dc4-92bd70fa75b3', '62984122-93ab-45df-bcb5-3a32258ab511', 'id_card_front', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/id_card_front/2gORXdCLo6xQabt8GmjxbdtsWeAnCzDSgUte3UX8.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 00:40:37', '2026-02-08 00:40:37'),
('5dd57a16-880d-4749-9325-22b5c2ee541c', '96d9c4fa-a5cb-424e-85dc-506842dc2979', 'commercial_register', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/commercial_register/bcAv5hgb4uR8S1HObnaJp8EFaU26xi13SKCONtjd.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 01:19:46', '2026-02-08 01:19:46'),
('5f1cc6ea-7dc8-4318-80aa-e9ac3fd1a08f', '468070bb-5b1b-49bf-8e43-2265c4be08ef', 'id_card_back', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/id_card_back/N4vF9tuwvQ0zKh8DrhZBCjIL0NUgVNii0x1HTnXF.pdf', 'pending', NULL, NULL, NULL, '2026-02-08 01:21:59', '2026-02-08 01:21:59'),
('62739140-da1a-4ff3-ac85-87469f545d38', 'd5b9c837-3f55-4ba1-9b9a-94e4f146ef42', 'id_card_back', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/id_card_back/mwFcCNLeAgxigadul6OAJvlAPK4ittvICTinW7Zq.pdf', 'pending', NULL, NULL, NULL, '2026-02-08 00:41:00', '2026-02-08 00:41:00'),
('62783048-4677-4827-9703-623b141555fd', 'd5b9c837-3f55-4ba1-9b9a-94e4f146ef42', 'id_card_front', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/id_card_front/Rc38Ids5Kn6WSZ8hCreqd7ksixo6G3dRPRVg5Gj6.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 00:41:00', '2026-02-08 00:41:00'),
('63b4ee61-b821-46a8-9a5f-50074215ce43', 'a9cea373-3add-4870-8d20-396244f0c458', 'tax_card', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/tax_card/gFbBX72KaHfzSwdpNLy6Uw67COmHJBHRfzLWlC8w.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 01:02:52', '2026-02-08 01:02:52'),
('66dcf803-1b17-4370-aea3-e04cdf41951b', 'ee205576-2965-4202-b8a6-5f3f43c8e31a', 'commercial_register', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/commercial_register/Nx6ySCopt0me85zxtfKI0GNKIwL9zgQaBNpGHDNv.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 01:33:52', '2026-02-08 01:33:52'),
('67022cdd-0293-473d-9d5b-5ccf87861646', 'd5b9c837-3f55-4ba1-9b9a-94e4f146ef42', 'commercial_register', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/commercial_register/p2YXvQVIr3timm7jflvWplBEUWIYlqkNcL4S6FDq.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 00:41:00', '2026-02-08 00:41:00'),
('68d72a13-11e7-49ee-9b60-fa2f47182dd0', 'a382425d-15bb-4e35-9988-5a8f4f99fe72', 'id_card_back', 'stores/verifications/jwsTJcZXQVrLIDsfqMbyhMHnLJojD2tODFmgeeMC.pdf', 'pending', NULL, NULL, NULL, '2026-02-08 00:17:55', '2026-02-08 00:17:55'),
('68ea7e57-bae8-4dea-afd8-4c2daa0b64da', 'ecf29eb0-81ce-4a21-8174-15ce00d5d5b0', 'tax_card', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/tax_card/prx17os3gYrPB2hm3K13ZRrOKmRglujggTO4dwDX.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 01:10:43', '2026-02-08 01:10:43'),
('69de6856-d574-459b-a24f-49f48aa5aa7d', '22507230-3892-4e78-9681-81c0ec94d7d0', 'commercial_register', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/commercial_register/3KXRJrmsLA0eWMs33un5jNuwGJCPex9U5SrBwMWL.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 01:34:13', '2026-02-08 01:34:13'),
('6d92abb1-7f04-4f3c-bb8f-400830795cfc', 'fe50f29d-ddb5-4e6f-80e3-47cb34893a07', 'commercial_register', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/commercial_register/gl8bWmwea21VKSXQ45FJqmMGOGdNKIbsniicwvac.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 01:29:12', '2026-02-08 01:29:12'),
('6e39ed02-bfc5-4244-b8a7-cf4f1273da10', '1d26abd4-7ab2-49e4-9feb-decb8767a1d9', 'id_card_front', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/id_card_front/PfqdO44eixM1JDlHHj14me3M8ZIT8q3XsVxJk76j.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 01:30:41', '2026-02-08 01:30:41'),
('6e862548-e071-4baa-98c3-e12336d2da8e', '468070bb-5b1b-49bf-8e43-2265c4be08ef', 'tax_card', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/tax_card/xb5NvYjyT4wYogdZJQ67mvRu5N56M8Ur5IVjB1Dy.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 01:21:59', '2026-02-08 01:21:59'),
('6e8e4e41-505a-4a8f-ac6c-46801d62aa30', 'a382425d-15bb-4e35-9988-5a8f4f99fe72', 'id_card_front', 'stores/verifications/Px3K7XT4Uiwb9D010Ftf9Jgdko0wMP67eEKeXZdG.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 00:17:55', '2026-02-08 00:17:55'),
('71ea5ee3-7027-44a8-bed4-585dd189f040', '06e975df-9320-4b1a-af53-b5c8f6373b39', 'id_card_front', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/id_card_front/fNrRSIhf1ilH298eUwGr5zbTwXcp6Y1CFviL1c1y.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 01:20:45', '2026-02-08 01:20:45'),
('7256f779-9c21-40b7-a9c8-b3fdd88d2aee', '96d9c4fa-a5cb-424e-85dc-506842dc2979', 'tax_card', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/tax_card/azrJYYBO7DhrcK2wJb613FDGIt6lYZyW6c2p7H4n.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 01:19:46', '2026-02-08 01:19:46'),
('72924ee6-7891-4910-838f-a084a1858c36', 'a382425d-15bb-4e35-9988-5a8f4f99fe72', 'tax_card', 'stores/verifications/OfjMGqIM6vys3EZJiNdr9S8h3WWGifcYZKA0uZ6D.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 00:17:55', '2026-02-08 00:17:55'),
('73c03e63-8e03-401b-b881-09c300919f9e', '85093754-da13-4d21-b53d-f76226b83e5e', 'id_card_back', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/id_card_back/qBradiFr4efgRwa6A8vBuPesTMmE1h15yzZ8FSCR.pdf', 'pending', NULL, NULL, NULL, '2026-02-08 01:22:10', '2026-02-08 01:22:10'),
('7579d958-0735-4ccf-9072-adee691e3aad', 'bed4702c-de6b-4dbd-a9fc-663f405e09f7', 'tax_card', 'stores/verifications/J667T1kpsMzboMYjjelWFgY8nyfngFMB8AhaSnRj.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 00:14:45', '2026-02-08 00:14:45'),
('774bf5e3-b779-46e1-93a1-f59307da8ac1', '76428452-698b-4516-80ff-1f8223faf4e2', 'tax_card', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/tax_card/LAUabSN9WZjXKgg9TjdQ1IwNJIqPzi2Jyx6SSybX.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 01:31:23', '2026-02-08 01:31:23'),
('7881c6a7-392b-4da4-a958-e2b0baee492a', 'd5b9c837-3f55-4ba1-9b9a-94e4f146ef42', 'tax_card', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/tax_card/XPV2OgZ7RC5vxLx6yIBA7OHLc6CfqWYFnCTO5yvY.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 00:41:00', '2026-02-08 00:41:00'),
('79600bfe-c0a0-49c3-9d39-a55cb4d27a9f', '74824326-6c95-4ff4-83c0-8ac729d97817', 'tax_card', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/tax_card/5mCXBTZb7RumRqFmZxzKvyGMizIPMzGP7omqcGsb.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 01:16:00', '2026-02-08 01:16:00'),
('79cb5872-ad62-4f7e-832d-7ff9f14513de', 'ecf29eb0-81ce-4a21-8174-15ce00d5d5b0', 'commercial_register', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/commercial_register/AmvjYp22Z0rKpi2Azo6yDqD4fcYMDVhPLzF4U5R2.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 01:10:43', '2026-02-08 01:10:43'),
('7cdc5abd-cb3c-4312-82ce-128231564fdf', '06e975df-9320-4b1a-af53-b5c8f6373b39', 'commercial_register', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/commercial_register/PmyJqFgE5zVQRmAv6zKafq9GpMsMdDAa7cjc8fP9.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 01:20:45', '2026-02-08 01:20:45'),
('7e024570-2502-48f3-a932-b0890bbded9c', '8820cda8-50d1-4617-b741-f91ae252d253', 'commercial_register', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/commercial_register/WXxpttGG81m1qlW8nPXEdu7aqsHykXQwUXdGC86o.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 01:33:58', '2026-02-08 01:33:58'),
('7f4d8c5a-e600-42dd-85c1-5066d4dd06fc', '2c45144b-9bcd-4b01-ace8-9133683cbf34', 'tax_card', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/tax_card/uVmXGp3OMga8Phln7A7JJzd3JAzayHCOQpkFJn3e.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 00:39:45', '2026-02-08 00:39:45'),
('7f818f4e-ca76-47f5-bf10-2da94f4d60c5', '61df8a7d-2493-4911-b3ec-8f9d9fcb6e69', 'commercial_register', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/HM/commercial_register/H2mkX8UCruyxrnwZ4PURTP394jdbNg87mMabXN7U.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 00:26:20', '2026-02-08 00:26:20'),
('84ab7ccf-c165-473b-bdef-53d8e39e9813', '67d978be-f571-4ecf-b939-997daa4c16a6', 'id_card_back', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/id_card_back/BFkyErslI5BHs49dCOacmlNc90NRzOl24NIdrny4.pdf', 'pending', NULL, NULL, NULL, '2026-02-08 01:26:14', '2026-02-08 01:26:14'),
('85304454-c7d9-4631-94ae-24b366db2ab5', 'f81b7249-08f9-430f-b06d-f54b5f3ebe4e', 'id_card_back', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/id_card_back/GBvZgH8lG7G4yUK3UUw7qyyeDfcc5erh0hj0qgFX.pdf', 'pending', NULL, NULL, NULL, '2026-02-08 01:04:38', '2026-02-08 01:04:38'),
('86fd6102-a8d7-4996-9c7e-25591c2b47a4', '8820cda8-50d1-4617-b741-f91ae252d253', 'id_card_back', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/id_card_back/PAopeYszJzmX7jjP08cbWkfQsDjGEigdS44SdwMC.pdf', 'pending', NULL, NULL, NULL, '2026-02-08 01:33:58', '2026-02-08 01:33:58'),
('87836d7f-6868-4347-984d-2d66535e6dd1', 'e608bf43-4e36-4823-8fda-b46399ad160f', 'id_card_back', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/id_card_back/i8hIx6A6KByBzSPE1tz0YnGH8qt9bWaf3a1bvXuC.pdf', 'pending', NULL, NULL, NULL, '2026-02-08 01:33:32', '2026-02-08 01:33:32'),
('8fe4e5e8-0286-4f36-ad00-c5c31c4216af', '22507230-3892-4e78-9681-81c0ec94d7d0', 'id_card_front', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/id_card_front/1HawVBJ6VCMYiyItVsEeuYR4u8pIECx337iMfIhJ.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 01:34:13', '2026-02-08 01:34:13'),
('90d130fd-ee7b-46b8-b05a-77c4cd878e03', '67d978be-f571-4ecf-b939-997daa4c16a6', 'tax_card', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/tax_card/3HckLexl6atWJ89x2IQ01GTCWNxfdSilUu7AY0LX.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 01:26:14', '2026-02-08 01:26:14'),
('94847c71-9308-42f1-9848-3a9be753a950', '06e975df-9320-4b1a-af53-b5c8f6373b39', 'id_card_back', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/id_card_back/YERtDdDLOHn2YeEiAl4vXxrGhpp6MtWBEGgiurMj.pdf', 'pending', NULL, NULL, NULL, '2026-02-08 01:20:45', '2026-02-08 01:20:45'),
('96b0a6f6-b8b6-4c6b-a9c7-37d70494f9b2', '76428452-698b-4516-80ff-1f8223faf4e2', 'commercial_register', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/commercial_register/ebwYlDcEcXdobgToVbePk4yAacFWrlvW5UDOejTq.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 01:31:23', '2026-02-08 01:31:23'),
('992bfd17-7106-47e2-985c-9cb0596b585b', 'a9cea373-3add-4870-8d20-396244f0c458', 'id_card_back', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/id_card_back/9H23iWBXVIiDtIjBnJwPzwnC515JozZVfp7Omhsn.pdf', 'pending', NULL, NULL, NULL, '2026-02-08 01:02:52', '2026-02-08 01:02:52'),
('995b8bdb-23ab-4edb-9070-f09017cae32a', '7f239fef-820c-4d1b-8e13-f778e50568e5', 'tax_card', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/tax_card/ByuUdMmyCbqJxs81I3nLc2dgwbQ4zEh0rNafARv9.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 00:45:38', '2026-02-08 00:45:38'),
('9ae49da9-0e1a-4197-8f7d-ae399de9b055', '61df8a7d-2493-4911-b3ec-8f9d9fcb6e69', 'id_card_back', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/HM/id_card_back/y8TwC9ogcuZG2qd1nxNLMlifZmVrqvVENN3lolSc.pdf', 'pending', NULL, NULL, NULL, '2026-02-08 00:26:20', '2026-02-08 00:26:20'),
('9d9b4809-e394-4222-afe4-99703b2ce529', 'eb379eb9-994b-466e-95fb-ce9344b37311', 'commercial_register', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/commercial_register/Cat7oErMDV8iGaD0R1ym1p3MyXzZSa34grxkbw5r.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 01:33:12', '2026-02-08 01:33:12'),
('9e6289c8-53ae-4d97-a35c-3d4fa416c616', '74824326-6c95-4ff4-83c0-8ac729d97817', 'id_card_back', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/id_card_back/fVwlxo5kqPVSuMh4sLHHPssEeO6vJrevRyqFmvc0.pdf', 'pending', NULL, NULL, NULL, '2026-02-08 01:16:00', '2026-02-08 01:16:00'),
('9ec27952-9e9a-4a7c-81da-3c0863466b70', 'eaf79b81-447a-4a7a-8a08-f26571f298b1', 'commercial_register', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/commercial_register/nCb6IPKBV8vddQImmL0QKTn61yiSekDpRj31i3rQ.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 01:28:08', '2026-02-08 01:28:08'),
('a13c34a5-ee27-41c9-b482-8910630a70eb', 'bed4702c-de6b-4dbd-a9fc-663f405e09f7', 'id_card_front', 'stores/verifications/wk5r7nTivCEJLvNWEG7eY2ef04PsWVSp14S30O3s.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 00:14:45', '2026-02-08 00:14:45'),
('a2eece8c-22f4-468d-9576-23ac09467cf1', '67d978be-f571-4ecf-b939-997daa4c16a6', 'commercial_register', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/commercial_register/1dvZHAFKlr1HTmiCWmAjUuCga4zxzu94sMK01YvQ.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 01:26:14', '2026-02-08 01:26:14'),
('a3e3825a-0fcd-4aad-9633-f4105e57c48a', '61df8a7d-2493-4911-b3ec-8f9d9fcb6e69', 'id_card_front', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/HM/id_card_front/D0QN1G7oQ4j9hcOAbgE9SjIGtCOjngM3ZI6jH3lB.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 00:26:20', '2026-02-08 00:26:20'),
('ae4fc57a-0f2a-4b16-9546-60ffcd20fccb', 'f81b7249-08f9-430f-b06d-f54b5f3ebe4e', 'tax_card', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/tax_card/YEXjV2tnBoBSd71lgYBAJsN22YRmj77EvVSgPa7a.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 01:04:38', '2026-02-08 01:04:38'),
('ae5f341a-33c5-4a65-a579-4bfac8f71c11', 'a9abfce0-b6b0-41d0-be5d-8ce27662bace', 'commercial_register', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/commercial_register/wgZelCFvseitD2H6wfcbZGxywZTsOyG0HxeUECCK.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 01:31:46', '2026-02-08 01:31:46'),
('b0544f08-88ed-43eb-abc4-9ce056bad797', '7f239fef-820c-4d1b-8e13-f778e50568e5', 'id_card_back', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/id_card_back/HN4RyE6UtP7f7TAwbVj8OmCtT502lzs3nPWMrr6L.pdf', 'pending', NULL, NULL, NULL, '2026-02-08 00:45:38', '2026-02-08 00:45:38'),
('b0f5378a-0291-4c64-8f8c-e13f17b3ae1d', '8083ad58-00c2-49f0-b282-7dcd39beb107', 'id_card_back', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/id_card_back/j5zH1LC5Y5h7NXKhKkCET7PiPRmvTW8PeR0zqDUc.pdf', 'pending', NULL, NULL, NULL, '2026-02-08 00:37:52', '2026-02-08 00:37:52'),
('b188e4aa-ce93-4516-8e9c-bdec6331742f', 'eb379eb9-994b-466e-95fb-ce9344b37311', 'id_card_back', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/id_card_back/aN6WXVmBJyxya30J0fA74CjBz1qZKy0O0TGIA0ko.pdf', 'pending', NULL, NULL, NULL, '2026-02-08 01:33:12', '2026-02-08 01:33:12'),
('b1ad0896-eb60-4953-b135-945445aeb53e', '06e975df-9320-4b1a-af53-b5c8f6373b39', 'tax_card', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/tax_card/Ogcj11uDIOIwUTYYqXSOFu1P2MOiStgDvlYWt9mY.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 01:20:45', '2026-02-08 01:20:45'),
('b425888b-38fa-45fb-8778-c26761614251', '85093754-da13-4d21-b53d-f76226b83e5e', 'tax_card', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/tax_card/sXu42ogffmXHtCpRNRRXf5oUCEhDHJuxCI6SKBAz.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 01:22:10', '2026-02-08 01:22:10'),
('b5144e64-6cdb-40ad-8c3d-a7261da4bb2c', 'a9cea373-3add-4870-8d20-396244f0c458', 'commercial_register', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/commercial_register/SsW4wTggQfQU8fBe1mklJEisXf6PkAoc5FjQkSen.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 01:02:52', '2026-02-08 01:02:52'),
('b5c8214d-2345-406a-8034-cb54d937b877', '5e7ae24d-fe0c-4829-b893-4eff6ecb2791', 'id_card_back', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/id_card_back/extuItW0GzZAysfrV5ySEFed26OtBupH2uUb6I2h.pdf', 'pending', NULL, NULL, NULL, '2026-02-08 00:52:50', '2026-02-08 00:52:50'),
('b6535eb1-fb30-4935-a3e1-2f5ebd853a0a', '76428452-698b-4516-80ff-1f8223faf4e2', 'id_card_front', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/id_card_front/ckNRa9IOaFlyn0KKJyp2drUA57wauCnkRLCCKz5y.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 01:31:23', '2026-02-08 01:31:23'),
('b88465b3-7e8d-4857-8dad-7eb3b70da39b', '1209d5a1-5ffa-4f77-b073-99f7098ae404', 'commercial_register', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/commercial_register/pNL8o7MddHOegdlMVNFPGvbxgJ9QKcW5mVwedRYU.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 01:30:11', '2026-02-08 01:30:11'),
('baa84f5b-a17b-4425-9dda-6c09b3fee52c', '61df8a7d-2493-4911-b3ec-8f9d9fcb6e69', 'tax_card', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/HM/tax_card/uQ4Ow672zOaAFZLVg19hnXz9bqnPzM9lOcPKBceB.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 00:26:20', '2026-02-08 00:26:20'),
('bacee422-1e19-4e1a-bb72-f1e61b2a6374', 'ee205576-2965-4202-b8a6-5f3f43c8e31a', 'id_card_front', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/id_card_front/PcyoU56653HuyQx7e6fJGNhNohSMCGkCptoB7pSv.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 01:33:52', '2026-02-08 01:33:52'),
('bedc647c-1b7a-44d5-b62c-978aa8fd1e42', 'a9abfce0-b6b0-41d0-be5d-8ce27662bace', 'id_card_back', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/id_card_back/Fe4ue6ZmvnIzHLmf3YxVMPwHmxEIYrPn99ew5ujl.pdf', 'pending', NULL, NULL, NULL, '2026-02-08 01:31:46', '2026-02-08 01:31:46'),
('c0388ee5-ff4d-4d23-b647-7a35ebfdc554', 'a9cea373-3add-4870-8d20-396244f0c458', 'id_card_front', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/id_card_front/EZrDke6MR2fxH0BkB6aEhgcKmUmfYwxTFQA0RHRY.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 01:02:52', '2026-02-08 01:02:52'),
('c1a44f1c-f72b-498f-b6d0-41ca38f9e4e2', '468070bb-5b1b-49bf-8e43-2265c4be08ef', 'commercial_register', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/commercial_register/aFlRliBhNNTbLy2yjPMAM0t7QOwVq6NG5mJ8HB7d.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 01:21:59', '2026-02-08 01:21:59'),
('c61c3da5-b7a3-472e-91d6-94fee530266f', '99cc4e46-477f-49ae-8716-ec4ac94a83f3', 'id_card_back', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/id_card_back/VUtJ3L57vp3sOye50Oqk0wAehbrYd3ISugzT3MNZ.pdf', 'pending', NULL, NULL, NULL, '2026-02-08 01:29:13', '2026-02-08 01:29:13'),
('c73be8c3-f214-445f-9137-506ada2e5ead', '22507230-3892-4e78-9681-81c0ec94d7d0', 'tax_card', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/tax_card/EyCdyuWMCZCSoTXMTifacDYpz0KqHhLSayKAA1GI.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 01:34:13', '2026-02-08 01:34:13'),
('caf0a0d8-01a0-45fa-8297-2a327f298977', '219f77bc-bb40-4121-9959-df510c83f90a', 'commercial_register', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/commercial_register/zsOArt2kjflFBmx9mMgj7e3N7W4Z3vonNxrTqTiE.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 00:38:58', '2026-02-08 00:38:58'),
('cc3e0fee-4a55-4866-a02d-7a363e5298f3', '6df8be66-bac3-4b9a-b51f-7c3c23fb8481', 'commercial_register', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539HM/commercial_register/7hxiwWp4A5EnkrZOcIRwYLEnKBw5xTfzLqIF8LmA.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 00:25:06', '2026-02-08 00:25:06'),
('cc707138-7e90-4b63-9589-16b32fdc9c0b', '1d26abd4-7ab2-49e4-9feb-decb8767a1d9', 'id_card_back', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/id_card_back/Y3DKiJcBX5sLL86lOiRA5L71NstIAtaSIEly60Hg.pdf', 'pending', NULL, NULL, NULL, '2026-02-08 01:30:41', '2026-02-08 01:30:41'),
('cf2f108d-9c85-4972-a6bb-1ab335635263', 'a9abfce0-b6b0-41d0-be5d-8ce27662bace', 'tax_card', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/tax_card/xZueZKNeOtMCV9NqnBh1LIsJX4PaA12VMDmg4Vju.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 01:31:46', '2026-02-08 01:31:46'),
('d0776838-c7a3-4d80-afaa-3013343685cd', 'a9abfce0-b6b0-41d0-be5d-8ce27662bace', 'id_card_front', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/id_card_front/hN4LT7daVmii9lFIXGlM9VZzyVBHbrXdTRuDpEL8.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 01:31:46', '2026-02-08 01:31:46'),
('d50e27b0-428e-4d96-b467-22295445716c', '6df8be66-bac3-4b9a-b51f-7c3c23fb8481', 'tax_card', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539HM/tax_card/GzYlVKwuksjwOSqhSeHeaOAYF3aogE39trM3qhDk.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 00:25:06', '2026-02-08 00:25:06'),
('d60c7216-0fe3-40c7-9091-d22b46cee995', '39b79652-beef-4877-8bd2-058eab653f6f', 'id_card_back', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/id_card_back/Vkx12GtsoafOXajj0yTGnojDeBe9ms2FgoJxapq7.pdf', 'pending', NULL, NULL, NULL, '2026-02-08 01:16:11', '2026-02-08 01:16:11'),
('d6462a89-2774-4f1b-9087-6956a78d99a3', 'ad61c349-7c87-42ec-9ab4-423f238fad87', 'tax_card', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/tax_card/UBS93hqQzCtDzSV6lrn0KZUlwpHavEfZggCRIsfo.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 01:19:24', '2026-02-08 01:19:24'),
('da04d141-ee7c-4c55-a995-2524dc80ed3e', 'eaf79b81-447a-4a7a-8a08-f26571f298b1', 'id_card_front', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/id_card_front/xnec0ek70gAe2NxGUwSAuc2q0MmZCpPLFhDrk5J9.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 01:28:08', '2026-02-08 01:28:08'),
('da2bc0cb-c1b6-45ee-861b-e663220292f9', 'fe50f29d-ddb5-4e6f-80e3-47cb34893a07', 'tax_card', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/tax_card/LBBSiRSOQfjNLy4jTjuKExZwBhQhEsBGwqorvJ0g.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 01:29:12', '2026-02-08 01:29:12'),
('dab00323-30cf-40ce-9aad-f7cbab4ec065', '2c45144b-9bcd-4b01-ace8-9133683cbf34', 'commercial_register', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/commercial_register/8SlGLMiDmDz4GrSL8dKDfZngnPTof3YSx6Je4Lsj.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 00:39:45', '2026-02-08 00:39:45'),
('daeb3686-e619-47a2-af22-50c001623148', '38dc83c7-7eed-4fe8-86ba-cab830e47b7f', 'tax_card', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/tax_card/sSUB0UCvu6JFF0MneswIjoRvVjkS7xBvHlBh3NrL.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 01:25:03', '2026-02-08 01:25:03'),
('db93d5b3-dab0-47e3-8ca6-fb57b34fee54', 'fe50f29d-ddb5-4e6f-80e3-47cb34893a07', 'id_card_front', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/id_card_front/Yh7QgyP5SXzasGsdNrS9ZPLyGwOTYi6w0wdOsb4y.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 01:29:12', '2026-02-08 01:29:12'),
('def848fc-17a9-4b3b-b30d-5a546db8b796', 'da952527-786d-4073-b8b2-753b64dcc99b', 'id_card_back', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/id_card_back/eB1aZNcZjCaPMP95CfCEbYDAp36QDh8rWDTchZ6F.pdf', 'pending', NULL, NULL, NULL, '2026-02-08 00:38:20', '2026-02-08 00:38:20'),
('df2e322c-5638-481e-9b72-a54e2aba2af1', 'e608bf43-4e36-4823-8fda-b46399ad160f', 'id_card_front', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/id_card_front/gwsfq8pJ1OMCfgriTP7eq2U5JuDcG9n1STweRqDL.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 01:33:32', '2026-02-08 01:33:32'),
('e142e17b-8106-4bfd-a797-ff0bb092d967', 'ee205576-2965-4202-b8a6-5f3f43c8e31a', 'tax_card', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/tax_card/MehCSYIOXoc4v4RyfIIoA3Y0VxJqYhBG9NkMDWE3.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 01:33:52', '2026-02-08 01:33:52'),
('e378e638-3973-4fa1-b643-03edc167bb3c', 'bd5f6b10-1640-4fe0-98a3-00f2eebf48f6', 'commercial_register', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/commercial_register/SvxQ0urV6zMtcmi8QLoiL3W3cX0Q8Yp4GDBYXx5F.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 01:29:07', '2026-02-08 01:29:07'),
('e4631bfb-43b7-4de3-a72a-8c988119f809', '1209d5a1-5ffa-4f77-b073-99f7098ae404', 'tax_card', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/tax_card/c7v9CPZBjuKGgKg7PXFVnndxOJJRfKeynz3bzSUZ.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 01:30:11', '2026-02-08 01:30:11'),
('e51c8314-e988-4102-81cc-118a67290218', '5e7ae24d-fe0c-4829-b893-4eff6ecb2791', 'id_card_front', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/id_card_front/3SDQYSc0lq3dIecEzYRl43Xp56odoScJQBloPgnC.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 00:52:50', '2026-02-08 00:52:50'),
('e546a9ce-9351-49be-9ad1-12ba0d1a4e19', '7f239fef-820c-4d1b-8e13-f778e50568e5', 'id_card_front', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/id_card_front/tCQ3kcEODI7xVYBkgRNYLXGCVhKsZ5sz695BdYQO.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 00:45:38', '2026-02-08 00:45:38'),
('e722b589-b64f-4a23-9818-88e511f5b945', '55dbc156-4082-46dc-90cd-b543b4f18418', 'id_card_back', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/id_card_back/uTatNAKvEXXrCpducal2kLwvMWO8ZzY8QkMBFsJi.pdf', 'pending', NULL, NULL, NULL, '2026-02-08 01:06:59', '2026-02-08 01:06:59'),
('e7548cb2-2098-47e1-9d79-cf1e285ae3f1', '96d9c4fa-a5cb-424e-85dc-506842dc2979', 'id_card_front', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/id_card_front/OVQ38EnxV7gvYO8tk3HW4v5g7E2Z8qztLCakyoKE.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 01:19:46', '2026-02-08 01:19:46'),
('e8949017-6b08-46e0-a76f-c49aee319ee4', '219f77bc-bb40-4121-9959-df510c83f90a', 'tax_card', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/tax_card/kzcImbnzJDoyXYVmAoZ5IcTnOn9uKKUfHTOTluvy.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 00:38:58', '2026-02-08 00:38:58'),
('e9bdd0b2-da05-4770-9318-a9202973ff8c', 'ad61c349-7c87-42ec-9ab4-423f238fad87', 'commercial_register', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/commercial_register/36J5uT5Vpb6LQqdF7BFPvVnd3QspKg9xjBKh6WY8.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 01:19:24', '2026-02-08 01:19:24'),
('ea11cabe-ca3a-4a9e-b181-c8c998624a06', 'ecf29eb0-81ce-4a21-8174-15ce00d5d5b0', 'id_card_front', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/id_card_front/pFPrBU25S5bLDS3wQSGtvud3rGQBp53F7R2Xz6BU.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 01:10:43', '2026-02-08 01:10:43'),
('eaf8652c-2f34-4321-8f3d-dc4e4e406fc3', '55dbc156-4082-46dc-90cd-b543b4f18418', 'tax_card', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/tax_card/Z7cwQ0Rgs5ggkz9ZQVRM1yDMab1sOO2oWgfRTbR6.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 01:06:59', '2026-02-08 01:06:59'),
('ec218442-40ec-4648-acc1-771950ad8835', 'bd5f6b10-1640-4fe0-98a3-00f2eebf48f6', 'tax_card', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/tax_card/UaisW6xFoRipZGeIVI2mIrFGwDCXUXNs5HsJVUAd.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 01:29:07', '2026-02-08 01:29:07'),
('f0fdb5cc-9eb1-4394-ada6-bf6973badbd6', '5e7ae24d-fe0c-4829-b893-4eff6ecb2791', 'tax_card', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/tax_card/2ndcezJKotu5lc29wlw08ib34K2S1j3kSHNEzUDH.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 00:52:50', '2026-02-08 00:52:50'),
('f23c92a7-a292-43aa-b92b-7663fafd0530', '99cc4e46-477f-49ae-8716-ec4ac94a83f3', 'id_card_front', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/id_card_front/EEx7WJXNdYSjTpOjlgHk7opSvikNjZr3REwNLnjk.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 01:29:13', '2026-02-08 01:29:13'),
('f263e383-76ac-483d-b865-fde8fbdbf5b2', '99cc4e46-477f-49ae-8716-ec4ac94a83f3', 'commercial_register', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/commercial_register/Eabj6UqtKXEAEw1odNKMTVFEGDqYfQhwLFT3psiE.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 01:29:13', '2026-02-08 01:29:13'),
('f378ee98-64e7-4031-9e34-13e559572c9e', 'bd5f6b10-1640-4fe0-98a3-00f2eebf48f6', 'id_card_front', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/id_card_front/cXt4uL1g7Ygz0InCOyhglZWc8XOSJzr23UFn9ZkD.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 01:29:07', '2026-02-08 01:29:07'),
('f51310f4-33b8-4f47-b91f-7c45d54779bb', '8083ad58-00c2-49f0-b282-7dcd39beb107', 'commercial_register', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/commercial_register/qC9exEkwH4hi4MIqAX3khhNG0AxaRaUoo0iCQXuV.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 00:37:52', '2026-02-08 00:37:52'),
('f6a1b7e1-990d-44be-87d9-ab7d15f5ac6a', '8083ad58-00c2-49f0-b282-7dcd39beb107', 'tax_card', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/tax_card/GSqBcVPe3P3PdnPppn5DddM8hn5jej4hMVMjgen3.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 00:37:52', '2026-02-08 00:37:52'),
('f98d042e-f5d0-4fa2-af40-ce532938c9f0', '67d978be-f571-4ecf-b939-997daa4c16a6', 'id_card_front', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/id_card_front/6mzZsNpo166xphDp79alPd6RNgbfHYVSa67Hhjnw.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 01:26:14', '2026-02-08 01:26:14'),
('fa1dccc0-f04c-462d-9513-99281eb91188', 'ecf29eb0-81ce-4a21-8174-15ce00d5d5b0', 'id_card_back', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/id_card_back/FMoCxBGsyi8SNf5MMRl41ZespfZ6WFi62bFJahIF.pdf', 'pending', NULL, NULL, NULL, '2026-02-08 01:10:43', '2026-02-08 01:10:43'),
('fd28df15-69e0-4ce9-b5f5-d6b0024f56c6', 'eaf79b81-447a-4a7a-8a08-f26571f298b1', 'tax_card', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/tax_card/WPBYVpwKdhqEh2jajHy6CBgFIXkLo1uDqZTb84Xx.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 01:28:08', '2026-02-08 01:28:08'),
('fd8a234a-14f9-4d4a-ba03-87eaed89a0d1', 'eb379eb9-994b-466e-95fb-ce9344b37311', 'id_card_front', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/id_card_front/u1MLuZnAoandYsftiyjKWF2s2CossJ54F4ronT4Y.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 01:33:12', '2026-02-08 01:33:12'),
('fda7e456-f43d-4ac1-a086-e237de22b170', '55dbc156-4082-46dc-90cd-b543b4f18418', 'commercial_register', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/commercial_register/VzkQuW0MxKdxkMIFPCSeTJGu9dHMPqOoB9l8kxVb.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 01:06:59', '2026-02-08 01:06:59'),
('fe718a1a-30dc-477b-8785-04f71bd5e339', '96d9c4fa-a5cb-424e-85dc-506842dc2979', 'id_card_back', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/id_card_back/GTQqr4WF5YWfGiUuuh1s5Cxc1V0oP5NAV7274vkP.pdf', 'pending', NULL, NULL, NULL, '2026-02-08 01:19:46', '2026-02-08 01:19:46'),
('fe748e2c-c69d-47ac-b1b7-567b6d954f39', 'ad61c349-7c87-42ec-9ab4-423f238fad87', 'id_card_front', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/id_card_front/sNe83WfU54FIc6KFCZOjTITvzXu4QLSABai3bGVc.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 01:19:24', '2026-02-08 01:19:24'),
('fe8bb1d1-fc30-4d99-b842-82a3879b4fcf', '55dbc156-4082-46dc-90cd-b543b4f18418', 'id_card_front', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/id_card_front/ZGCw9bWXro59i6dYp1VU1MvGIkPm358ly2b3eAGD.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 01:06:59', '2026-02-08 01:06:59'),
('ffed600a-5d5b-46e6-9add-a79cb13ed866', 'f3d9eca3-c7a3-4571-82d3-b76cf1d1f1d9', 'commercial_register', 'stores/verifications/193e51d4-5225-4cd0-8c54-5ea3b90a5539/hm/commercial_register/tf8SlVazM0VbiFjqwVFXbAOzlvvhuwcNyla9Onxs.jpg', 'pending', NULL, NULL, NULL, '2026-02-08 00:47:24', '2026-02-08 00:47:24');

-- --------------------------------------------------------

--
-- Table structure for table `telescope_entries`
--

CREATE TABLE `telescope_entries` (
  `sequence` bigint(20) UNSIGNED NOT NULL,
  `uuid` char(36) NOT NULL,
  `batch_id` char(36) NOT NULL,
  `family_hash` varchar(255) DEFAULT NULL,
  `should_display_on_index` tinyint(1) NOT NULL DEFAULT 1,
  `type` varchar(20) NOT NULL,
  `content` longtext NOT NULL,
  `created_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `telescope_entries`
--

INSERT INTO `telescope_entries` (`sequence`, `uuid`, `batch_id`, `family_hash`, `should_display_on_index`, `type`, `content`, `created_at`) VALUES
(1, 'a1044d0b-bc90-432f-b2dc-57a4fe62806c', 'a1044d0b-bdfd-4227-b12a-60716aa6adb2', '9d729e5de3738b409c773e3d730b3956', 1, 'exception', '{\"class\":\"ReflectionException\",\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\RouteSignatureParameters.php\",\"line\":27,\"message\":\"Function () does not exist\",\"context\":null,\"trace\":[{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\RouteSignatureParameters.php\",\"line\":27},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\Route.php\",\"line\":545},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\ImplicitRouteBinding.php\",\"line\":79},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\ImplicitRouteBinding.php\",\"line\":27},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\Router.php\",\"line\":980},[],{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\Router.php\",\"line\":982},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\Middleware\\\\SubstituteBindings.php\",\"line\":41},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":137},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\Router.php\",\"line\":821},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\Router.php\",\"line\":800},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\Router.php\",\"line\":764},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\Router.php\",\"line\":753},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Kernel.php\",\"line\":200},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":180},{\"file\":\"E:\\\\coupony-backend\\\\app\\\\Http\\\\Middleware\\\\UpdateUserSession.php\",\"line\":26},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219},{\"file\":\"E:\\\\coupony-backend\\\\app\\\\Http\\\\Middleware\\\\PerformanceMonitoring.php\",\"line\":21},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Middleware\\\\TransformsRequest.php\",\"line\":21},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Middleware\\\\ConvertEmptyStringsToNull.php\",\"line\":31},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Middleware\\\\TransformsRequest.php\",\"line\":21},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Middleware\\\\TrimStrings.php\",\"line\":51},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Http\\\\Middleware\\\\ValidatePostSize.php\",\"line\":27},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Middleware\\\\PreventRequestsDuringMaintenance.php\",\"line\":109},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Http\\\\Middleware\\\\HandleCors.php\",\"line\":74},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Http\\\\Middleware\\\\TrustProxies.php\",\"line\":58},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Middleware\\\\InvokeDeferredCallbacks.php\",\"line\":22},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Http\\\\Middleware\\\\ValidatePathEncoding.php\",\"line\":26},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":137},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Kernel.php\",\"line\":175},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Kernel.php\",\"line\":144},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Application.php\",\"line\":1220},{\"file\":\"E:\\\\coupony-backend\\\\public\\\\index.php\",\"line\":20},{\"file\":\"C:\\\\Program Files\\\\Herd\\\\resources\\\\app.asar.unpacked\\\\resources\\\\valet\\\\server.php\",\"line\":139}],\"line_preview\":{\"18\":\"     *\\/\",\"19\":\"    public static function fromAction(array $action, $conditions = [])\",\"20\":\"    {\",\"21\":\"        $callback = RouteAction::containsSerializedClosure($action)\",\"22\":\"            ? unserialize($action[\'uses\'])->getClosure()\",\"23\":\"            : $action[\'uses\'];\",\"24\":\"\",\"25\":\"        $parameters = is_string($callback)\",\"26\":\"            ? static::fromClassMethodString($callback)\",\"27\":\"            : (new ReflectionFunction($callback))->getParameters();\",\"28\":\"\",\"29\":\"        return match (true) {\",\"30\":\"            ! empty($conditions[\'subClass\']) => array_filter($parameters, fn ($p) => Reflector::isParameterSubclassOf($p, $conditions[\'subClass\'])),\",\"31\":\"            ! empty($conditions[\'backedEnum\']) => array_filter($parameters, fn ($p) => Reflector::isParameterBackedEnumWithStringBackingType($p)),\",\"32\":\"            default => $parameters,\",\"33\":\"        };\",\"34\":\"    }\",\"35\":\"\",\"36\":\"    \\/**\",\"37\":\"     * Get the parameters for the given class \\/ method by string.\"},\"hostname\":\"DESKTOP-GDIKUKP\",\"occurrences\":1}', '2026-02-06 19:20:45'),
(2, 'a1044d0b-bda7-4af3-93f7-6a14f187fe14', 'a1044d0b-bdfd-4227-b12a-60716aa6adb2', NULL, 1, 'request', '{\"ip_address\":\"127.0.0.1\",\"uri\":\"\\/api\\/v1\\/admin\\/register\",\"method\":\"POST\",\"controller_action\":\"Closure\",\"middleware\":[\"api\"],\"headers\":{\"content-length\":\"194\",\"connection\":\"keep-alive\",\"accept-encoding\":\"gzip, deflate, br\",\"host\":\"coupony-backend.test\",\"postman-token\":\"3fb9ff26-ab85-429a-862b-d6db8195278e\",\"cache-control\":\"no-cache\",\"user-agent\":\"PostmanRuntime\\/7.51.1\",\"authorization\":\"********\",\"content-type\":\"application\\/json\",\"accept\":\"application\\/json\"},\"payload\":{\"first_name\":\"Ahmed\",\"last_name\":\"Mostafa\",\"email\":\"lofylofy56@gmail.com\",\"password\":\"********\",\"password_confirmation\":\"********\",\"role\":\"admin\"},\"session\":[],\"response_headers\":{\"cache-control\":\"no-cache, private\",\"date\":\"Fri, 06 Feb 2026 17:20:45 GMT\",\"content-type\":\"application\\/json\",\"access-control-allow-origin\":\"*\"},\"response_status\":500,\"response\":{\"message\":\"Function () does not exist\",\"exception\":\"ReflectionException\",\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\RouteSignatureParameters.php\",\"line\":27,\"trace\":[{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\RouteSignatureParameters.php\",\"line\":27,\"function\":\"__construct\",\"class\":\"ReflectionFunction\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\Route.php\",\"line\":545,\"function\":\"fromAction\",\"class\":\"Illuminate\\\\Routing\\\\RouteSignatureParameters\",\"type\":\"::\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\ImplicitRouteBinding.php\",\"line\":79,\"function\":\"signatureParameters\",\"class\":\"Illuminate\\\\Routing\\\\Route\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\ImplicitRouteBinding.php\",\"line\":27,\"function\":\"resolveBackedEnumsForRoute\",\"class\":\"Illuminate\\\\Routing\\\\ImplicitRouteBinding\",\"type\":\"::\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\Router.php\",\"line\":980,\"function\":\"resolveForRoute\",\"class\":\"Illuminate\\\\Routing\\\\ImplicitRouteBinding\",\"type\":\"::\"},{\"function\":\"{closure:Illuminate\\\\Routing\\\\Router::substituteImplicitBindings():980}\",\"class\":\"Illuminate\\\\Routing\\\\Router\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\Router.php\",\"line\":982,\"function\":\"call_user_func\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\Middleware\\\\SubstituteBindings.php\",\"line\":41,\"function\":\"substituteImplicitBindings\",\"class\":\"Illuminate\\\\Routing\\\\Router\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219,\"function\":\"handle\",\"class\":\"Illuminate\\\\Routing\\\\Middleware\\\\SubstituteBindings\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":137,\"function\":\"{closure:{closure:Illuminate\\\\Pipeline\\\\Pipeline::carry():194}:195}\",\"class\":\"Illuminate\\\\Pipeline\\\\Pipeline\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\Router.php\",\"line\":821,\"function\":\"then\",\"class\":\"Illuminate\\\\Pipeline\\\\Pipeline\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\Router.php\",\"line\":800,\"function\":\"runRouteWithinStack\",\"class\":\"Illuminate\\\\Routing\\\\Router\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\Router.php\",\"line\":764,\"function\":\"runRoute\",\"class\":\"Illuminate\\\\Routing\\\\Router\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\Router.php\",\"line\":753,\"function\":\"dispatchToRoute\",\"class\":\"Illuminate\\\\Routing\\\\Router\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Kernel.php\",\"line\":200,\"function\":\"dispatch\",\"class\":\"Illuminate\\\\Routing\\\\Router\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":180,\"function\":\"{closure:Illuminate\\\\Foundation\\\\Http\\\\Kernel::dispatchToRouter():197}\",\"class\":\"Illuminate\\\\Foundation\\\\Http\\\\Kernel\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\app\\\\Http\\\\Middleware\\\\UpdateUserSession.php\",\"line\":26,\"function\":\"{closure:Illuminate\\\\Pipeline\\\\Pipeline::prepareDestination():178}\",\"class\":\"Illuminate\\\\Pipeline\\\\Pipeline\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219,\"function\":\"handle\",\"class\":\"App\\\\Http\\\\Middleware\\\\UpdateUserSession\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\app\\\\Http\\\\Middleware\\\\PerformanceMonitoring.php\",\"line\":21,\"function\":\"{closure:{closure:Illuminate\\\\Pipeline\\\\Pipeline::carry():194}:195}\",\"class\":\"Illuminate\\\\Pipeline\\\\Pipeline\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219,\"function\":\"handle\",\"class\":\"App\\\\Http\\\\Middleware\\\\PerformanceMonitoring\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Middleware\\\\TransformsRequest.php\",\"line\":21,\"function\":\"{closure:{closure:Illuminate\\\\Pipeline\\\\Pipeline::carry():194}:195}\",\"class\":\"Illuminate\\\\Pipeline\\\\Pipeline\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Middleware\\\\ConvertEmptyStringsToNull.php\",\"line\":31,\"function\":\"handle\",\"class\":\"Illuminate\\\\Foundation\\\\Http\\\\Middleware\\\\TransformsRequest\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219,\"function\":\"handle\",\"class\":\"Illuminate\\\\Foundation\\\\Http\\\\Middleware\\\\ConvertEmptyStringsToNull\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Middleware\\\\TransformsRequest.php\",\"line\":21,\"function\":\"{closure:{closure:Illuminate\\\\Pipeline\\\\Pipeline::carry():194}:195}\",\"class\":\"Illuminate\\\\Pipeline\\\\Pipeline\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Middleware\\\\TrimStrings.php\",\"line\":51,\"function\":\"handle\",\"class\":\"Illuminate\\\\Foundation\\\\Http\\\\Middleware\\\\TransformsRequest\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219,\"function\":\"handle\",\"class\":\"Illuminate\\\\Foundation\\\\Http\\\\Middleware\\\\TrimStrings\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Http\\\\Middleware\\\\ValidatePostSize.php\",\"line\":27,\"function\":\"{closure:{closure:Illuminate\\\\Pipeline\\\\Pipeline::carry():194}:195}\",\"class\":\"Illuminate\\\\Pipeline\\\\Pipeline\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219,\"function\":\"handle\",\"class\":\"Illuminate\\\\Http\\\\Middleware\\\\ValidatePostSize\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Middleware\\\\PreventRequestsDuringMaintenance.php\",\"line\":109,\"function\":\"{closure:{closure:Illuminate\\\\Pipeline\\\\Pipeline::carry():194}:195}\",\"class\":\"Illuminate\\\\Pipeline\\\\Pipeline\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219,\"function\":\"handle\",\"class\":\"Illuminate\\\\Foundation\\\\Http\\\\Middleware\\\\PreventRequestsDuringMaintenance\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Http\\\\Middleware\\\\HandleCors.php\",\"line\":74,\"function\":\"{closure:{closure:Illuminate\\\\Pipeline\\\\Pipeline::carry():194}:195}\",\"class\":\"Illuminate\\\\Pipeline\\\\Pipeline\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219,\"function\":\"handle\",\"class\":\"Illuminate\\\\Http\\\\Middleware\\\\HandleCors\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Http\\\\Middleware\\\\TrustProxies.php\",\"line\":58,\"function\":\"{closure:{closure:Illuminate\\\\Pipeline\\\\Pipeline::carry():194}:195}\",\"class\":\"Illuminate\\\\Pipeline\\\\Pipeline\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219,\"function\":\"handle\",\"class\":\"Illuminate\\\\Http\\\\Middleware\\\\TrustProxies\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Middleware\\\\InvokeDeferredCallbacks.php\",\"line\":22,\"function\":\"{closure:{closure:Illuminate\\\\Pipeline\\\\Pipeline::carry():194}:195}\",\"class\":\"Illuminate\\\\Pipeline\\\\Pipeline\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219,\"function\":\"handle\",\"class\":\"Illuminate\\\\Foundation\\\\Http\\\\Middleware\\\\InvokeDeferredCallbacks\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Http\\\\Middleware\\\\ValidatePathEncoding.php\",\"line\":26,\"function\":\"{closure:{closure:Illuminate\\\\Pipeline\\\\Pipeline::carry():194}:195}\",\"class\":\"Illuminate\\\\Pipeline\\\\Pipeline\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219,\"function\":\"handle\",\"class\":\"Illuminate\\\\Http\\\\Middleware\\\\ValidatePathEncoding\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":137,\"function\":\"{closure:{closure:Illuminate\\\\Pipeline\\\\Pipeline::carry():194}:195}\",\"class\":\"Illuminate\\\\Pipeline\\\\Pipeline\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Kernel.php\",\"line\":175,\"function\":\"then\",\"class\":\"Illuminate\\\\Pipeline\\\\Pipeline\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Kernel.php\",\"line\":144,\"function\":\"sendRequestThroughRouter\",\"class\":\"Illuminate\\\\Foundation\\\\Http\\\\Kernel\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Application.php\",\"line\":1220,\"function\":\"handle\",\"class\":\"Illuminate\\\\Foundation\\\\Http\\\\Kernel\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\public\\\\index.php\",\"line\":20,\"function\":\"handleRequest\",\"class\":\"Illuminate\\\\Foundation\\\\Application\",\"type\":\"->\"},{\"file\":\"C:\\\\Program Files\\\\Herd\\\\resources\\\\app.asar.unpacked\\\\resources\\\\valet\\\\server.php\",\"line\":139,\"function\":\"require\"}]},\"duration\":81,\"memory\":4,\"hostname\":\"DESKTOP-GDIKUKP\"}', '2026-02-06 19:20:45'),
(3, 'a1045858-1853-458a-a0b5-368ec3b63b53', 'a1045867-c65a-42c3-b62e-b324fe32f598', '9ecbcb1b62f7bcc07a3fc26dc340657c', 0, 'exception', '{\"class\":\"Symfony\\\\Component\\\\Routing\\\\Exception\\\\RouteNotFoundException\",\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\UrlGenerator.php\",\"line\":526,\"message\":\"Route [login] not defined.\",\"context\":null,\"trace\":[{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\helpers.php\",\"line\":871},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Configuration\\\\ApplicationBuilder.php\",\"line\":278},[],{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Auth\\\\Middleware\\\\Authenticate.php\",\"line\":117},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Auth\\\\Middleware\\\\Authenticate.php\",\"line\":104},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Auth\\\\Middleware\\\\Authenticate.php\",\"line\":87},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Auth\\\\Middleware\\\\Authenticate.php\",\"line\":61},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":137},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\Router.php\",\"line\":821},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\Router.php\",\"line\":800},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\Router.php\",\"line\":764},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\Router.php\",\"line\":753},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Kernel.php\",\"line\":200},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":180},{\"file\":\"E:\\\\coupony-backend\\\\app\\\\Http\\\\Middleware\\\\UpdateUserSession.php\",\"line\":26},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219},{\"file\":\"E:\\\\coupony-backend\\\\app\\\\Http\\\\Middleware\\\\PerformanceMonitoring.php\",\"line\":21},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Middleware\\\\TransformsRequest.php\",\"line\":21},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Middleware\\\\ConvertEmptyStringsToNull.php\",\"line\":31},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Middleware\\\\TransformsRequest.php\",\"line\":21},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Middleware\\\\TrimStrings.php\",\"line\":51},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Http\\\\Middleware\\\\ValidatePostSize.php\",\"line\":27},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Middleware\\\\PreventRequestsDuringMaintenance.php\",\"line\":109},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Http\\\\Middleware\\\\HandleCors.php\",\"line\":74},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Http\\\\Middleware\\\\TrustProxies.php\",\"line\":58},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Middleware\\\\InvokeDeferredCallbacks.php\",\"line\":22},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Http\\\\Middleware\\\\ValidatePathEncoding.php\",\"line\":26},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":137},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Kernel.php\",\"line\":175},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Kernel.php\",\"line\":144},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Application.php\",\"line\":1220},{\"file\":\"E:\\\\coupony-backend\\\\public\\\\index.php\",\"line\":20},{\"file\":\"C:\\\\Program Files\\\\Herd\\\\resources\\\\app.asar.unpacked\\\\resources\\\\valet\\\\server.php\",\"line\":139}],\"line_preview\":{\"517\":\"        if (! is_null($route = $this->routes->getByName($name))) {\",\"518\":\"            return $this->toRoute($route, $parameters, $absolute);\",\"519\":\"        }\",\"520\":\"\",\"521\":\"        if (! is_null($this->missingNamedRouteResolver) &&\",\"522\":\"            ! is_null($url = call_user_func($this->missingNamedRouteResolver, $name, $parameters, $absolute))) {\",\"523\":\"            return $url;\",\"524\":\"        }\",\"525\":\"\",\"526\":\"        throw new RouteNotFoundException(\\\"Route [{$name}] not defined.\\\");\",\"527\":\"    }\",\"528\":\"\",\"529\":\"    \\/**\",\"530\":\"     * Get the URL for a given route instance.\",\"531\":\"     *\",\"532\":\"     * @param  \\\\Illuminate\\\\Routing\\\\Route  $route\",\"533\":\"     * @param  mixed  $parameters\",\"534\":\"     * @param  bool  $absolute\",\"535\":\"     * @return string\",\"536\":\"     *\"},\"hostname\":\"DESKTOP-GDIKUKP\",\"occurrences\":1}', '2026-02-06 19:52:21'),
(4, 'a1045867-c54a-45a4-b4c3-d21855861f07', 'a1045867-c65a-42c3-b62e-b324fe32f598', NULL, 1, 'request', '{\"ip_address\":\"127.0.0.1\",\"uri\":\"\\/api\\/v1\\/admin\\/notify-me\\/list\",\"method\":\"GET\",\"controller_action\":\"App\\\\Application\\\\Http\\\\Controllers\\\\API\\\\V1\\\\NotifyMeController@list\",\"middleware\":[\"api\",\"Spatie\\\\Permission\\\\Middleware\\\\RoleMiddleware:admin\",\"auth:sanctum\"],\"headers\":{\"connection\":\"keep-alive\",\"accept-encoding\":\"gzip, deflate, br\",\"host\":\"coupony-backend.test\",\"postman-token\":\"02d6ba98-8f40-4225-8ee0-1b5a1410b88b\",\"cache-control\":\"no-cache\",\"accept\":\"*\\/*\",\"user-agent\":\"PostmanRuntime\\/7.51.1\"},\"payload\":[],\"session\":[],\"response_headers\":{\"cache-control\":\"no-cache, private\",\"date\":\"Fri, 06 Feb 2026 17:52:31 GMT\",\"content-type\":\"text\\/html; charset=utf-8\",\"access-control-allow-origin\":\"*\"},\"response_status\":500,\"response\":\"HTML Response\",\"duration\":10331,\"memory\":12,\"hostname\":\"DESKTOP-GDIKUKP\"}', '2026-02-06 19:52:31'),
(5, 'a1063e41-adba-419e-858e-6ea582671025', 'a1063e44-3b7f-44f0-bf3b-d5aef39bf93c', '0d8e599ca01a32e2197427b0c9b53b6d', 1, 'exception', '{\"class\":\"Symfony\\\\Component\\\\Mailer\\\\Exception\\\\UnexpectedResponseException\",\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\symfony\\\\mailer\\\\Transport\\\\Smtp\\\\SmtpTransport.php\",\"line\":331,\"message\":\"Expected response code \\\"250\\\" but got code \\\"421\\\", with message \\\"421 4.4.2 smtp.hostinger.com Error: timeout exceeded\\\".\",\"context\":null,\"trace\":[{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\symfony\\\\mailer\\\\Transport\\\\Smtp\\\\SmtpTransport.php\",\"line\":187},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\symfony\\\\mailer\\\\Transport\\\\Smtp\\\\EsmtpTransport.php\",\"line\":150},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\symfony\\\\mailer\\\\Transport\\\\Smtp\\\\SmtpTransport.php\",\"line\":252},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\symfony\\\\mailer\\\\Transport\\\\Smtp\\\\SmtpTransport.php\",\"line\":204},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\symfony\\\\mailer\\\\Transport\\\\AbstractTransport.php\",\"line\":69},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\symfony\\\\mailer\\\\Transport\\\\Smtp\\\\SmtpTransport.php\",\"line\":138},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Mail\\\\Mailer.php\",\"line\":584},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Mail\\\\Mailer.php\",\"line\":331},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Mail\\\\Mailable.php\",\"line\":207},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Support\\\\Traits\\\\Localizable.php\",\"line\":19},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Mail\\\\Mailable.php\",\"line\":200},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Mail\\\\SendQueuedMailable.php\",\"line\":82},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Container\\\\BoundMethod.php\",\"line\":36},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Container\\\\Util.php\",\"line\":43},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Container\\\\BoundMethod.php\",\"line\":96},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Container\\\\BoundMethod.php\",\"line\":35},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Container\\\\Container.php\",\"line\":799},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Bus\\\\Dispatcher.php\",\"line\":129},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":180},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":137},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Bus\\\\Dispatcher.php\",\"line\":133},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Queue\\\\CallQueuedHandler.php\",\"line\":134},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":180},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":137},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Queue\\\\CallQueuedHandler.php\",\"line\":127},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Queue\\\\CallQueuedHandler.php\",\"line\":68},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Queue\\\\Jobs\\\\Job.php\",\"line\":102},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Queue\\\\Worker.php\",\"line\":485},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Queue\\\\Worker.php\",\"line\":435},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Queue\\\\Worker.php\",\"line\":201},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Queue\\\\Console\\\\WorkCommand.php\",\"line\":148},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Queue\\\\Console\\\\WorkCommand.php\",\"line\":131},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Container\\\\BoundMethod.php\",\"line\":36},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Container\\\\Util.php\",\"line\":43},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Container\\\\BoundMethod.php\",\"line\":96},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Container\\\\BoundMethod.php\",\"line\":35},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Container\\\\Container.php\",\"line\":799},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Console\\\\Command.php\",\"line\":211},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\symfony\\\\console\\\\Command\\\\Command.php\",\"line\":341},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Console\\\\Command.php\",\"line\":180},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\symfony\\\\console\\\\Application.php\",\"line\":1102},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\symfony\\\\console\\\\Application.php\",\"line\":356},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\symfony\\\\console\\\\Application.php\",\"line\":195},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Console\\\\Kernel.php\",\"line\":198},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Application.php\",\"line\":1235},{\"file\":\"E:\\\\coupony-backend\\\\artisan\",\"line\":16}],\"line_preview\":{\"322\":\"        }\",\"323\":\"\",\"324\":\"        [$code] = sscanf($response, \'%3d\');\",\"325\":\"        $valid = \\\\in_array($code, $codes);\",\"326\":\"\",\"327\":\"        if (!$valid || !$response) {\",\"328\":\"            $codeStr = $code ? \\\\sprintf(\'code \\\"%s\\\"\', $code) : \'empty code\';\",\"329\":\"            $responseStr = $response ? \\\\sprintf(\', with message \\\"%s\\\"\', trim($response)) : \'\';\",\"330\":\"\",\"331\":\"            throw new UnexpectedResponseException(\\\\sprintf(\'Expected response code \\\"%s\\\" but got \', implode(\'\\/\', $codes)).$codeStr.$responseStr.\'.\', $code ?: 0);\",\"332\":\"        }\",\"333\":\"    }\",\"334\":\"\",\"335\":\"    private function getFullResponse(): string\",\"336\":\"    {\",\"337\":\"        $response = \'\';\",\"338\":\"        do {\",\"339\":\"            $line = $this->stream->readLine();\",\"340\":\"            $response .= $line;\",\"341\":\"        } while ($line && isset($line[3]) && \' \' !== $line[3]);\"},\"hostname\":\"DESKTOP-GDIKUKP\",\"occurrences\":1}', '2026-02-07 18:31:03'),
(6, 'a1064251-a751-4ee3-b146-a1a2baccdff9', 'a1064251-f45a-4274-a405-d36453c27700', 'a4c5b87a53e1681958ab14e7934c9917', 1, 'exception', '{\"class\":\"Illuminate\\\\Broadcasting\\\\BroadcastException\",\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Broadcasting\\\\Broadcasters\\\\PusherBroadcaster.php\",\"line\":163,\"message\":\"Pusher error: <!DOCTYPE HTML PUBLIC \\\"-\\/\\/IETF\\/\\/DTD HTML 2.0\\/\\/EN\\\">\\n<html><head>\\n<title>404 Not Found<\\/title>\\n<\\/head><body>\\n<h1>Not Found<\\/h1>\\n<p>The requested URL was not found on this server.<\\/p>\\n<hr>\\n<address>Apache\\/2.4.58 (Win64) OpenSSL\\/3.1.3 PHP\\/8.2.12 Server at localhost Port 8080<\\/address>\\n<\\/body><\\/html>\\n.\",\"context\":null,\"trace\":[{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Broadcasting\\\\BroadcastEvent.php\",\"line\":100},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Container\\\\BoundMethod.php\",\"line\":36},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Container\\\\Util.php\",\"line\":43},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Container\\\\BoundMethod.php\",\"line\":96},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Container\\\\BoundMethod.php\",\"line\":35},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Container\\\\Container.php\",\"line\":799},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Bus\\\\Dispatcher.php\",\"line\":129},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":180},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":137},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Bus\\\\Dispatcher.php\",\"line\":133},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Queue\\\\CallQueuedHandler.php\",\"line\":134},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":180},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":137},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Queue\\\\CallQueuedHandler.php\",\"line\":127},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Queue\\\\CallQueuedHandler.php\",\"line\":68},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Queue\\\\Jobs\\\\Job.php\",\"line\":102},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Queue\\\\Worker.php\",\"line\":485},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Queue\\\\Worker.php\",\"line\":435},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Queue\\\\Worker.php\",\"line\":201},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Queue\\\\Console\\\\WorkCommand.php\",\"line\":148},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Queue\\\\Console\\\\WorkCommand.php\",\"line\":131},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Container\\\\BoundMethod.php\",\"line\":36},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Container\\\\Util.php\",\"line\":43},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Container\\\\BoundMethod.php\",\"line\":96},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Container\\\\BoundMethod.php\",\"line\":35},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Container\\\\Container.php\",\"line\":799},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Console\\\\Command.php\",\"line\":211},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\symfony\\\\console\\\\Command\\\\Command.php\",\"line\":341},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Console\\\\Command.php\",\"line\":180},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\symfony\\\\console\\\\Application.php\",\"line\":1102},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\symfony\\\\console\\\\Application.php\",\"line\":356},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\symfony\\\\console\\\\Application.php\",\"line\":195},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Console\\\\Kernel.php\",\"line\":198},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Application.php\",\"line\":1235},{\"file\":\"E:\\\\coupony-backend\\\\artisan\",\"line\":16}],\"line_preview\":{\"154\":\"        $parameters = $socket !== null ? [\'socket_id\' => $socket] : [];\",\"155\":\"\",\"156\":\"        $channels = new Collection($this->formatChannels($channels));\",\"157\":\"\",\"158\":\"        try {\",\"159\":\"            $channels->chunk(100)->each(function ($channels) use ($event, $payload, $parameters) {\",\"160\":\"                $this->pusher->trigger($channels->toArray(), $event, $payload, $parameters);\",\"161\":\"            });\",\"162\":\"        } catch (ApiErrorException $e) {\",\"163\":\"            throw new BroadcastException(\",\"164\":\"                sprintf(\'Pusher error: %s.\', $e->getMessage())\",\"165\":\"            );\",\"166\":\"        }\",\"167\":\"    }\",\"168\":\"\",\"169\":\"    \\/**\",\"170\":\"     * Get the Pusher SDK instance.\",\"171\":\"     *\",\"172\":\"     * @return \\\\Pusher\\\\Pusher\",\"173\":\"     *\\/\"},\"hostname\":\"DESKTOP-GDIKUKP\",\"occurrences\":1}', '2026-02-07 18:42:25'),
(7, 'a1064251-c940-44cf-b432-a141ea46f490', 'a1064251-f45a-4274-a405-d36453c27700', 'a4c5b87a53e1681958ab14e7934c9917', 1, 'exception', '{\"class\":\"Illuminate\\\\Broadcasting\\\\BroadcastException\",\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Broadcasting\\\\Broadcasters\\\\PusherBroadcaster.php\",\"line\":163,\"message\":\"Pusher error: <!DOCTYPE HTML PUBLIC \\\"-\\/\\/IETF\\/\\/DTD HTML 2.0\\/\\/EN\\\">\\n<html><head>\\n<title>404 Not Found<\\/title>\\n<\\/head><body>\\n<h1>Not Found<\\/h1>\\n<p>The requested URL was not found on this server.<\\/p>\\n<hr>\\n<address>Apache\\/2.4.58 (Win64) OpenSSL\\/3.1.3 PHP\\/8.2.12 Server at localhost Port 8080<\\/address>\\n<\\/body><\\/html>\\n.\",\"context\":null,\"trace\":[{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Broadcasting\\\\BroadcastEvent.php\",\"line\":100},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Container\\\\BoundMethod.php\",\"line\":36},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Container\\\\Util.php\",\"line\":43},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Container\\\\BoundMethod.php\",\"line\":96},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Container\\\\BoundMethod.php\",\"line\":35},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Container\\\\Container.php\",\"line\":799},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Bus\\\\Dispatcher.php\",\"line\":129},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":180},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":137},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Bus\\\\Dispatcher.php\",\"line\":133},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Queue\\\\CallQueuedHandler.php\",\"line\":134},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":180},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":137},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Queue\\\\CallQueuedHandler.php\",\"line\":127},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Queue\\\\CallQueuedHandler.php\",\"line\":68},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Queue\\\\Jobs\\\\Job.php\",\"line\":102},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Queue\\\\Worker.php\",\"line\":485},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Queue\\\\Worker.php\",\"line\":435},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Queue\\\\Worker.php\",\"line\":201},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Queue\\\\Console\\\\WorkCommand.php\",\"line\":148},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Queue\\\\Console\\\\WorkCommand.php\",\"line\":131},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Container\\\\BoundMethod.php\",\"line\":36},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Container\\\\Util.php\",\"line\":43},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Container\\\\BoundMethod.php\",\"line\":96},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Container\\\\BoundMethod.php\",\"line\":35},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Container\\\\Container.php\",\"line\":799},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Console\\\\Command.php\",\"line\":211},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\symfony\\\\console\\\\Command\\\\Command.php\",\"line\":341},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Console\\\\Command.php\",\"line\":180},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\symfony\\\\console\\\\Application.php\",\"line\":1102},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\symfony\\\\console\\\\Application.php\",\"line\":356},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\symfony\\\\console\\\\Application.php\",\"line\":195},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Console\\\\Kernel.php\",\"line\":198},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Application.php\",\"line\":1235},{\"file\":\"E:\\\\coupony-backend\\\\artisan\",\"line\":16}],\"line_preview\":{\"154\":\"        $parameters = $socket !== null ? [\'socket_id\' => $socket] : [];\",\"155\":\"\",\"156\":\"        $channels = new Collection($this->formatChannels($channels));\",\"157\":\"\",\"158\":\"        try {\",\"159\":\"            $channels->chunk(100)->each(function ($channels) use ($event, $payload, $parameters) {\",\"160\":\"                $this->pusher->trigger($channels->toArray(), $event, $payload, $parameters);\",\"161\":\"            });\",\"162\":\"        } catch (ApiErrorException $e) {\",\"163\":\"            throw new BroadcastException(\",\"164\":\"                sprintf(\'Pusher error: %s.\', $e->getMessage())\",\"165\":\"            );\",\"166\":\"        }\",\"167\":\"    }\",\"168\":\"\",\"169\":\"    \\/**\",\"170\":\"     * Get the Pusher SDK instance.\",\"171\":\"     *\",\"172\":\"     * @return \\\\Pusher\\\\Pusher\",\"173\":\"     *\\/\"},\"hostname\":\"DESKTOP-GDIKUKP\",\"occurrences\":1}', '2026-02-07 18:42:25');
INSERT INTO `telescope_entries` (`sequence`, `uuid`, `batch_id`, `family_hash`, `should_display_on_index`, `type`, `content`, `created_at`) VALUES
(8, 'a106cedc-3875-42c4-8bf1-5883dbf1d088', 'a106ceec-e054-44bf-b161-5f330252c168', '9ecbcb1b62f7bcc07a3fc26dc340657c', 0, 'exception', '{\"class\":\"Symfony\\\\Component\\\\Routing\\\\Exception\\\\RouteNotFoundException\",\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\UrlGenerator.php\",\"line\":526,\"message\":\"Route [login] not defined.\",\"context\":null,\"trace\":[{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\helpers.php\",\"line\":871},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Configuration\\\\ApplicationBuilder.php\",\"line\":278},[],{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Auth\\\\Middleware\\\\Authenticate.php\",\"line\":117},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Auth\\\\Middleware\\\\Authenticate.php\",\"line\":104},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Auth\\\\Middleware\\\\Authenticate.php\",\"line\":87},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Auth\\\\Middleware\\\\Authenticate.php\",\"line\":61},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":137},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\Router.php\",\"line\":821},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\Router.php\",\"line\":800},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\Router.php\",\"line\":764},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\Router.php\",\"line\":753},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Kernel.php\",\"line\":200},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":180},{\"file\":\"E:\\\\coupony-backend\\\\app\\\\Http\\\\Middleware\\\\UpdateUserSession.php\",\"line\":26},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219},{\"file\":\"E:\\\\coupony-backend\\\\app\\\\Http\\\\Middleware\\\\PerformanceMonitoring.php\",\"line\":21},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Middleware\\\\TransformsRequest.php\",\"line\":21},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Middleware\\\\ConvertEmptyStringsToNull.php\",\"line\":31},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Middleware\\\\TransformsRequest.php\",\"line\":21},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Middleware\\\\TrimStrings.php\",\"line\":51},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Http\\\\Middleware\\\\ValidatePostSize.php\",\"line\":27},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Middleware\\\\PreventRequestsDuringMaintenance.php\",\"line\":109},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Http\\\\Middleware\\\\HandleCors.php\",\"line\":74},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Http\\\\Middleware\\\\TrustProxies.php\",\"line\":58},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Middleware\\\\InvokeDeferredCallbacks.php\",\"line\":22},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Http\\\\Middleware\\\\ValidatePathEncoding.php\",\"line\":26},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":137},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Kernel.php\",\"line\":175},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Kernel.php\",\"line\":144},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Application.php\",\"line\":1220},{\"file\":\"E:\\\\coupony-backend\\\\public\\\\index.php\",\"line\":20},{\"file\":\"C:\\\\Program Files\\\\Herd\\\\resources\\\\app.asar.unpacked\\\\resources\\\\valet\\\\server.php\",\"line\":139}],\"line_preview\":{\"517\":\"        if (! is_null($route = $this->routes->getByName($name))) {\",\"518\":\"            return $this->toRoute($route, $parameters, $absolute);\",\"519\":\"        }\",\"520\":\"\",\"521\":\"        if (! is_null($this->missingNamedRouteResolver) &&\",\"522\":\"            ! is_null($url = call_user_func($this->missingNamedRouteResolver, $name, $parameters, $absolute))) {\",\"523\":\"            return $url;\",\"524\":\"        }\",\"525\":\"\",\"526\":\"        throw new RouteNotFoundException(\\\"Route [{$name}] not defined.\\\");\",\"527\":\"    }\",\"528\":\"\",\"529\":\"    \\/**\",\"530\":\"     * Get the URL for a given route instance.\",\"531\":\"     *\",\"532\":\"     * @param  \\\\Illuminate\\\\Routing\\\\Route  $route\",\"533\":\"     * @param  mixed  $parameters\",\"534\":\"     * @param  bool  $absolute\",\"535\":\"     * @return string\",\"536\":\"     *\"},\"hostname\":\"DESKTOP-GDIKUKP\",\"occurrences\":2}', '2026-02-08 01:15:24'),
(9, 'a106ceec-ded2-4271-a7e9-cbbab36166f8', 'a106ceec-e054-44bf-b161-5f330252c168', NULL, 1, 'request', '{\"ip_address\":\"127.0.0.1\",\"uri\":\"\\/api\\/v1\\/admin\\/store-category\\/1\",\"method\":\"PUT\",\"controller_action\":\"App\\\\Application\\\\Http\\\\Controllers\\\\API\\\\V1\\\\StoreCategoryController@update\",\"middleware\":[\"api\",\"Spatie\\\\Permission\\\\Middleware\\\\RoleMiddleware:admin\",\"auth:sanctum\"],\"headers\":{\"content-length\":\"49\",\"connection\":\"keep-alive\",\"accept-encoding\":\"gzip, deflate, br\",\"host\":\"coupony-backend.test\",\"postman-token\":\"bcfc7217-ccf5-4559-ba14-7bd7924f287f\",\"cache-control\":\"no-cache\",\"accept\":\"*\\/*\",\"user-agent\":\"PostmanRuntime\\/7.51.1\",\"authorization\":\"********\",\"content-type\":\"application\\/json\"},\"payload\":{\"name\":\"cafe\",\"is_active\":false},\"session\":[],\"response_headers\":{\"cache-control\":\"no-cache, private\",\"date\":\"Sat, 07 Feb 2026 23:15:35 GMT\",\"content-type\":\"text\\/html; charset=utf-8\",\"access-control-allow-origin\":\"*\"},\"response_status\":500,\"response\":\"HTML Response\",\"duration\":10976,\"memory\":12,\"hostname\":\"DESKTOP-GDIKUKP\"}', '2026-02-08 01:15:35'),
(10, 'a106cf1a-7e6c-4d12-b7ea-3c8e0dccb288', 'a106cf1a-8001-4143-913f-0d99bdb8aef6', '5c868616b295a2d7334b95426ec5f673', 0, 'exception', '{\"class\":\"BadMethodCallException\",\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Validation\\\\Validator.php\",\"line\":1733,\"message\":\"Method Illuminate\\\\Validation\\\\Validator::validateRequeired does not exist.\",\"context\":{\"userId\":\"a496c4b6-719e-4aee-bb31-c7cb1063522e\"},\"trace\":[{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Validation\\\\Validator.php\",\"line\":686},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Validation\\\\Validator.php\",\"line\":481},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Validation\\\\Validator.php\",\"line\":516},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Validation\\\\ValidatesWhenResolvedTrait.php\",\"line\":31},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Providers\\\\FormRequestServiceProvider.php\",\"line\":30},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Container\\\\Container.php\",\"line\":1622},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Container\\\\Container.php\",\"line\":1560},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Container\\\\Container.php\",\"line\":1546},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Container\\\\Container.php\",\"line\":951},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Application.php\",\"line\":1078},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Container\\\\Container.php\",\"line\":864},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Application.php\",\"line\":1058},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\ResolvesRouteDependencies.php\",\"line\":92},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\ResolvesRouteDependencies.php\",\"line\":51},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\ResolvesRouteDependencies.php\",\"line\":30},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\ControllerDispatcher.php\",\"line\":59},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\ControllerDispatcher.php\",\"line\":40},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\Route.php\",\"line\":265},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\Route.php\",\"line\":211},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\Router.php\",\"line\":822},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":180},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\spatie\\\\laravel-permission\\\\src\\\\Middleware\\\\RoleMiddleware.php\",\"line\":37},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\Middleware\\\\SubstituteBindings.php\",\"line\":50},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Auth\\\\Middleware\\\\Authenticate.php\",\"line\":63},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":137},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\Router.php\",\"line\":821},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\Router.php\",\"line\":800},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\Router.php\",\"line\":764},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\Router.php\",\"line\":753},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Kernel.php\",\"line\":200},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":180},{\"file\":\"E:\\\\coupony-backend\\\\app\\\\Http\\\\Middleware\\\\UpdateUserSession.php\",\"line\":26},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219},{\"file\":\"E:\\\\coupony-backend\\\\app\\\\Http\\\\Middleware\\\\PerformanceMonitoring.php\",\"line\":21},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Middleware\\\\TransformsRequest.php\",\"line\":21},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Middleware\\\\ConvertEmptyStringsToNull.php\",\"line\":31},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Middleware\\\\TransformsRequest.php\",\"line\":21},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Middleware\\\\TrimStrings.php\",\"line\":51},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Http\\\\Middleware\\\\ValidatePostSize.php\",\"line\":27},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Middleware\\\\PreventRequestsDuringMaintenance.php\",\"line\":109},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Http\\\\Middleware\\\\HandleCors.php\",\"line\":74},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Http\\\\Middleware\\\\TrustProxies.php\",\"line\":58},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Middleware\\\\InvokeDeferredCallbacks.php\",\"line\":22},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Http\\\\Middleware\\\\ValidatePathEncoding.php\",\"line\":26},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":137},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Kernel.php\",\"line\":175},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Kernel.php\",\"line\":144},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Application.php\",\"line\":1220},{\"file\":\"E:\\\\coupony-backend\\\\public\\\\index.php\",\"line\":20},{\"file\":\"C:\\\\Program Files\\\\Herd\\\\resources\\\\app.asar.unpacked\\\\resources\\\\valet\\\\server.php\",\"line\":139}],\"line_preview\":{\"1724\":\"     *\\/\",\"1725\":\"    public function __call($method, $parameters)\",\"1726\":\"    {\",\"1727\":\"        $rule = Str::snake(substr($method, 8));\",\"1728\":\"\",\"1729\":\"        if (isset($this->extensions[$rule])) {\",\"1730\":\"            return $this->callExtension($rule, $parameters);\",\"1731\":\"        }\",\"1732\":\"\",\"1733\":\"        throw new BadMethodCallException(sprintf(\",\"1734\":\"            \'Method %s::%s does not exist.\', static::class, $method\",\"1735\":\"        ));\",\"1736\":\"    }\",\"1737\":\"}\",\"1738\":\"\"},\"hostname\":\"DESKTOP-GDIKUKP\",\"user\":{\"id\":\"a496c4b6-719e-4aee-bb31-c7cb1063522e\",\"name\":null,\"email\":\"super.admin@coupony.shop\"},\"occurrences\":1}', '2026-02-08 01:16:04'),
(11, 'a106cf1a-7f93-4831-9386-7c62977c16db', 'a106cf1a-8001-4143-913f-0d99bdb8aef6', NULL, 1, 'request', '{\"ip_address\":\"127.0.0.1\",\"uri\":\"\\/api\\/v1\\/admin\\/store-category\\/1\",\"method\":\"PUT\",\"controller_action\":\"App\\\\Application\\\\Http\\\\Controllers\\\\API\\\\V1\\\\StoreCategoryController@update\",\"middleware\":[\"api\",\"Spatie\\\\Permission\\\\Middleware\\\\RoleMiddleware:admin\",\"auth:sanctum\"],\"headers\":{\"content-length\":\"49\",\"connection\":\"keep-alive\",\"accept-encoding\":\"gzip, deflate, br\",\"host\":\"coupony-backend.test\",\"postman-token\":\"ee5b9815-1e72-48df-a91c-d96b26618444\",\"cache-control\":\"no-cache\",\"user-agent\":\"PostmanRuntime\\/7.51.1\",\"authorization\":\"********\",\"content-type\":\"application\\/json\",\"accept\":\"application\\/json\"},\"payload\":{\"name\":\"cafe\",\"is_active\":false},\"session\":[],\"response_headers\":{\"cache-control\":\"no-cache, private\",\"date\":\"Sat, 07 Feb 2026 23:16:04 GMT\",\"content-type\":\"application\\/json\",\"access-control-allow-origin\":\"*\"},\"response_status\":500,\"response\":{\"message\":\"Method Illuminate\\\\Validation\\\\Validator::validateRequeired does not exist.\",\"exception\":\"BadMethodCallException\",\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Validation\\\\Validator.php\",\"line\":1733,\"trace\":[{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Validation\\\\Validator.php\",\"line\":686,\"function\":\"__call\",\"class\":\"Illuminate\\\\Validation\\\\Validator\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Validation\\\\Validator.php\",\"line\":481,\"function\":\"validateAttribute\",\"class\":\"Illuminate\\\\Validation\\\\Validator\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Validation\\\\Validator.php\",\"line\":516,\"function\":\"passes\",\"class\":\"Illuminate\\\\Validation\\\\Validator\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Validation\\\\ValidatesWhenResolvedTrait.php\",\"line\":31,\"function\":\"fails\",\"class\":\"Illuminate\\\\Validation\\\\Validator\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Providers\\\\FormRequestServiceProvider.php\",\"line\":30,\"function\":\"validateResolved\",\"class\":\"Illuminate\\\\Foundation\\\\Http\\\\FormRequest\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Container\\\\Container.php\",\"line\":1622,\"function\":\"{closure:Illuminate\\\\Foundation\\\\Providers\\\\FormRequestServiceProvider::boot():29}\",\"class\":\"Illuminate\\\\Foundation\\\\Providers\\\\FormRequestServiceProvider\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Container\\\\Container.php\",\"line\":1560,\"function\":\"fireCallbackArray\",\"class\":\"Illuminate\\\\Container\\\\Container\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Container\\\\Container.php\",\"line\":1546,\"function\":\"fireAfterResolvingCallbacks\",\"class\":\"Illuminate\\\\Container\\\\Container\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Container\\\\Container.php\",\"line\":951,\"function\":\"fireResolvingCallbacks\",\"class\":\"Illuminate\\\\Container\\\\Container\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Application.php\",\"line\":1078,\"function\":\"resolve\",\"class\":\"Illuminate\\\\Container\\\\Container\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Container\\\\Container.php\",\"line\":864,\"function\":\"resolve\",\"class\":\"Illuminate\\\\Foundation\\\\Application\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Application.php\",\"line\":1058,\"function\":\"make\",\"class\":\"Illuminate\\\\Container\\\\Container\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\ResolvesRouteDependencies.php\",\"line\":92,\"function\":\"make\",\"class\":\"Illuminate\\\\Foundation\\\\Application\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\ResolvesRouteDependencies.php\",\"line\":51,\"function\":\"transformDependency\",\"class\":\"Illuminate\\\\Routing\\\\ControllerDispatcher\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\ResolvesRouteDependencies.php\",\"line\":30,\"function\":\"resolveMethodDependencies\",\"class\":\"Illuminate\\\\Routing\\\\ControllerDispatcher\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\ControllerDispatcher.php\",\"line\":59,\"function\":\"resolveClassMethodDependencies\",\"class\":\"Illuminate\\\\Routing\\\\ControllerDispatcher\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\ControllerDispatcher.php\",\"line\":40,\"function\":\"resolveParameters\",\"class\":\"Illuminate\\\\Routing\\\\ControllerDispatcher\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\Route.php\",\"line\":265,\"function\":\"dispatch\",\"class\":\"Illuminate\\\\Routing\\\\ControllerDispatcher\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\Route.php\",\"line\":211,\"function\":\"runController\",\"class\":\"Illuminate\\\\Routing\\\\Route\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\Router.php\",\"line\":822,\"function\":\"run\",\"class\":\"Illuminate\\\\Routing\\\\Route\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":180,\"function\":\"{closure:Illuminate\\\\Routing\\\\Router::runRouteWithinStack():821}\",\"class\":\"Illuminate\\\\Routing\\\\Router\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\spatie\\\\laravel-permission\\\\src\\\\Middleware\\\\RoleMiddleware.php\",\"line\":37,\"function\":\"{closure:Illuminate\\\\Pipeline\\\\Pipeline::prepareDestination():178}\",\"class\":\"Illuminate\\\\Pipeline\\\\Pipeline\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219,\"function\":\"handle\",\"class\":\"Spatie\\\\Permission\\\\Middleware\\\\RoleMiddleware\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\Middleware\\\\SubstituteBindings.php\",\"line\":50,\"function\":\"{closure:{closure:Illuminate\\\\Pipeline\\\\Pipeline::carry():194}:195}\",\"class\":\"Illuminate\\\\Pipeline\\\\Pipeline\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219,\"function\":\"handle\",\"class\":\"Illuminate\\\\Routing\\\\Middleware\\\\SubstituteBindings\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Auth\\\\Middleware\\\\Authenticate.php\",\"line\":63,\"function\":\"{closure:{closure:Illuminate\\\\Pipeline\\\\Pipeline::carry():194}:195}\",\"class\":\"Illuminate\\\\Pipeline\\\\Pipeline\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219,\"function\":\"handle\",\"class\":\"Illuminate\\\\Auth\\\\Middleware\\\\Authenticate\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":137,\"function\":\"{closure:{closure:Illuminate\\\\Pipeline\\\\Pipeline::carry():194}:195}\",\"class\":\"Illuminate\\\\Pipeline\\\\Pipeline\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\Router.php\",\"line\":821,\"function\":\"then\",\"class\":\"Illuminate\\\\Pipeline\\\\Pipeline\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\Router.php\",\"line\":800,\"function\":\"runRouteWithinStack\",\"class\":\"Illuminate\\\\Routing\\\\Router\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\Router.php\",\"line\":764,\"function\":\"runRoute\",\"class\":\"Illuminate\\\\Routing\\\\Router\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\Router.php\",\"line\":753,\"function\":\"dispatchToRoute\",\"class\":\"Illuminate\\\\Routing\\\\Router\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Kernel.php\",\"line\":200,\"function\":\"dispatch\",\"class\":\"Illuminate\\\\Routing\\\\Router\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":180,\"function\":\"{closure:Illuminate\\\\Foundation\\\\Http\\\\Kernel::dispatchToRouter():197}\",\"class\":\"Illuminate\\\\Foundation\\\\Http\\\\Kernel\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\app\\\\Http\\\\Middleware\\\\UpdateUserSession.php\",\"line\":26,\"function\":\"{closure:Illuminate\\\\Pipeline\\\\Pipeline::prepareDestination():178}\",\"class\":\"Illuminate\\\\Pipeline\\\\Pipeline\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219,\"function\":\"handle\",\"class\":\"App\\\\Http\\\\Middleware\\\\UpdateUserSession\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\app\\\\Http\\\\Middleware\\\\PerformanceMonitoring.php\",\"line\":21,\"function\":\"{closure:{closure:Illuminate\\\\Pipeline\\\\Pipeline::carry():194}:195}\",\"class\":\"Illuminate\\\\Pipeline\\\\Pipeline\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219,\"function\":\"handle\",\"class\":\"App\\\\Http\\\\Middleware\\\\PerformanceMonitoring\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Middleware\\\\TransformsRequest.php\",\"line\":21,\"function\":\"{closure:{closure:Illuminate\\\\Pipeline\\\\Pipeline::carry():194}:195}\",\"class\":\"Illuminate\\\\Pipeline\\\\Pipeline\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Middleware\\\\ConvertEmptyStringsToNull.php\",\"line\":31,\"function\":\"handle\",\"class\":\"Illuminate\\\\Foundation\\\\Http\\\\Middleware\\\\TransformsRequest\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219,\"function\":\"handle\",\"class\":\"Illuminate\\\\Foundation\\\\Http\\\\Middleware\\\\ConvertEmptyStringsToNull\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Middleware\\\\TransformsRequest.php\",\"line\":21,\"function\":\"{closure:{closure:Illuminate\\\\Pipeline\\\\Pipeline::carry():194}:195}\",\"class\":\"Illuminate\\\\Pipeline\\\\Pipeline\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Middleware\\\\TrimStrings.php\",\"line\":51,\"function\":\"handle\",\"class\":\"Illuminate\\\\Foundation\\\\Http\\\\Middleware\\\\TransformsRequest\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219,\"function\":\"handle\",\"class\":\"Illuminate\\\\Foundation\\\\Http\\\\Middleware\\\\TrimStrings\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Http\\\\Middleware\\\\ValidatePostSize.php\",\"line\":27,\"function\":\"{closure:{closure:Illuminate\\\\Pipeline\\\\Pipeline::carry():194}:195}\",\"class\":\"Illuminate\\\\Pipeline\\\\Pipeline\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219,\"function\":\"handle\",\"class\":\"Illuminate\\\\Http\\\\Middleware\\\\ValidatePostSize\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Middleware\\\\PreventRequestsDuringMaintenance.php\",\"line\":109,\"function\":\"{closure:{closure:Illuminate\\\\Pipeline\\\\Pipeline::carry():194}:195}\",\"class\":\"Illuminate\\\\Pipeline\\\\Pipeline\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219,\"function\":\"handle\",\"class\":\"Illuminate\\\\Foundation\\\\Http\\\\Middleware\\\\PreventRequestsDuringMaintenance\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Http\\\\Middleware\\\\HandleCors.php\",\"line\":74,\"function\":\"{closure:{closure:Illuminate\\\\Pipeline\\\\Pipeline::carry():194}:195}\",\"class\":\"Illuminate\\\\Pipeline\\\\Pipeline\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219,\"function\":\"handle\",\"class\":\"Illuminate\\\\Http\\\\Middleware\\\\HandleCors\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Http\\\\Middleware\\\\TrustProxies.php\",\"line\":58,\"function\":\"{closure:{closure:Illuminate\\\\Pipeline\\\\Pipeline::carry():194}:195}\",\"class\":\"Illuminate\\\\Pipeline\\\\Pipeline\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219,\"function\":\"handle\",\"class\":\"Illuminate\\\\Http\\\\Middleware\\\\TrustProxies\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Middleware\\\\InvokeDeferredCallbacks.php\",\"line\":22,\"function\":\"{closure:{closure:Illuminate\\\\Pipeline\\\\Pipeline::carry():194}:195}\",\"class\":\"Illuminate\\\\Pipeline\\\\Pipeline\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219,\"function\":\"handle\",\"class\":\"Illuminate\\\\Foundation\\\\Http\\\\Middleware\\\\InvokeDeferredCallbacks\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Http\\\\Middleware\\\\ValidatePathEncoding.php\",\"line\":26,\"function\":\"{closure:{closure:Illuminate\\\\Pipeline\\\\Pipeline::carry():194}:195}\",\"class\":\"Illuminate\\\\Pipeline\\\\Pipeline\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219,\"function\":\"handle\",\"class\":\"Illuminate\\\\Http\\\\Middleware\\\\ValidatePathEncoding\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":137,\"function\":\"{closure:{closure:Illuminate\\\\Pipeline\\\\Pipeline::carry():194}:195}\",\"class\":\"Illuminate\\\\Pipeline\\\\Pipeline\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Kernel.php\",\"line\":175,\"function\":\"then\",\"class\":\"Illuminate\\\\Pipeline\\\\Pipeline\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Kernel.php\",\"line\":144,\"function\":\"sendRequestThroughRouter\",\"class\":\"Illuminate\\\\Foundation\\\\Http\\\\Kernel\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Application.php\",\"line\":1220,\"function\":\"handle\",\"class\":\"Illuminate\\\\Foundation\\\\Http\\\\Kernel\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\public\\\\index.php\",\"line\":20,\"function\":\"handleRequest\",\"class\":\"Illuminate\\\\Foundation\\\\Application\",\"type\":\"->\"},{\"file\":\"C:\\\\Program Files\\\\Herd\\\\resources\\\\app.asar.unpacked\\\\resources\\\\valet\\\\server.php\",\"line\":139,\"function\":\"require\"}]},\"duration\":149,\"memory\":6,\"hostname\":\"DESKTOP-GDIKUKP\",\"user\":{\"id\":\"a496c4b6-719e-4aee-bb31-c7cb1063522e\",\"name\":null,\"email\":\"super.admin@coupony.shop\"}}', '2026-02-08 01:16:04'),
(12, 'a106cf87-feb3-4163-8f28-069715d18325', 'a106cf88-0049-4eb6-889d-07b63721b84a', '5c868616b295a2d7334b95426ec5f673', 1, 'exception', '{\"class\":\"BadMethodCallException\",\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Validation\\\\Validator.php\",\"line\":1733,\"message\":\"Method Illuminate\\\\Validation\\\\Validator::validateRequeired does not exist.\",\"context\":{\"userId\":\"a496c4b6-719e-4aee-bb31-c7cb1063522e\"},\"trace\":[{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Validation\\\\Validator.php\",\"line\":686},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Validation\\\\Validator.php\",\"line\":481},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Validation\\\\Validator.php\",\"line\":516},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Validation\\\\ValidatesWhenResolvedTrait.php\",\"line\":31},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Providers\\\\FormRequestServiceProvider.php\",\"line\":30},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Container\\\\Container.php\",\"line\":1622},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Container\\\\Container.php\",\"line\":1560},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Container\\\\Container.php\",\"line\":1546},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Container\\\\Container.php\",\"line\":951},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Application.php\",\"line\":1078},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Container\\\\Container.php\",\"line\":864},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Application.php\",\"line\":1058},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\ResolvesRouteDependencies.php\",\"line\":92},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\ResolvesRouteDependencies.php\",\"line\":51},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\ResolvesRouteDependencies.php\",\"line\":30},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\ControllerDispatcher.php\",\"line\":59},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\ControllerDispatcher.php\",\"line\":40},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\Route.php\",\"line\":265},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\Route.php\",\"line\":211},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\Router.php\",\"line\":822},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":180},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\spatie\\\\laravel-permission\\\\src\\\\Middleware\\\\RoleMiddleware.php\",\"line\":37},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\Middleware\\\\SubstituteBindings.php\",\"line\":50},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Auth\\\\Middleware\\\\Authenticate.php\",\"line\":63},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":137},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\Router.php\",\"line\":821},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\Router.php\",\"line\":800},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\Router.php\",\"line\":764},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\Router.php\",\"line\":753},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Kernel.php\",\"line\":200},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":180},{\"file\":\"E:\\\\coupony-backend\\\\app\\\\Http\\\\Middleware\\\\UpdateUserSession.php\",\"line\":26},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219},{\"file\":\"E:\\\\coupony-backend\\\\app\\\\Http\\\\Middleware\\\\PerformanceMonitoring.php\",\"line\":21},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Middleware\\\\TransformsRequest.php\",\"line\":21},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Middleware\\\\ConvertEmptyStringsToNull.php\",\"line\":31},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Middleware\\\\TransformsRequest.php\",\"line\":21},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Middleware\\\\TrimStrings.php\",\"line\":51},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Http\\\\Middleware\\\\ValidatePostSize.php\",\"line\":27},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Middleware\\\\PreventRequestsDuringMaintenance.php\",\"line\":109},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Http\\\\Middleware\\\\HandleCors.php\",\"line\":74},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Http\\\\Middleware\\\\TrustProxies.php\",\"line\":58},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Middleware\\\\InvokeDeferredCallbacks.php\",\"line\":22},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Http\\\\Middleware\\\\ValidatePathEncoding.php\",\"line\":26},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":137},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Kernel.php\",\"line\":175},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Kernel.php\",\"line\":144},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Application.php\",\"line\":1220},{\"file\":\"E:\\\\coupony-backend\\\\public\\\\index.php\",\"line\":20},{\"file\":\"C:\\\\Program Files\\\\Herd\\\\resources\\\\app.asar.unpacked\\\\resources\\\\valet\\\\server.php\",\"line\":139}],\"line_preview\":{\"1724\":\"     *\\/\",\"1725\":\"    public function __call($method, $parameters)\",\"1726\":\"    {\",\"1727\":\"        $rule = Str::snake(substr($method, 8));\",\"1728\":\"\",\"1729\":\"        if (isset($this->extensions[$rule])) {\",\"1730\":\"            return $this->callExtension($rule, $parameters);\",\"1731\":\"        }\",\"1732\":\"\",\"1733\":\"        throw new BadMethodCallException(sprintf(\",\"1734\":\"            \'Method %s::%s does not exist.\', static::class, $method\",\"1735\":\"        ));\",\"1736\":\"    }\",\"1737\":\"}\",\"1738\":\"\"},\"hostname\":\"DESKTOP-GDIKUKP\",\"user\":{\"id\":\"a496c4b6-719e-4aee-bb31-c7cb1063522e\",\"name\":null,\"email\":\"super.admin@coupony.shop\"},\"occurrences\":2}', '2026-02-08 01:17:16');
INSERT INTO `telescope_entries` (`sequence`, `uuid`, `batch_id`, `family_hash`, `should_display_on_index`, `type`, `content`, `created_at`) VALUES
(13, 'a106cf87-ffdd-452b-ad30-977513460f2b', 'a106cf88-0049-4eb6-889d-07b63721b84a', NULL, 1, 'request', '{\"ip_address\":\"127.0.0.1\",\"uri\":\"\\/api\\/v1\\/admin\\/store-category\\/1\",\"method\":\"PUT\",\"controller_action\":\"App\\\\Application\\\\Http\\\\Controllers\\\\API\\\\V1\\\\StoreCategoryController@update\",\"middleware\":[\"api\",\"Spatie\\\\Permission\\\\Middleware\\\\RoleMiddleware:admin\",\"auth:sanctum\"],\"headers\":{\"content-length\":\"28\",\"connection\":\"keep-alive\",\"accept-encoding\":\"gzip, deflate, br\",\"host\":\"coupony-backend.test\",\"postman-token\":\"fd4034b4-d31b-4ca1-b47a-592b37a58804\",\"cache-control\":\"no-cache\",\"user-agent\":\"PostmanRuntime\\/7.51.1\",\"authorization\":\"********\",\"content-type\":\"application\\/json\",\"accept\":\"application\\/json\"},\"payload\":{\"is_active\":false},\"session\":[],\"response_headers\":{\"cache-control\":\"no-cache, private\",\"date\":\"Sat, 07 Feb 2026 23:17:16 GMT\",\"content-type\":\"application\\/json\",\"access-control-allow-origin\":\"*\"},\"response_status\":500,\"response\":{\"message\":\"Method Illuminate\\\\Validation\\\\Validator::validateRequeired does not exist.\",\"exception\":\"BadMethodCallException\",\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Validation\\\\Validator.php\",\"line\":1733,\"trace\":[{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Validation\\\\Validator.php\",\"line\":686,\"function\":\"__call\",\"class\":\"Illuminate\\\\Validation\\\\Validator\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Validation\\\\Validator.php\",\"line\":481,\"function\":\"validateAttribute\",\"class\":\"Illuminate\\\\Validation\\\\Validator\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Validation\\\\Validator.php\",\"line\":516,\"function\":\"passes\",\"class\":\"Illuminate\\\\Validation\\\\Validator\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Validation\\\\ValidatesWhenResolvedTrait.php\",\"line\":31,\"function\":\"fails\",\"class\":\"Illuminate\\\\Validation\\\\Validator\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Providers\\\\FormRequestServiceProvider.php\",\"line\":30,\"function\":\"validateResolved\",\"class\":\"Illuminate\\\\Foundation\\\\Http\\\\FormRequest\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Container\\\\Container.php\",\"line\":1622,\"function\":\"{closure:Illuminate\\\\Foundation\\\\Providers\\\\FormRequestServiceProvider::boot():29}\",\"class\":\"Illuminate\\\\Foundation\\\\Providers\\\\FormRequestServiceProvider\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Container\\\\Container.php\",\"line\":1560,\"function\":\"fireCallbackArray\",\"class\":\"Illuminate\\\\Container\\\\Container\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Container\\\\Container.php\",\"line\":1546,\"function\":\"fireAfterResolvingCallbacks\",\"class\":\"Illuminate\\\\Container\\\\Container\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Container\\\\Container.php\",\"line\":951,\"function\":\"fireResolvingCallbacks\",\"class\":\"Illuminate\\\\Container\\\\Container\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Application.php\",\"line\":1078,\"function\":\"resolve\",\"class\":\"Illuminate\\\\Container\\\\Container\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Container\\\\Container.php\",\"line\":864,\"function\":\"resolve\",\"class\":\"Illuminate\\\\Foundation\\\\Application\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Application.php\",\"line\":1058,\"function\":\"make\",\"class\":\"Illuminate\\\\Container\\\\Container\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\ResolvesRouteDependencies.php\",\"line\":92,\"function\":\"make\",\"class\":\"Illuminate\\\\Foundation\\\\Application\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\ResolvesRouteDependencies.php\",\"line\":51,\"function\":\"transformDependency\",\"class\":\"Illuminate\\\\Routing\\\\ControllerDispatcher\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\ResolvesRouteDependencies.php\",\"line\":30,\"function\":\"resolveMethodDependencies\",\"class\":\"Illuminate\\\\Routing\\\\ControllerDispatcher\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\ControllerDispatcher.php\",\"line\":59,\"function\":\"resolveClassMethodDependencies\",\"class\":\"Illuminate\\\\Routing\\\\ControllerDispatcher\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\ControllerDispatcher.php\",\"line\":40,\"function\":\"resolveParameters\",\"class\":\"Illuminate\\\\Routing\\\\ControllerDispatcher\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\Route.php\",\"line\":265,\"function\":\"dispatch\",\"class\":\"Illuminate\\\\Routing\\\\ControllerDispatcher\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\Route.php\",\"line\":211,\"function\":\"runController\",\"class\":\"Illuminate\\\\Routing\\\\Route\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\Router.php\",\"line\":822,\"function\":\"run\",\"class\":\"Illuminate\\\\Routing\\\\Route\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":180,\"function\":\"{closure:Illuminate\\\\Routing\\\\Router::runRouteWithinStack():821}\",\"class\":\"Illuminate\\\\Routing\\\\Router\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\spatie\\\\laravel-permission\\\\src\\\\Middleware\\\\RoleMiddleware.php\",\"line\":37,\"function\":\"{closure:Illuminate\\\\Pipeline\\\\Pipeline::prepareDestination():178}\",\"class\":\"Illuminate\\\\Pipeline\\\\Pipeline\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219,\"function\":\"handle\",\"class\":\"Spatie\\\\Permission\\\\Middleware\\\\RoleMiddleware\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\Middleware\\\\SubstituteBindings.php\",\"line\":50,\"function\":\"{closure:{closure:Illuminate\\\\Pipeline\\\\Pipeline::carry():194}:195}\",\"class\":\"Illuminate\\\\Pipeline\\\\Pipeline\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219,\"function\":\"handle\",\"class\":\"Illuminate\\\\Routing\\\\Middleware\\\\SubstituteBindings\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Auth\\\\Middleware\\\\Authenticate.php\",\"line\":63,\"function\":\"{closure:{closure:Illuminate\\\\Pipeline\\\\Pipeline::carry():194}:195}\",\"class\":\"Illuminate\\\\Pipeline\\\\Pipeline\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219,\"function\":\"handle\",\"class\":\"Illuminate\\\\Auth\\\\Middleware\\\\Authenticate\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":137,\"function\":\"{closure:{closure:Illuminate\\\\Pipeline\\\\Pipeline::carry():194}:195}\",\"class\":\"Illuminate\\\\Pipeline\\\\Pipeline\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\Router.php\",\"line\":821,\"function\":\"then\",\"class\":\"Illuminate\\\\Pipeline\\\\Pipeline\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\Router.php\",\"line\":800,\"function\":\"runRouteWithinStack\",\"class\":\"Illuminate\\\\Routing\\\\Router\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\Router.php\",\"line\":764,\"function\":\"runRoute\",\"class\":\"Illuminate\\\\Routing\\\\Router\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\Router.php\",\"line\":753,\"function\":\"dispatchToRoute\",\"class\":\"Illuminate\\\\Routing\\\\Router\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Kernel.php\",\"line\":200,\"function\":\"dispatch\",\"class\":\"Illuminate\\\\Routing\\\\Router\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":180,\"function\":\"{closure:Illuminate\\\\Foundation\\\\Http\\\\Kernel::dispatchToRouter():197}\",\"class\":\"Illuminate\\\\Foundation\\\\Http\\\\Kernel\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\app\\\\Http\\\\Middleware\\\\UpdateUserSession.php\",\"line\":26,\"function\":\"{closure:Illuminate\\\\Pipeline\\\\Pipeline::prepareDestination():178}\",\"class\":\"Illuminate\\\\Pipeline\\\\Pipeline\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219,\"function\":\"handle\",\"class\":\"App\\\\Http\\\\Middleware\\\\UpdateUserSession\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\app\\\\Http\\\\Middleware\\\\PerformanceMonitoring.php\",\"line\":21,\"function\":\"{closure:{closure:Illuminate\\\\Pipeline\\\\Pipeline::carry():194}:195}\",\"class\":\"Illuminate\\\\Pipeline\\\\Pipeline\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219,\"function\":\"handle\",\"class\":\"App\\\\Http\\\\Middleware\\\\PerformanceMonitoring\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Middleware\\\\TransformsRequest.php\",\"line\":21,\"function\":\"{closure:{closure:Illuminate\\\\Pipeline\\\\Pipeline::carry():194}:195}\",\"class\":\"Illuminate\\\\Pipeline\\\\Pipeline\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Middleware\\\\ConvertEmptyStringsToNull.php\",\"line\":31,\"function\":\"handle\",\"class\":\"Illuminate\\\\Foundation\\\\Http\\\\Middleware\\\\TransformsRequest\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219,\"function\":\"handle\",\"class\":\"Illuminate\\\\Foundation\\\\Http\\\\Middleware\\\\ConvertEmptyStringsToNull\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Middleware\\\\TransformsRequest.php\",\"line\":21,\"function\":\"{closure:{closure:Illuminate\\\\Pipeline\\\\Pipeline::carry():194}:195}\",\"class\":\"Illuminate\\\\Pipeline\\\\Pipeline\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Middleware\\\\TrimStrings.php\",\"line\":51,\"function\":\"handle\",\"class\":\"Illuminate\\\\Foundation\\\\Http\\\\Middleware\\\\TransformsRequest\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219,\"function\":\"handle\",\"class\":\"Illuminate\\\\Foundation\\\\Http\\\\Middleware\\\\TrimStrings\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Http\\\\Middleware\\\\ValidatePostSize.php\",\"line\":27,\"function\":\"{closure:{closure:Illuminate\\\\Pipeline\\\\Pipeline::carry():194}:195}\",\"class\":\"Illuminate\\\\Pipeline\\\\Pipeline\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219,\"function\":\"handle\",\"class\":\"Illuminate\\\\Http\\\\Middleware\\\\ValidatePostSize\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Middleware\\\\PreventRequestsDuringMaintenance.php\",\"line\":109,\"function\":\"{closure:{closure:Illuminate\\\\Pipeline\\\\Pipeline::carry():194}:195}\",\"class\":\"Illuminate\\\\Pipeline\\\\Pipeline\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219,\"function\":\"handle\",\"class\":\"Illuminate\\\\Foundation\\\\Http\\\\Middleware\\\\PreventRequestsDuringMaintenance\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Http\\\\Middleware\\\\HandleCors.php\",\"line\":74,\"function\":\"{closure:{closure:Illuminate\\\\Pipeline\\\\Pipeline::carry():194}:195}\",\"class\":\"Illuminate\\\\Pipeline\\\\Pipeline\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219,\"function\":\"handle\",\"class\":\"Illuminate\\\\Http\\\\Middleware\\\\HandleCors\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Http\\\\Middleware\\\\TrustProxies.php\",\"line\":58,\"function\":\"{closure:{closure:Illuminate\\\\Pipeline\\\\Pipeline::carry():194}:195}\",\"class\":\"Illuminate\\\\Pipeline\\\\Pipeline\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219,\"function\":\"handle\",\"class\":\"Illuminate\\\\Http\\\\Middleware\\\\TrustProxies\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Middleware\\\\InvokeDeferredCallbacks.php\",\"line\":22,\"function\":\"{closure:{closure:Illuminate\\\\Pipeline\\\\Pipeline::carry():194}:195}\",\"class\":\"Illuminate\\\\Pipeline\\\\Pipeline\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219,\"function\":\"handle\",\"class\":\"Illuminate\\\\Foundation\\\\Http\\\\Middleware\\\\InvokeDeferredCallbacks\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Http\\\\Middleware\\\\ValidatePathEncoding.php\",\"line\":26,\"function\":\"{closure:{closure:Illuminate\\\\Pipeline\\\\Pipeline::carry():194}:195}\",\"class\":\"Illuminate\\\\Pipeline\\\\Pipeline\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219,\"function\":\"handle\",\"class\":\"Illuminate\\\\Http\\\\Middleware\\\\ValidatePathEncoding\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":137,\"function\":\"{closure:{closure:Illuminate\\\\Pipeline\\\\Pipeline::carry():194}:195}\",\"class\":\"Illuminate\\\\Pipeline\\\\Pipeline\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Kernel.php\",\"line\":175,\"function\":\"then\",\"class\":\"Illuminate\\\\Pipeline\\\\Pipeline\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Kernel.php\",\"line\":144,\"function\":\"sendRequestThroughRouter\",\"class\":\"Illuminate\\\\Foundation\\\\Http\\\\Kernel\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Application.php\",\"line\":1220,\"function\":\"handle\",\"class\":\"Illuminate\\\\Foundation\\\\Http\\\\Kernel\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\public\\\\index.php\",\"line\":20,\"function\":\"handleRequest\",\"class\":\"Illuminate\\\\Foundation\\\\Application\",\"type\":\"->\"},{\"file\":\"C:\\\\Program Files\\\\Herd\\\\resources\\\\app.asar.unpacked\\\\resources\\\\valet\\\\server.php\",\"line\":139,\"function\":\"require\"}]},\"duration\":83,\"memory\":6,\"hostname\":\"DESKTOP-GDIKUKP\",\"user\":{\"id\":\"a496c4b6-719e-4aee-bb31-c7cb1063522e\",\"name\":null,\"email\":\"super.admin@coupony.shop\"}}', '2026-02-08 01:17:16'),
(14, 'a106d42d-2c07-435e-8a63-9f4510e0cb4d', 'a106d439-6bbe-433c-8e4d-9d65657b7ad3', '9ecbcb1b62f7bcc07a3fc26dc340657c', 1, 'exception', '{\"class\":\"Symfony\\\\Component\\\\Routing\\\\Exception\\\\RouteNotFoundException\",\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\UrlGenerator.php\",\"line\":526,\"message\":\"Route [login] not defined.\",\"context\":null,\"trace\":[{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\helpers.php\",\"line\":871},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Configuration\\\\ApplicationBuilder.php\",\"line\":278},[],{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Auth\\\\Middleware\\\\Authenticate.php\",\"line\":117},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Auth\\\\Middleware\\\\Authenticate.php\",\"line\":104},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Auth\\\\Middleware\\\\Authenticate.php\",\"line\":87},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Auth\\\\Middleware\\\\Authenticate.php\",\"line\":61},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":137},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\Router.php\",\"line\":821},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\Router.php\",\"line\":800},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\Router.php\",\"line\":764},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\Router.php\",\"line\":753},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Kernel.php\",\"line\":200},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":180},{\"file\":\"E:\\\\coupony-backend\\\\app\\\\Http\\\\Middleware\\\\UpdateUserSession.php\",\"line\":26},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219},{\"file\":\"E:\\\\coupony-backend\\\\app\\\\Http\\\\Middleware\\\\PerformanceMonitoring.php\",\"line\":21},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Middleware\\\\TransformsRequest.php\",\"line\":21},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Middleware\\\\ConvertEmptyStringsToNull.php\",\"line\":31},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Middleware\\\\TransformsRequest.php\",\"line\":21},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Middleware\\\\TrimStrings.php\",\"line\":51},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Http\\\\Middleware\\\\ValidatePostSize.php\",\"line\":27},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Middleware\\\\PreventRequestsDuringMaintenance.php\",\"line\":109},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Http\\\\Middleware\\\\HandleCors.php\",\"line\":74},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Http\\\\Middleware\\\\TrustProxies.php\",\"line\":58},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Middleware\\\\InvokeDeferredCallbacks.php\",\"line\":22},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Http\\\\Middleware\\\\ValidatePathEncoding.php\",\"line\":26},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":137},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Kernel.php\",\"line\":175},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Kernel.php\",\"line\":144},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Application.php\",\"line\":1220},{\"file\":\"E:\\\\coupony-backend\\\\public\\\\index.php\",\"line\":20},{\"file\":\"C:\\\\Program Files\\\\Herd\\\\resources\\\\app.asar.unpacked\\\\resources\\\\valet\\\\server.php\",\"line\":139}],\"line_preview\":{\"517\":\"        if (! is_null($route = $this->routes->getByName($name))) {\",\"518\":\"            return $this->toRoute($route, $parameters, $absolute);\",\"519\":\"        }\",\"520\":\"\",\"521\":\"        if (! is_null($this->missingNamedRouteResolver) &&\",\"522\":\"            ! is_null($url = call_user_func($this->missingNamedRouteResolver, $name, $parameters, $absolute))) {\",\"523\":\"            return $url;\",\"524\":\"        }\",\"525\":\"\",\"526\":\"        throw new RouteNotFoundException(\\\"Route [{$name}] not defined.\\\");\",\"527\":\"    }\",\"528\":\"\",\"529\":\"    \\/**\",\"530\":\"     * Get the URL for a given route instance.\",\"531\":\"     *\",\"532\":\"     * @param  \\\\Illuminate\\\\Routing\\\\Route  $route\",\"533\":\"     * @param  mixed  $parameters\",\"534\":\"     * @param  bool  $absolute\",\"535\":\"     * @return string\",\"536\":\"     *\"},\"hostname\":\"DESKTOP-GDIKUKP\",\"occurrences\":3}', '2026-02-08 01:30:16'),
(15, 'a106d439-6a7e-4ad2-a724-1f9c852ffbac', 'a106d439-6bbe-433c-8e4d-9d65657b7ad3', NULL, 1, 'request', '{\"ip_address\":\"127.0.0.1\",\"uri\":\"\\/api\\/v1\\/store-categories\",\"method\":\"GET\",\"controller_action\":\"App\\\\Application\\\\Http\\\\Controllers\\\\API\\\\V1\\\\UserStoreCategoryController@index\",\"middleware\":[\"api\",\"auth:sanctum\"],\"headers\":{\"connection\":\"keep-alive\",\"accept-encoding\":\"gzip, deflate, br\",\"host\":\"coupony-backend.test\",\"postman-token\":\"13879489-0331-42cd-a09c-427fe9aafb0e\",\"cache-control\":\"no-cache\",\"accept\":\"*\\/*\",\"user-agent\":\"PostmanRuntime\\/7.51.1\",\"authorization\":\"********\"},\"payload\":[],\"session\":[],\"response_headers\":{\"cache-control\":\"no-cache, private\",\"date\":\"Sat, 07 Feb 2026 23:30:24 GMT\",\"content-type\":\"text\\/html; charset=utf-8\",\"access-control-allow-origin\":\"*\"},\"response_status\":500,\"response\":\"HTML Response\",\"duration\":8088,\"memory\":12,\"hostname\":\"DESKTOP-GDIKUKP\"}', '2026-02-08 01:30:24'),
(16, 'a106e00e-c9e7-4622-91fb-cd0f540215f3', 'a106e00e-ca4a-4a85-883c-9d465205bcfc', NULL, 1, 'request', '{\"ip_address\":\"127.0.0.1\",\"uri\":\"\\/api\\/v1\\/store\\/create\",\"method\":\"POST\",\"controller_action\":\"App\\\\Application\\\\Http\\\\Controllers\\\\API\\\\V1\\\\StoreController@create\",\"middleware\":[\"api\",\"auth:sanctum\"],\"headers\":{\"content-length\":\"642594\",\"content-type\":\"multipart\\/form-data; boundary=--------------------------738902739619130094247621\",\"connection\":\"keep-alive\",\"accept-encoding\":\"gzip, deflate, br\",\"host\":\"coupony-backend.test\",\"postman-token\":\"d4d61c68-9e1f-483d-845b-e3cfd96604af\",\"cache-control\":\"no-cache\",\"user-agent\":\"PostmanRuntime\\/7.51.1\",\"authorization\":\"********\",\"accept\":\"application\\/json\"},\"payload\":{\"name\":\"HM\",\"phone\":\"01025250321\",\"address_line1\":\"gamal street\",\"city\":\"fayoum\",\"categories\":[\"1\"],\"verification_docs\":{\"commercial_register\":{\"name\":\"800.jpg\",\"size\":\"135.886KB\"},\"tax_card\":{\"name\":\"800.jpg\",\"size\":\"135.886KB\"},\"id_card_front\":{\"name\":\"800.jpg\",\"size\":\"135.886KB\"},\"id_card_back\":{\"name\":\"FrontendCV.pdf\",\"size\":\"233.595KB\"}}},\"session\":[],\"response_headers\":{\"cache-control\":\"no-cache, private\",\"date\":\"Sun, 08 Feb 2026 00:03:29 GMT\",\"content-type\":\"application\\/json\",\"access-control-allow-origin\":\"*\"},\"response_status\":500,\"response\":{\"message\":\"Failed to create store\",\"error\":\"SQLSTATE[23000]: Integrity constraint violation: 1452 Cannot add or update a child row: a foreign key constraint fails (`coupony`.`store_store_category`, CONSTRAINT `store_store_category_store_category_id_foreign` FOREIGN KEY (`store_category_id`) REFERENCES `store_categories` (`id`) ON DELETE CASCADE) (Connection: mysql, Host: 127.0.0.1, Port: 3306, Database: coupony, SQL: insert into `store_store_category` (`store_category_id`, `store_id`) values (1, fd069cc5-67c7-459e-850b-7656c9aeb888))\"},\"duration\":894,\"memory\":14,\"hostname\":\"DESKTOP-GDIKUKP\",\"user\":{\"id\":\"193e51d4-5225-4cd0-8c54-5ea3b90a5539\",\"name\":null,\"email\":\"lofylofy56@gmail.com\"}}', '2026-02-08 02:03:29'),
(17, 'a106e0bd-1174-49e4-b858-a4daae1cf920', 'a106e0bd-11d9-4af6-b583-251d37b2f8e9', NULL, 1, 'request', '{\"ip_address\":\"127.0.0.1\",\"uri\":\"\\/api\\/v1\\/store\\/create\",\"method\":\"POST\",\"controller_action\":\"App\\\\Application\\\\Http\\\\Controllers\\\\API\\\\V1\\\\StoreController@create\",\"middleware\":[\"api\",\"auth:sanctum\"],\"headers\":{\"content-length\":\"642594\",\"content-type\":\"multipart\\/form-data; boundary=--------------------------013290243593135128290436\",\"connection\":\"keep-alive\",\"accept-encoding\":\"gzip, deflate, br\",\"host\":\"coupony-backend.test\",\"postman-token\":\"21a3e6f1-37b1-44b1-8232-f2f6e1bd8b63\",\"cache-control\":\"no-cache\",\"user-agent\":\"PostmanRuntime\\/7.51.1\",\"authorization\":\"********\",\"accept\":\"application\\/json\"},\"payload\":{\"name\":\"HM\",\"phone\":\"01025250321\",\"address_line1\":\"gamal street\",\"city\":\"fayoum\",\"categories\":[\"2\"],\"verification_docs\":{\"commercial_register\":{\"name\":\"800.jpg\",\"size\":\"135.886KB\"},\"tax_card\":{\"name\":\"800.jpg\",\"size\":\"135.886KB\"},\"id_card_front\":{\"name\":\"800.jpg\",\"size\":\"135.886KB\"},\"id_card_back\":{\"name\":\"FrontendCV.pdf\",\"size\":\"233.595KB\"}}},\"session\":[],\"response_headers\":{\"cache-control\":\"no-cache, private\",\"date\":\"Sun, 08 Feb 2026 00:05:23 GMT\",\"content-type\":\"application\\/json\",\"access-control-allow-origin\":\"*\"},\"response_status\":500,\"response\":{\"message\":\"Failed to create store\",\"error\":\"SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry \'App\\\\Domain\\\\Store\\\\Models\\\\Store-ffa228b9-68b7-43ff-9cdf-5f7ad01...\' for key \'unique_owner_address\' (Connection: mysql, Host: 127.0.0.1, Port: 3306, Database: coupony, SQL: insert into `addressables` (`address_id`, `created_at`, `label`, `owner_id`, `owner_type`, `updated_at`) values (1, 2026-02-08 02:05:23, branch, ffa228b9-68b7-43ff-9cdf-5f7ad01e5456, App\\\\Domain\\\\Store\\\\Models\\\\Store, 2026-02-08 02:05:23))\"},\"duration\":135,\"memory\":12,\"hostname\":\"DESKTOP-GDIKUKP\",\"user\":{\"id\":\"193e51d4-5225-4cd0-8c54-5ea3b90a5539\",\"name\":null,\"email\":\"lofylofy56@gmail.com\"}}', '2026-02-08 02:05:23'),
(18, 'a106e1ca-d76a-4d09-8a1c-91d418f6b824', 'a106e1ca-d801-4b2c-a47b-a6f0afe1a3d6', NULL, 1, 'request', '{\"ip_address\":\"127.0.0.1\",\"uri\":\"\\/api\\/v1\\/store\\/create\",\"method\":\"POST\",\"controller_action\":\"App\\\\Application\\\\Http\\\\Controllers\\\\API\\\\V1\\\\StoreController@create\",\"middleware\":[\"api\",\"auth:sanctum\"],\"headers\":{\"content-length\":\"642594\",\"content-type\":\"multipart\\/form-data; boundary=--------------------------182727790513426243615669\",\"connection\":\"keep-alive\",\"accept-encoding\":\"gzip, deflate, br\",\"host\":\"coupony-backend.test\",\"postman-token\":\"31344884-16a6-4c1c-9206-62135f445df8\",\"cache-control\":\"no-cache\",\"user-agent\":\"PostmanRuntime\\/7.51.1\",\"authorization\":\"********\",\"accept\":\"application\\/json\"},\"payload\":{\"name\":\"HM\",\"phone\":\"01025250321\",\"address_line1\":\"gamal street\",\"city\":\"fayoum\",\"categories\":[\"2\"],\"verification_docs\":{\"commercial_register\":{\"name\":\"800.jpg\",\"size\":\"135.886KB\"},\"tax_card\":{\"name\":\"800.jpg\",\"size\":\"135.886KB\"},\"id_card_front\":{\"name\":\"800.jpg\",\"size\":\"135.886KB\"},\"id_card_back\":{\"name\":\"FrontendCV.pdf\",\"size\":\"233.595KB\"}}},\"session\":[],\"response_headers\":{\"cache-control\":\"no-cache, private\",\"date\":\"Sun, 08 Feb 2026 00:08:20 GMT\",\"content-type\":\"application\\/json\",\"access-control-allow-origin\":\"*\"},\"response_status\":500,\"response\":{\"message\":\"Failed to create store\",\"error\":\"SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry \'App\\\\Domain\\\\Store\\\\Models\\\\Store-edd35d2a-5176-4686-aa13-b38a209...\' for key \'unique_owner_address\' (Connection: mysql, Host: 127.0.0.1, Port: 3306, Database: coupony, SQL: insert into `addressables` (`address_id`, `created_at`, `label`, `owner_id`, `owner_type`, `updated_at`) values (2, 2026-02-08 02:08:20, branch, edd35d2a-5176-4686-aa13-b38a2098dc89, App\\\\Domain\\\\Store\\\\Models\\\\Store, 2026-02-08 02:08:20))\"},\"duration\":105,\"memory\":14,\"hostname\":\"DESKTOP-GDIKUKP\",\"user\":{\"id\":\"193e51d4-5225-4cd0-8c54-5ea3b90a5539\",\"name\":null,\"email\":\"lofylofy56@gmail.com\"}}', '2026-02-08 02:08:20'),
(19, 'a106e2f9-a88b-4a5c-9b0c-dc8783581153', 'a106e2f9-a8eb-457a-8933-63bbf7acc36b', NULL, 1, 'request', '{\"ip_address\":\"127.0.0.1\",\"uri\":\"\\/api\\/v1\\/store\\/create\",\"method\":\"POST\",\"controller_action\":\"App\\\\Application\\\\Http\\\\Controllers\\\\API\\\\V1\\\\StoreController@create\",\"middleware\":[\"api\",\"auth:sanctum\"],\"headers\":{\"content-length\":\"642594\",\"content-type\":\"multipart\\/form-data; boundary=--------------------------357343426087164907265867\",\"connection\":\"keep-alive\",\"accept-encoding\":\"gzip, deflate, br\",\"host\":\"coupony-backend.test\",\"postman-token\":\"ad1b48dc-2bdf-4102-bdf1-2c8c6b731432\",\"cache-control\":\"no-cache\",\"user-agent\":\"PostmanRuntime\\/7.51.1\",\"authorization\":\"********\",\"accept\":\"application\\/json\"},\"payload\":{\"name\":\"HM\",\"phone\":\"01025250321\",\"address_line1\":\"gamal street\",\"city\":\"fayoum\",\"categories\":[\"2\"],\"verification_docs\":{\"commercial_register\":{\"name\":\"800.jpg\",\"size\":\"135.886KB\"},\"tax_card\":{\"name\":\"800.jpg\",\"size\":\"135.886KB\"},\"id_card_front\":{\"name\":\"800.jpg\",\"size\":\"135.886KB\"},\"id_card_back\":{\"name\":\"FrontendCV.pdf\",\"size\":\"233.595KB\"}}},\"session\":[],\"response_headers\":{\"cache-control\":\"no-cache, private\",\"date\":\"Sun, 08 Feb 2026 00:11:38 GMT\",\"content-type\":\"application\\/json\",\"access-control-allow-origin\":\"*\"},\"response_status\":500,\"response\":{\"message\":\"Failed to create store\",\"error\":\"Call to undefined method App\\\\Domain\\\\User\\\\Models\\\\User::seller()\"},\"duration\":142,\"memory\":14,\"hostname\":\"DESKTOP-GDIKUKP\",\"user\":{\"id\":\"193e51d4-5225-4cd0-8c54-5ea3b90a5539\",\"name\":null,\"email\":\"lofylofy56@gmail.com\"}}', '2026-02-08 02:11:38'),
(20, 'a106e33e-5ff6-4a96-ac2d-216f559c70dc', 'a106e33e-605f-4f45-b5ce-19fedac94c43', NULL, 1, 'request', '{\"ip_address\":\"127.0.0.1\",\"uri\":\"\\/api\\/v1\\/store\\/create\",\"method\":\"POST\",\"controller_action\":\"App\\\\Application\\\\Http\\\\Controllers\\\\API\\\\V1\\\\StoreController@create\",\"middleware\":[\"api\",\"auth:sanctum\"],\"headers\":{\"content-length\":\"642594\",\"content-type\":\"multipart\\/form-data; boundary=--------------------------626951736474992024690146\",\"connection\":\"keep-alive\",\"accept-encoding\":\"gzip, deflate, br\",\"host\":\"coupony-backend.test\",\"postman-token\":\"166ab2db-54af-4ed9-8303-7a7a92f988a4\",\"cache-control\":\"no-cache\",\"user-agent\":\"PostmanRuntime\\/7.51.1\",\"authorization\":\"********\",\"accept\":\"application\\/json\"},\"payload\":{\"name\":\"HM\",\"phone\":\"01025250321\",\"address_line1\":\"gamal street\",\"city\":\"fayoum\",\"categories\":[\"2\"],\"verification_docs\":{\"commercial_register\":{\"name\":\"800.jpg\",\"size\":\"135.886KB\"},\"tax_card\":{\"name\":\"800.jpg\",\"size\":\"135.886KB\"},\"id_card_front\":{\"name\":\"800.jpg\",\"size\":\"135.886KB\"},\"id_card_back\":{\"name\":\"FrontendCV.pdf\",\"size\":\"233.595KB\"}}},\"session\":[],\"response_headers\":{\"cache-control\":\"no-cache, private\",\"date\":\"Sun, 08 Feb 2026 00:12:23 GMT\",\"content-type\":\"application\\/json\",\"access-control-allow-origin\":\"*\"},\"response_status\":500,\"response\":{\"message\":\"Failed to create store\",\"error\":\"SQLSTATE[HY000]: General error: 1364 Field \'role_id\' doesn\'t have a default value (Connection: mysql, Host: 127.0.0.1, Port: 3306, Database: coupony, SQL: insert into `user_roles` (`user_id`, `store_id`, `updated_at`, `created_at`) values (193e51d4-5225-4cd0-8c54-5ea3b90a5539, e7b420dd-0af5-4b72-a04b-0ef614721f73, 2026-02-08 02:12:23, 2026-02-08 02:12:23))\"},\"duration\":129,\"memory\":14,\"hostname\":\"DESKTOP-GDIKUKP\",\"user\":{\"id\":\"193e51d4-5225-4cd0-8c54-5ea3b90a5539\",\"name\":null,\"email\":\"lofylofy56@gmail.com\"}}', '2026-02-08 02:12:23'),
(21, 'a106ec5a-5c33-4487-9ab8-6f19cc619761', 'a106ec5a-5d9b-4cc6-bd76-d7734751dc6b', 'df5fba598f83c148f1077c02afe5b3b2', 0, 'exception', '{\"class\":\"TypeError\",\"file\":\"E:\\\\coupony-backend\\\\app\\\\Domain\\\\Store\\\\Actions\\\\CreateStore.php\",\"line\":78,\"message\":\"App\\\\Domain\\\\Store\\\\Actions\\\\CreateStore::execute(): Return value must be of type App\\\\Domain\\\\Store\\\\Models\\\\Store, App\\\\Application\\\\Http\\\\Resources\\\\StoreResource returned\",\"context\":{\"userId\":\"193e51d4-5225-4cd0-8c54-5ea3b90a5539\"},\"trace\":[{\"file\":\"E:\\\\coupony-backend\\\\app\\\\Application\\\\Http\\\\Controllers\\\\API\\\\V1\\\\StoreController.php\",\"line\":23},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\ControllerDispatcher.php\",\"line\":46},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\Route.php\",\"line\":265},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\Route.php\",\"line\":211},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\Router.php\",\"line\":822},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":180},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\Middleware\\\\SubstituteBindings.php\",\"line\":50},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Auth\\\\Middleware\\\\Authenticate.php\",\"line\":63},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":137},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\Router.php\",\"line\":821},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\Router.php\",\"line\":800},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\Router.php\",\"line\":764},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\Router.php\",\"line\":753},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Kernel.php\",\"line\":200},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":180},{\"file\":\"E:\\\\coupony-backend\\\\app\\\\Http\\\\Middleware\\\\UpdateUserSession.php\",\"line\":26},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219},{\"file\":\"E:\\\\coupony-backend\\\\app\\\\Http\\\\Middleware\\\\PerformanceMonitoring.php\",\"line\":21},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Middleware\\\\TransformsRequest.php\",\"line\":21},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Middleware\\\\ConvertEmptyStringsToNull.php\",\"line\":31},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Middleware\\\\TransformsRequest.php\",\"line\":21},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Middleware\\\\TrimStrings.php\",\"line\":51},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Http\\\\Middleware\\\\ValidatePostSize.php\",\"line\":27},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Middleware\\\\PreventRequestsDuringMaintenance.php\",\"line\":109},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Http\\\\Middleware\\\\HandleCors.php\",\"line\":74},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Http\\\\Middleware\\\\TrustProxies.php\",\"line\":58},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Middleware\\\\InvokeDeferredCallbacks.php\",\"line\":22},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Http\\\\Middleware\\\\ValidatePathEncoding.php\",\"line\":26},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":137},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Kernel.php\",\"line\":175},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Kernel.php\",\"line\":144},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Application.php\",\"line\":1220},{\"file\":\"E:\\\\coupony-backend\\\\public\\\\index.php\",\"line\":20},{\"file\":\"C:\\\\Program Files\\\\Herd\\\\resources\\\\app.asar.unpacked\\\\resources\\\\valet\\\\server.php\",\"line\":139}],\"line_preview\":{\"69\":\"                \'role_id\' => $owner->roles()->where(\'name\', \'seller\')->first()->id,\\r\",\"70\":\"                \'role\' => \'seller\',\\r\",\"71\":\"                \'store_id\' => $store->id,\\r\",\"72\":\"            ]);\\r\",\"73\":\"\\r\",\"74\":\"            $this->createDefaultStoreHours($store);\\r\",\"75\":\"\\r\",\"76\":\"            event(new StoreCreated($store));\\r\",\"77\":\"            return StoreResource::make($store);\\r\",\"78\":\"        });\\r\",\"79\":\"    }\\r\",\"80\":\"    private function createDefaultStoreHours(Store $store): void\\r\",\"81\":\"    {\\r\",\"82\":\"        $defaultHours = [\\r\",\"83\":\"            [\'day_of_week\' => 1, \'open_time\' => \'09:00\', \'close_time\' => \'17:00\', \'is_closed\' => false],\\r\",\"84\":\"            [\'day_of_week\' => 2, \'open_time\' => \'09:00\', \'close_time\' => \'17:00\', \'is_closed\' => false],\\r\",\"85\":\"            [\'day_of_week\' => 3, \'open_time\' => \'09:00\', \'close_time\' => \'17:00\', \'is_closed\' => false],\\r\",\"86\":\"            [\'day_of_week\' => 4, \'open_time\' => \'09:00\', \'close_time\' => \'17:00\', \'is_closed\' => false],\\r\",\"87\":\"            [\'day_of_week\' => 5, \'open_time\' => \'09:00\', \'close_time\' => \'17:00\', \'is_closed\' => false],\\r\",\"88\":\"            [\'day_of_week\' => 6, \'open_time\' => \'09:00\', \'close_time\' => \'17:00\', \'is_closed\' => true],\\r\"},\"hostname\":\"DESKTOP-GDIKUKP\",\"user\":{\"id\":\"193e51d4-5225-4cd0-8c54-5ea3b90a5539\",\"name\":null,\"email\":\"lofylofy56@gmail.com\"},\"occurrences\":1}', '2026-02-08 02:37:52');
INSERT INTO `telescope_entries` (`sequence`, `uuid`, `batch_id`, `family_hash`, `should_display_on_index`, `type`, `content`, `created_at`) VALUES
(22, 'a106ec5a-5d34-4cf2-88df-94e5eaffb25f', 'a106ec5a-5d9b-4cc6-bd76-d7734751dc6b', NULL, 1, 'request', '{\"ip_address\":\"127.0.0.1\",\"uri\":\"\\/api\\/v1\\/store\\/create\",\"method\":\"POST\",\"controller_action\":\"App\\\\Application\\\\Http\\\\Controllers\\\\API\\\\V1\\\\StoreController@create\",\"middleware\":[\"api\",\"auth:sanctum\"],\"headers\":{\"content-length\":\"642594\",\"content-type\":\"multipart\\/form-data; boundary=--------------------------186193708716141168535811\",\"connection\":\"keep-alive\",\"accept-encoding\":\"gzip, deflate, br\",\"host\":\"coupony-backend.test\",\"postman-token\":\"18cf2f58-85bf-4aa4-8907-1b9c6f786029\",\"cache-control\":\"no-cache\",\"user-agent\":\"PostmanRuntime\\/7.51.1\",\"authorization\":\"********\",\"accept\":\"application\\/json\"},\"payload\":{\"name\":\"HM\",\"phone\":\"01025250321\",\"address_line1\":\"gamal street\",\"city\":\"fayoum\",\"categories\":[\"2\"],\"verification_docs\":{\"commercial_register\":{\"name\":\"800.jpg\",\"size\":\"135.886KB\"},\"tax_card\":{\"name\":\"800.jpg\",\"size\":\"135.886KB\"},\"id_card_front\":{\"name\":\"800.jpg\",\"size\":\"135.886KB\"},\"id_card_back\":{\"name\":\"FrontendCV.pdf\",\"size\":\"233.595KB\"}}},\"session\":[],\"response_headers\":{\"cache-control\":\"no-cache, private\",\"date\":\"Sun, 08 Feb 2026 00:37:52 GMT\",\"content-type\":\"application\\/json\",\"access-control-allow-origin\":\"*\"},\"response_status\":500,\"response\":{\"message\":\"App\\\\Domain\\\\Store\\\\Actions\\\\CreateStore::execute(): Return value must be of type App\\\\Domain\\\\Store\\\\Models\\\\Store, App\\\\Application\\\\Http\\\\Resources\\\\StoreResource returned\",\"exception\":\"TypeError\",\"file\":\"E:\\\\coupony-backend\\\\app\\\\Domain\\\\Store\\\\Actions\\\\CreateStore.php\",\"line\":78,\"trace\":[{\"file\":\"E:\\\\coupony-backend\\\\app\\\\Application\\\\Http\\\\Controllers\\\\API\\\\V1\\\\StoreController.php\",\"line\":23,\"function\":\"execute\",\"class\":\"App\\\\Domain\\\\Store\\\\Actions\\\\CreateStore\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\ControllerDispatcher.php\",\"line\":46,\"function\":\"create\",\"class\":\"App\\\\Application\\\\Http\\\\Controllers\\\\API\\\\V1\\\\StoreController\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\Route.php\",\"line\":265,\"function\":\"dispatch\",\"class\":\"Illuminate\\\\Routing\\\\ControllerDispatcher\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\Route.php\",\"line\":211,\"function\":\"runController\",\"class\":\"Illuminate\\\\Routing\\\\Route\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\Router.php\",\"line\":822,\"function\":\"run\",\"class\":\"Illuminate\\\\Routing\\\\Route\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":180,\"function\":\"{closure:Illuminate\\\\Routing\\\\Router::runRouteWithinStack():821}\",\"class\":\"Illuminate\\\\Routing\\\\Router\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\Middleware\\\\SubstituteBindings.php\",\"line\":50,\"function\":\"{closure:Illuminate\\\\Pipeline\\\\Pipeline::prepareDestination():178}\",\"class\":\"Illuminate\\\\Pipeline\\\\Pipeline\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219,\"function\":\"handle\",\"class\":\"Illuminate\\\\Routing\\\\Middleware\\\\SubstituteBindings\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Auth\\\\Middleware\\\\Authenticate.php\",\"line\":63,\"function\":\"{closure:{closure:Illuminate\\\\Pipeline\\\\Pipeline::carry():194}:195}\",\"class\":\"Illuminate\\\\Pipeline\\\\Pipeline\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219,\"function\":\"handle\",\"class\":\"Illuminate\\\\Auth\\\\Middleware\\\\Authenticate\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":137,\"function\":\"{closure:{closure:Illuminate\\\\Pipeline\\\\Pipeline::carry():194}:195}\",\"class\":\"Illuminate\\\\Pipeline\\\\Pipeline\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\Router.php\",\"line\":821,\"function\":\"then\",\"class\":\"Illuminate\\\\Pipeline\\\\Pipeline\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\Router.php\",\"line\":800,\"function\":\"runRouteWithinStack\",\"class\":\"Illuminate\\\\Routing\\\\Router\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\Router.php\",\"line\":764,\"function\":\"runRoute\",\"class\":\"Illuminate\\\\Routing\\\\Router\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\Router.php\",\"line\":753,\"function\":\"dispatchToRoute\",\"class\":\"Illuminate\\\\Routing\\\\Router\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Kernel.php\",\"line\":200,\"function\":\"dispatch\",\"class\":\"Illuminate\\\\Routing\\\\Router\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":180,\"function\":\"{closure:Illuminate\\\\Foundation\\\\Http\\\\Kernel::dispatchToRouter():197}\",\"class\":\"Illuminate\\\\Foundation\\\\Http\\\\Kernel\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\app\\\\Http\\\\Middleware\\\\UpdateUserSession.php\",\"line\":26,\"function\":\"{closure:Illuminate\\\\Pipeline\\\\Pipeline::prepareDestination():178}\",\"class\":\"Illuminate\\\\Pipeline\\\\Pipeline\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219,\"function\":\"handle\",\"class\":\"App\\\\Http\\\\Middleware\\\\UpdateUserSession\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\app\\\\Http\\\\Middleware\\\\PerformanceMonitoring.php\",\"line\":21,\"function\":\"{closure:{closure:Illuminate\\\\Pipeline\\\\Pipeline::carry():194}:195}\",\"class\":\"Illuminate\\\\Pipeline\\\\Pipeline\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219,\"function\":\"handle\",\"class\":\"App\\\\Http\\\\Middleware\\\\PerformanceMonitoring\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Middleware\\\\TransformsRequest.php\",\"line\":21,\"function\":\"{closure:{closure:Illuminate\\\\Pipeline\\\\Pipeline::carry():194}:195}\",\"class\":\"Illuminate\\\\Pipeline\\\\Pipeline\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Middleware\\\\ConvertEmptyStringsToNull.php\",\"line\":31,\"function\":\"handle\",\"class\":\"Illuminate\\\\Foundation\\\\Http\\\\Middleware\\\\TransformsRequest\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219,\"function\":\"handle\",\"class\":\"Illuminate\\\\Foundation\\\\Http\\\\Middleware\\\\ConvertEmptyStringsToNull\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Middleware\\\\TransformsRequest.php\",\"line\":21,\"function\":\"{closure:{closure:Illuminate\\\\Pipeline\\\\Pipeline::carry():194}:195}\",\"class\":\"Illuminate\\\\Pipeline\\\\Pipeline\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Middleware\\\\TrimStrings.php\",\"line\":51,\"function\":\"handle\",\"class\":\"Illuminate\\\\Foundation\\\\Http\\\\Middleware\\\\TransformsRequest\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219,\"function\":\"handle\",\"class\":\"Illuminate\\\\Foundation\\\\Http\\\\Middleware\\\\TrimStrings\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Http\\\\Middleware\\\\ValidatePostSize.php\",\"line\":27,\"function\":\"{closure:{closure:Illuminate\\\\Pipeline\\\\Pipeline::carry():194}:195}\",\"class\":\"Illuminate\\\\Pipeline\\\\Pipeline\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219,\"function\":\"handle\",\"class\":\"Illuminate\\\\Http\\\\Middleware\\\\ValidatePostSize\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Middleware\\\\PreventRequestsDuringMaintenance.php\",\"line\":109,\"function\":\"{closure:{closure:Illuminate\\\\Pipeline\\\\Pipeline::carry():194}:195}\",\"class\":\"Illuminate\\\\Pipeline\\\\Pipeline\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219,\"function\":\"handle\",\"class\":\"Illuminate\\\\Foundation\\\\Http\\\\Middleware\\\\PreventRequestsDuringMaintenance\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Http\\\\Middleware\\\\HandleCors.php\",\"line\":74,\"function\":\"{closure:{closure:Illuminate\\\\Pipeline\\\\Pipeline::carry():194}:195}\",\"class\":\"Illuminate\\\\Pipeline\\\\Pipeline\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219,\"function\":\"handle\",\"class\":\"Illuminate\\\\Http\\\\Middleware\\\\HandleCors\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Http\\\\Middleware\\\\TrustProxies.php\",\"line\":58,\"function\":\"{closure:{closure:Illuminate\\\\Pipeline\\\\Pipeline::carry():194}:195}\",\"class\":\"Illuminate\\\\Pipeline\\\\Pipeline\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219,\"function\":\"handle\",\"class\":\"Illuminate\\\\Http\\\\Middleware\\\\TrustProxies\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Middleware\\\\InvokeDeferredCallbacks.php\",\"line\":22,\"function\":\"{closure:{closure:Illuminate\\\\Pipeline\\\\Pipeline::carry():194}:195}\",\"class\":\"Illuminate\\\\Pipeline\\\\Pipeline\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219,\"function\":\"handle\",\"class\":\"Illuminate\\\\Foundation\\\\Http\\\\Middleware\\\\InvokeDeferredCallbacks\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Http\\\\Middleware\\\\ValidatePathEncoding.php\",\"line\":26,\"function\":\"{closure:{closure:Illuminate\\\\Pipeline\\\\Pipeline::carry():194}:195}\",\"class\":\"Illuminate\\\\Pipeline\\\\Pipeline\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219,\"function\":\"handle\",\"class\":\"Illuminate\\\\Http\\\\Middleware\\\\ValidatePathEncoding\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":137,\"function\":\"{closure:{closure:Illuminate\\\\Pipeline\\\\Pipeline::carry():194}:195}\",\"class\":\"Illuminate\\\\Pipeline\\\\Pipeline\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Kernel.php\",\"line\":175,\"function\":\"then\",\"class\":\"Illuminate\\\\Pipeline\\\\Pipeline\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Kernel.php\",\"line\":144,\"function\":\"sendRequestThroughRouter\",\"class\":\"Illuminate\\\\Foundation\\\\Http\\\\Kernel\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Application.php\",\"line\":1220,\"function\":\"handle\",\"class\":\"Illuminate\\\\Foundation\\\\Http\\\\Kernel\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\public\\\\index.php\",\"line\":20,\"function\":\"handleRequest\",\"class\":\"Illuminate\\\\Foundation\\\\Application\",\"type\":\"->\"},{\"file\":\"C:\\\\Program Files\\\\Herd\\\\resources\\\\app.asar.unpacked\\\\resources\\\\valet\\\\server.php\",\"line\":139,\"function\":\"require\"}]},\"duration\":130,\"memory\":14,\"hostname\":\"DESKTOP-GDIKUKP\",\"user\":{\"id\":\"193e51d4-5225-4cd0-8c54-5ea3b90a5539\",\"name\":null,\"email\":\"lofylofy56@gmail.com\"}}', '2026-02-08 02:37:52'),
(23, 'a106ec85-0192-4fa5-be09-496d548efd2b', 'a106ec85-0312-4502-b974-55fad8ada576', 'df5fba598f83c148f1077c02afe5b3b2', 1, 'exception', '{\"class\":\"TypeError\",\"file\":\"E:\\\\coupony-backend\\\\app\\\\Domain\\\\Store\\\\Actions\\\\CreateStore.php\",\"line\":78,\"message\":\"App\\\\Domain\\\\Store\\\\Actions\\\\CreateStore::execute(): Return value must be of type App\\\\Domain\\\\Store\\\\Models\\\\Store, App\\\\Application\\\\Http\\\\Resources\\\\StoreResource returned\",\"context\":{\"userId\":\"193e51d4-5225-4cd0-8c54-5ea3b90a5539\"},\"trace\":[{\"file\":\"E:\\\\coupony-backend\\\\app\\\\Application\\\\Http\\\\Controllers\\\\API\\\\V1\\\\StoreController.php\",\"line\":23},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\ControllerDispatcher.php\",\"line\":46},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\Route.php\",\"line\":265},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\Route.php\",\"line\":211},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\Router.php\",\"line\":822},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":180},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\Middleware\\\\SubstituteBindings.php\",\"line\":50},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Auth\\\\Middleware\\\\Authenticate.php\",\"line\":63},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":137},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\Router.php\",\"line\":821},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\Router.php\",\"line\":800},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\Router.php\",\"line\":764},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\Router.php\",\"line\":753},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Kernel.php\",\"line\":200},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":180},{\"file\":\"E:\\\\coupony-backend\\\\app\\\\Http\\\\Middleware\\\\UpdateUserSession.php\",\"line\":26},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219},{\"file\":\"E:\\\\coupony-backend\\\\app\\\\Http\\\\Middleware\\\\PerformanceMonitoring.php\",\"line\":21},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Middleware\\\\TransformsRequest.php\",\"line\":21},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Middleware\\\\ConvertEmptyStringsToNull.php\",\"line\":31},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Middleware\\\\TransformsRequest.php\",\"line\":21},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Middleware\\\\TrimStrings.php\",\"line\":51},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Http\\\\Middleware\\\\ValidatePostSize.php\",\"line\":27},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Middleware\\\\PreventRequestsDuringMaintenance.php\",\"line\":109},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Http\\\\Middleware\\\\HandleCors.php\",\"line\":74},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Http\\\\Middleware\\\\TrustProxies.php\",\"line\":58},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Middleware\\\\InvokeDeferredCallbacks.php\",\"line\":22},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Http\\\\Middleware\\\\ValidatePathEncoding.php\",\"line\":26},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":137},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Kernel.php\",\"line\":175},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Kernel.php\",\"line\":144},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Application.php\",\"line\":1220},{\"file\":\"E:\\\\coupony-backend\\\\public\\\\index.php\",\"line\":20},{\"file\":\"C:\\\\Program Files\\\\Herd\\\\resources\\\\app.asar.unpacked\\\\resources\\\\valet\\\\server.php\",\"line\":139}],\"line_preview\":{\"69\":\"                \'role_id\' => $owner->roles()->where(\'name\', \'seller\')->first()->id,\\r\",\"70\":\"                \'role\' => \'seller\',\\r\",\"71\":\"                \'store_id\' => $store->id,\\r\",\"72\":\"            ]);\\r\",\"73\":\"\\r\",\"74\":\"            $this->createDefaultStoreHours($store);\\r\",\"75\":\"\\r\",\"76\":\"            event(new StoreCreated($store));\\r\",\"77\":\"            return new StoreResource($store);\\r\",\"78\":\"        });\\r\",\"79\":\"    }\\r\",\"80\":\"    private function createDefaultStoreHours(Store $store): void\\r\",\"81\":\"    {\\r\",\"82\":\"        $defaultHours = [\\r\",\"83\":\"            [\'day_of_week\' => 1, \'open_time\' => \'09:00\', \'close_time\' => \'17:00\', \'is_closed\' => false],\\r\",\"84\":\"            [\'day_of_week\' => 2, \'open_time\' => \'09:00\', \'close_time\' => \'17:00\', \'is_closed\' => false],\\r\",\"85\":\"            [\'day_of_week\' => 3, \'open_time\' => \'09:00\', \'close_time\' => \'17:00\', \'is_closed\' => false],\\r\",\"86\":\"            [\'day_of_week\' => 4, \'open_time\' => \'09:00\', \'close_time\' => \'17:00\', \'is_closed\' => false],\\r\",\"87\":\"            [\'day_of_week\' => 5, \'open_time\' => \'09:00\', \'close_time\' => \'17:00\', \'is_closed\' => false],\\r\",\"88\":\"            [\'day_of_week\' => 6, \'open_time\' => \'09:00\', \'close_time\' => \'17:00\', \'is_closed\' => true],\\r\"},\"hostname\":\"DESKTOP-GDIKUKP\",\"user\":{\"id\":\"193e51d4-5225-4cd0-8c54-5ea3b90a5539\",\"name\":null,\"email\":\"lofylofy56@gmail.com\"},\"occurrences\":2}', '2026-02-08 02:38:20'),
(24, 'a106ec85-0292-4880-a7e6-4881b688aee0', 'a106ec85-0312-4502-b974-55fad8ada576', NULL, 1, 'request', '{\"ip_address\":\"127.0.0.1\",\"uri\":\"\\/api\\/v1\\/store\\/create\",\"method\":\"POST\",\"controller_action\":\"App\\\\Application\\\\Http\\\\Controllers\\\\API\\\\V1\\\\StoreController@create\",\"middleware\":[\"api\",\"auth:sanctum\"],\"headers\":{\"content-length\":\"642594\",\"content-type\":\"multipart\\/form-data; boundary=--------------------------325871179515498689702333\",\"connection\":\"keep-alive\",\"accept-encoding\":\"gzip, deflate, br\",\"host\":\"coupony-backend.test\",\"postman-token\":\"2564efa7-0622-49ff-9ae1-987a9404fa96\",\"cache-control\":\"no-cache\",\"user-agent\":\"PostmanRuntime\\/7.51.1\",\"authorization\":\"********\",\"accept\":\"application\\/json\"},\"payload\":{\"name\":\"HM\",\"phone\":\"01025250321\",\"address_line1\":\"gamal street\",\"city\":\"fayoum\",\"categories\":[\"2\"],\"verification_docs\":{\"commercial_register\":{\"name\":\"800.jpg\",\"size\":\"135.886KB\"},\"tax_card\":{\"name\":\"800.jpg\",\"size\":\"135.886KB\"},\"id_card_front\":{\"name\":\"800.jpg\",\"size\":\"135.886KB\"},\"id_card_back\":{\"name\":\"FrontendCV.pdf\",\"size\":\"233.595KB\"}}},\"session\":[],\"response_headers\":{\"cache-control\":\"no-cache, private\",\"date\":\"Sun, 08 Feb 2026 00:38:20 GMT\",\"content-type\":\"application\\/json\",\"access-control-allow-origin\":\"*\"},\"response_status\":500,\"response\":{\"message\":\"App\\\\Domain\\\\Store\\\\Actions\\\\CreateStore::execute(): Return value must be of type App\\\\Domain\\\\Store\\\\Models\\\\Store, App\\\\Application\\\\Http\\\\Resources\\\\StoreResource returned\",\"exception\":\"TypeError\",\"file\":\"E:\\\\coupony-backend\\\\app\\\\Domain\\\\Store\\\\Actions\\\\CreateStore.php\",\"line\":78,\"trace\":[{\"file\":\"E:\\\\coupony-backend\\\\app\\\\Application\\\\Http\\\\Controllers\\\\API\\\\V1\\\\StoreController.php\",\"line\":23,\"function\":\"execute\",\"class\":\"App\\\\Domain\\\\Store\\\\Actions\\\\CreateStore\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\ControllerDispatcher.php\",\"line\":46,\"function\":\"create\",\"class\":\"App\\\\Application\\\\Http\\\\Controllers\\\\API\\\\V1\\\\StoreController\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\Route.php\",\"line\":265,\"function\":\"dispatch\",\"class\":\"Illuminate\\\\Routing\\\\ControllerDispatcher\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\Route.php\",\"line\":211,\"function\":\"runController\",\"class\":\"Illuminate\\\\Routing\\\\Route\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\Router.php\",\"line\":822,\"function\":\"run\",\"class\":\"Illuminate\\\\Routing\\\\Route\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":180,\"function\":\"{closure:Illuminate\\\\Routing\\\\Router::runRouteWithinStack():821}\",\"class\":\"Illuminate\\\\Routing\\\\Router\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\Middleware\\\\SubstituteBindings.php\",\"line\":50,\"function\":\"{closure:Illuminate\\\\Pipeline\\\\Pipeline::prepareDestination():178}\",\"class\":\"Illuminate\\\\Pipeline\\\\Pipeline\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219,\"function\":\"handle\",\"class\":\"Illuminate\\\\Routing\\\\Middleware\\\\SubstituteBindings\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Auth\\\\Middleware\\\\Authenticate.php\",\"line\":63,\"function\":\"{closure:{closure:Illuminate\\\\Pipeline\\\\Pipeline::carry():194}:195}\",\"class\":\"Illuminate\\\\Pipeline\\\\Pipeline\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219,\"function\":\"handle\",\"class\":\"Illuminate\\\\Auth\\\\Middleware\\\\Authenticate\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":137,\"function\":\"{closure:{closure:Illuminate\\\\Pipeline\\\\Pipeline::carry():194}:195}\",\"class\":\"Illuminate\\\\Pipeline\\\\Pipeline\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\Router.php\",\"line\":821,\"function\":\"then\",\"class\":\"Illuminate\\\\Pipeline\\\\Pipeline\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\Router.php\",\"line\":800,\"function\":\"runRouteWithinStack\",\"class\":\"Illuminate\\\\Routing\\\\Router\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\Router.php\",\"line\":764,\"function\":\"runRoute\",\"class\":\"Illuminate\\\\Routing\\\\Router\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Routing\\\\Router.php\",\"line\":753,\"function\":\"dispatchToRoute\",\"class\":\"Illuminate\\\\Routing\\\\Router\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Kernel.php\",\"line\":200,\"function\":\"dispatch\",\"class\":\"Illuminate\\\\Routing\\\\Router\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":180,\"function\":\"{closure:Illuminate\\\\Foundation\\\\Http\\\\Kernel::dispatchToRouter():197}\",\"class\":\"Illuminate\\\\Foundation\\\\Http\\\\Kernel\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\app\\\\Http\\\\Middleware\\\\UpdateUserSession.php\",\"line\":26,\"function\":\"{closure:Illuminate\\\\Pipeline\\\\Pipeline::prepareDestination():178}\",\"class\":\"Illuminate\\\\Pipeline\\\\Pipeline\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219,\"function\":\"handle\",\"class\":\"App\\\\Http\\\\Middleware\\\\UpdateUserSession\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\app\\\\Http\\\\Middleware\\\\PerformanceMonitoring.php\",\"line\":21,\"function\":\"{closure:{closure:Illuminate\\\\Pipeline\\\\Pipeline::carry():194}:195}\",\"class\":\"Illuminate\\\\Pipeline\\\\Pipeline\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219,\"function\":\"handle\",\"class\":\"App\\\\Http\\\\Middleware\\\\PerformanceMonitoring\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Middleware\\\\TransformsRequest.php\",\"line\":21,\"function\":\"{closure:{closure:Illuminate\\\\Pipeline\\\\Pipeline::carry():194}:195}\",\"class\":\"Illuminate\\\\Pipeline\\\\Pipeline\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Middleware\\\\ConvertEmptyStringsToNull.php\",\"line\":31,\"function\":\"handle\",\"class\":\"Illuminate\\\\Foundation\\\\Http\\\\Middleware\\\\TransformsRequest\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219,\"function\":\"handle\",\"class\":\"Illuminate\\\\Foundation\\\\Http\\\\Middleware\\\\ConvertEmptyStringsToNull\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Middleware\\\\TransformsRequest.php\",\"line\":21,\"function\":\"{closure:{closure:Illuminate\\\\Pipeline\\\\Pipeline::carry():194}:195}\",\"class\":\"Illuminate\\\\Pipeline\\\\Pipeline\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Middleware\\\\TrimStrings.php\",\"line\":51,\"function\":\"handle\",\"class\":\"Illuminate\\\\Foundation\\\\Http\\\\Middleware\\\\TransformsRequest\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219,\"function\":\"handle\",\"class\":\"Illuminate\\\\Foundation\\\\Http\\\\Middleware\\\\TrimStrings\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Http\\\\Middleware\\\\ValidatePostSize.php\",\"line\":27,\"function\":\"{closure:{closure:Illuminate\\\\Pipeline\\\\Pipeline::carry():194}:195}\",\"class\":\"Illuminate\\\\Pipeline\\\\Pipeline\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219,\"function\":\"handle\",\"class\":\"Illuminate\\\\Http\\\\Middleware\\\\ValidatePostSize\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Middleware\\\\PreventRequestsDuringMaintenance.php\",\"line\":109,\"function\":\"{closure:{closure:Illuminate\\\\Pipeline\\\\Pipeline::carry():194}:195}\",\"class\":\"Illuminate\\\\Pipeline\\\\Pipeline\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219,\"function\":\"handle\",\"class\":\"Illuminate\\\\Foundation\\\\Http\\\\Middleware\\\\PreventRequestsDuringMaintenance\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Http\\\\Middleware\\\\HandleCors.php\",\"line\":74,\"function\":\"{closure:{closure:Illuminate\\\\Pipeline\\\\Pipeline::carry():194}:195}\",\"class\":\"Illuminate\\\\Pipeline\\\\Pipeline\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219,\"function\":\"handle\",\"class\":\"Illuminate\\\\Http\\\\Middleware\\\\HandleCors\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Http\\\\Middleware\\\\TrustProxies.php\",\"line\":58,\"function\":\"{closure:{closure:Illuminate\\\\Pipeline\\\\Pipeline::carry():194}:195}\",\"class\":\"Illuminate\\\\Pipeline\\\\Pipeline\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219,\"function\":\"handle\",\"class\":\"Illuminate\\\\Http\\\\Middleware\\\\TrustProxies\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Middleware\\\\InvokeDeferredCallbacks.php\",\"line\":22,\"function\":\"{closure:{closure:Illuminate\\\\Pipeline\\\\Pipeline::carry():194}:195}\",\"class\":\"Illuminate\\\\Pipeline\\\\Pipeline\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219,\"function\":\"handle\",\"class\":\"Illuminate\\\\Foundation\\\\Http\\\\Middleware\\\\InvokeDeferredCallbacks\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Http\\\\Middleware\\\\ValidatePathEncoding.php\",\"line\":26,\"function\":\"{closure:{closure:Illuminate\\\\Pipeline\\\\Pipeline::carry():194}:195}\",\"class\":\"Illuminate\\\\Pipeline\\\\Pipeline\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":219,\"function\":\"handle\",\"class\":\"Illuminate\\\\Http\\\\Middleware\\\\ValidatePathEncoding\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Pipeline\\\\Pipeline.php\",\"line\":137,\"function\":\"{closure:{closure:Illuminate\\\\Pipeline\\\\Pipeline::carry():194}:195}\",\"class\":\"Illuminate\\\\Pipeline\\\\Pipeline\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Kernel.php\",\"line\":175,\"function\":\"then\",\"class\":\"Illuminate\\\\Pipeline\\\\Pipeline\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Http\\\\Kernel.php\",\"line\":144,\"function\":\"sendRequestThroughRouter\",\"class\":\"Illuminate\\\\Foundation\\\\Http\\\\Kernel\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\vendor\\\\laravel\\\\framework\\\\src\\\\Illuminate\\\\Foundation\\\\Application.php\",\"line\":1220,\"function\":\"handle\",\"class\":\"Illuminate\\\\Foundation\\\\Http\\\\Kernel\",\"type\":\"->\"},{\"file\":\"E:\\\\coupony-backend\\\\public\\\\index.php\",\"line\":20,\"function\":\"handleRequest\",\"class\":\"Illuminate\\\\Foundation\\\\Application\",\"type\":\"->\"},{\"file\":\"C:\\\\Program Files\\\\Herd\\\\resources\\\\app.asar.unpacked\\\\resources\\\\valet\\\\server.php\",\"line\":139,\"function\":\"require\"}]},\"duration\":135,\"memory\":12,\"hostname\":\"DESKTOP-GDIKUKP\",\"user\":{\"id\":\"193e51d4-5225-4cd0-8c54-5ea3b90a5539\",\"name\":null,\"email\":\"lofylofy56@gmail.com\"}}', '2026-02-08 02:38:20'),
(25, 'a106fd75-1742-493c-ba92-4d0ccfb35624', 'a106fd75-17bf-48ef-8602-aa4d2428c525', NULL, 1, 'request', '{\"ip_address\":\"127.0.0.1\",\"uri\":\"\\/api\\/v1\\/store\\/create\",\"method\":\"POST\",\"controller_action\":\"App\\\\Application\\\\Http\\\\Controllers\\\\API\\\\V1\\\\StoreController@create\",\"middleware\":[\"api\",\"auth:sanctum\"],\"headers\":{\"content-length\":\"642594\",\"content-type\":\"multipart\\/form-data; boundary=--------------------------661509627515226868393871\",\"connection\":\"keep-alive\",\"accept-encoding\":\"gzip, deflate, br\",\"host\":\"coupony-backend.test\",\"postman-token\":\"3ca413a8-6761-41da-abc7-849a3ec84cd4\",\"cache-control\":\"no-cache\",\"user-agent\":\"PostmanRuntime\\/7.51.1\",\"authorization\":\"********\",\"accept\":\"application\\/json\"},\"payload\":{\"name\":\"HM\",\"phone\":\"01025250321\",\"address_line1\":\"gamal street\",\"city\":\"fayoum\",\"categories\":[\"2\"],\"verification_docs\":{\"commercial_register\":{\"name\":\"800.jpg\",\"size\":\"135.886KB\"},\"tax_card\":{\"name\":\"800.jpg\",\"size\":\"135.886KB\"},\"id_card_front\":{\"name\":\"800.jpg\",\"size\":\"135.886KB\"},\"id_card_back\":{\"name\":\"FrontendCV.pdf\",\"size\":\"233.595KB\"}}},\"session\":[],\"response_headers\":{\"cache-control\":\"no-cache, private\",\"date\":\"Sun, 08 Feb 2026 01:25:41 GMT\",\"content-type\":\"application\\/json\",\"access-control-allow-origin\":\"*\"},\"response_status\":500,\"response\":{\"message\":\"Failed to create store\",\"error\":\"SQLSTATE[42S22]: Column not found: 1054 Unknown column \'address_line2\' in \'field list\' (Connection: mysql, Host: 127.0.0.1, Port: 3306, Database: coupony, SQL: insert into `addressables` (`address_id`, `address_line2`, `created_at`, `latitude`, `longitude`, `owner_id`, `owner_type`, `updated_at`) values (17, ?, 2026-02-08 03:25:41, ?, ?, 23670e67-5d91-4016-a99a-fc1ef24ea4ea, App\\\\Domain\\\\Store\\\\Models\\\\Store, 2026-02-08 03:25:41))\"},\"duration\":155,\"memory\":14,\"hostname\":\"DESKTOP-GDIKUKP\",\"user\":{\"id\":\"193e51d4-5225-4cd0-8c54-5ea3b90a5539\",\"name\":null,\"email\":\"lofylofy56@gmail.com\"}}', '2026-02-08 03:25:41');

-- --------------------------------------------------------

--
-- Table structure for table `telescope_entries_tags`
--

CREATE TABLE `telescope_entries_tags` (
  `entry_uuid` char(36) NOT NULL,
  `tag` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `telescope_entries_tags`
--

INSERT INTO `telescope_entries_tags` (`entry_uuid`, `tag`) VALUES
('a106cf1a-7e6c-4d12-b7ea-3c8e0dccb288', 'Auth:a496c4b6-719e-4aee-bb31-c7cb1063522e'),
('a106cf1a-7f93-4831-9386-7c62977c16db', 'Auth:a496c4b6-719e-4aee-bb31-c7cb1063522e'),
('a106cf87-feb3-4163-8f28-069715d18325', 'Auth:a496c4b6-719e-4aee-bb31-c7cb1063522e'),
('a106cf87-ffdd-452b-ad30-977513460f2b', 'Auth:a496c4b6-719e-4aee-bb31-c7cb1063522e'),
('a106e00e-c9e7-4622-91fb-cd0f540215f3', 'Auth:193e51d4-5225-4cd0-8c54-5ea3b90a5539'),
('a106e0bd-1174-49e4-b858-a4daae1cf920', 'Auth:193e51d4-5225-4cd0-8c54-5ea3b90a5539'),
('a106e1ca-d76a-4d09-8a1c-91d418f6b824', 'Auth:193e51d4-5225-4cd0-8c54-5ea3b90a5539'),
('a106e2f9-a88b-4a5c-9b0c-dc8783581153', 'Auth:193e51d4-5225-4cd0-8c54-5ea3b90a5539'),
('a106e33e-5ff6-4a96-ac2d-216f559c70dc', 'Auth:193e51d4-5225-4cd0-8c54-5ea3b90a5539'),
('a106ec5a-5c33-4487-9ab8-6f19cc619761', 'Auth:193e51d4-5225-4cd0-8c54-5ea3b90a5539'),
('a106ec5a-5d34-4cf2-88df-94e5eaffb25f', 'Auth:193e51d4-5225-4cd0-8c54-5ea3b90a5539'),
('a106ec85-0192-4fa5-be09-496d548efd2b', 'Auth:193e51d4-5225-4cd0-8c54-5ea3b90a5539'),
('a106ec85-0292-4880-a7e6-4881b688aee0', 'Auth:193e51d4-5225-4cd0-8c54-5ea3b90a5539'),
('a106fd75-1742-493c-ba92-4d0ccfb35624', 'Auth:193e51d4-5225-4cd0-8c54-5ea3b90a5539');

-- --------------------------------------------------------

--
-- Table structure for table `telescope_monitoring`
--

CREATE TABLE `telescope_monitoring` (
  `tag` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `phone_verified_at` timestamp NULL DEFAULT NULL,
  `status` enum('active','suspended','deleted') NOT NULL DEFAULT 'active',
  `last_login_at` timestamp NULL DEFAULT NULL,
  `login_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `shard_key` varchar(50) DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `two_factor_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `last_ip` varchar(45) DEFAULT NULL,
  `provider` varchar(50) DEFAULT NULL,
  `provider_id` varchar(255) DEFAULT NULL,
  `language` varchar(10) NOT NULL DEFAULT 'ar',
  `timezone` varchar(50) NOT NULL DEFAULT 'UTC',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `password_hash`, `phone_number`, `email_verified_at`, `phone_verified_at`, `status`, `last_login_at`, `login_count`, `shard_key`, `remember_token`, `two_factor_enabled`, `last_ip`, `provider`, `provider_id`, `language`, `timezone`, `created_at`, `updated_at`, `deleted_at`) VALUES
('193e51d4-5225-4cd0-8c54-5ea3b90a5539', 'lofylofy56@gmail.com', '$2y$12$nqNgKl9lxxRPrx0jZlet3O99WW6hjNvgPEPySSgc7DwdKdR8MsOOu', NULL, '2026-02-06 17:25:19', '2026-02-06 17:25:19', 'active', '2026-02-07 23:58:05', 6, '22f275d6', '982efe845f0141e2fe6b3672e2c27d34795df7d141b94bf49bea23a58446b9c3', 0, '127.0.0.1', NULL, NULL, 'ar', 'Africa/Cairo', '2026-02-06 17:25:19', '2026-02-08 01:02:11', NULL),
('a496c4b6-719e-4aee-bb31-c7cb1063522e', 'super.admin@coupony.shop', '$2y$12$nPA/ZCc1S96qQ39SkjzwjOFxu6nMII1vfUlGvF0PDL5KF.WaoIs.m', NULL, '2026-02-06 17:12:11', '2026-02-06 17:12:11', 'active', '2026-02-07 23:12:41', 2, '21f44b4f', '0efde9e31779eb262d1a91be2f7a5d02850ec75a7649f11293d7a15da99f551e', 0, '127.0.0.1', NULL, NULL, 'ar', 'Africa/Cairo', '2026-02-06 17:12:11', '2026-02-07 23:12:41', NULL),
('c69077f2-4064-4b7d-85f7-836d04afa7a3', 'ahmedmostafabusiness3@gmail.com', '$2y$12$PE.5WCFy70mfnePOfrZZlu588VyhtxQJj7V.2YNU3WxaipmxkKoe6', NULL, NULL, NULL, 'active', NULL, 0, '20ff0a85', NULL, 0, '127.0.0.1', NULL, NULL, 'ar', 'Africa/Cairo', '2026-02-07 16:42:20', '2026-02-07 16:42:20', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_points`
--

CREATE TABLE `user_points` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` char(36) NOT NULL,
  `current_balance` int(11) NOT NULL DEFAULT 0,
  `lifetime_earned` int(11) NOT NULL DEFAULT 0,
  `lifetime_spent` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_roles`
--

CREATE TABLE `user_roles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` char(36) NOT NULL,
  `role_id` bigint(20) UNSIGNED NOT NULL,
  `store_id` char(36) DEFAULT NULL,
  `granted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `granted_by_user_id` char(36) DEFAULT NULL,
  `expires_at` time DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_roles`
--

INSERT INTO `user_roles` (`id`, `user_id`, `role_id`, `store_id`, `granted_at`, `granted_by_user_id`, `expires_at`, `created_at`, `updated_at`) VALUES
(1, '193e51d4-5225-4cd0-8c54-5ea3b90a5539', 4, NULL, '2026-02-06 17:25:19', NULL, NULL, '2026-02-06 17:25:19', '2026-02-06 17:25:19'),
(2, 'c69077f2-4064-4b7d-85f7-836d04afa7a3', 5, NULL, '2026-02-07 16:42:20', 'c69077f2-4064-4b7d-85f7-836d04afa7a3', NULL, '2026-02-07 16:42:20', '2026-02-07 16:42:20'),
(3, '193e51d4-5225-4cd0-8c54-5ea3b90a5539', 1, 'bed4702c-de6b-4dbd-a9fc-663f405e09f7', '2026-02-08 00:14:45', NULL, NULL, '2026-02-08 00:14:45', '2026-02-08 00:14:45'),
(4, '193e51d4-5225-4cd0-8c54-5ea3b90a5539', 1, 'a382425d-15bb-4e35-9988-5a8f4f99fe72', '2026-02-08 00:17:55', NULL, NULL, '2026-02-08 00:17:55', '2026-02-08 00:17:55'),
(5, '193e51d4-5225-4cd0-8c54-5ea3b90a5539', 1, '6df8be66-bac3-4b9a-b51f-7c3c23fb8481', '2026-02-08 00:25:06', NULL, NULL, '2026-02-08 00:25:06', '2026-02-08 00:25:06'),
(6, '193e51d4-5225-4cd0-8c54-5ea3b90a5539', 1, '61df8a7d-2493-4911-b3ec-8f9d9fcb6e69', '2026-02-08 00:26:20', NULL, NULL, '2026-02-08 00:26:20', '2026-02-08 00:26:20'),
(7, '193e51d4-5225-4cd0-8c54-5ea3b90a5539', 1, '8083ad58-00c2-49f0-b282-7dcd39beb107', '2026-02-08 00:37:52', NULL, NULL, '2026-02-08 00:37:52', '2026-02-08 00:37:52'),
(8, '193e51d4-5225-4cd0-8c54-5ea3b90a5539', 1, 'da952527-786d-4073-b8b2-753b64dcc99b', '2026-02-08 00:38:20', NULL, NULL, '2026-02-08 00:38:20', '2026-02-08 00:38:20'),
(9, '193e51d4-5225-4cd0-8c54-5ea3b90a5539', 1, '219f77bc-bb40-4121-9959-df510c83f90a', '2026-02-08 00:38:58', NULL, NULL, '2026-02-08 00:38:58', '2026-02-08 00:38:58'),
(10, '193e51d4-5225-4cd0-8c54-5ea3b90a5539', 1, '2c45144b-9bcd-4b01-ace8-9133683cbf34', '2026-02-08 00:39:45', NULL, NULL, '2026-02-08 00:39:45', '2026-02-08 00:39:45'),
(11, '193e51d4-5225-4cd0-8c54-5ea3b90a5539', 1, '62984122-93ab-45df-bcb5-3a32258ab511', '2026-02-08 00:40:37', NULL, NULL, '2026-02-08 00:40:37', '2026-02-08 00:40:37'),
(12, '193e51d4-5225-4cd0-8c54-5ea3b90a5539', 1, 'd5b9c837-3f55-4ba1-9b9a-94e4f146ef42', '2026-02-08 00:41:00', NULL, NULL, '2026-02-08 00:41:00', '2026-02-08 00:41:00'),
(13, '193e51d4-5225-4cd0-8c54-5ea3b90a5539', 1, '7f239fef-820c-4d1b-8e13-f778e50568e5', '2026-02-08 00:45:38', NULL, NULL, '2026-02-08 00:45:38', '2026-02-08 00:45:38'),
(14, '193e51d4-5225-4cd0-8c54-5ea3b90a5539', 1, 'f3d9eca3-c7a3-4571-82d3-b76cf1d1f1d9', '2026-02-08 00:47:24', NULL, NULL, '2026-02-08 00:47:24', '2026-02-08 00:47:24'),
(15, '193e51d4-5225-4cd0-8c54-5ea3b90a5539', 1, '5e7ae24d-fe0c-4829-b893-4eff6ecb2791', '2026-02-08 00:52:50', NULL, NULL, '2026-02-08 00:52:50', '2026-02-08 00:52:50'),
(16, '193e51d4-5225-4cd0-8c54-5ea3b90a5539', 1, '6881dd0d-87d1-4a19-b8b8-f6e02e7aec40', '2026-02-08 01:02:23', NULL, NULL, '2026-02-08 01:02:23', '2026-02-08 01:02:23'),
(17, '193e51d4-5225-4cd0-8c54-5ea3b90a5539', 1, 'a9cea373-3add-4870-8d20-396244f0c458', '2026-02-08 01:02:52', NULL, NULL, '2026-02-08 01:02:52', '2026-02-08 01:02:52'),
(18, '193e51d4-5225-4cd0-8c54-5ea3b90a5539', 1, 'f81b7249-08f9-430f-b06d-f54b5f3ebe4e', '2026-02-08 01:04:38', NULL, NULL, '2026-02-08 01:04:38', '2026-02-08 01:04:38'),
(19, '193e51d4-5225-4cd0-8c54-5ea3b90a5539', 1, '55dbc156-4082-46dc-90cd-b543b4f18418', '2026-02-08 01:06:59', NULL, NULL, '2026-02-08 01:06:59', '2026-02-08 01:06:59'),
(20, '193e51d4-5225-4cd0-8c54-5ea3b90a5539', 1, 'ecf29eb0-81ce-4a21-8174-15ce00d5d5b0', '2026-02-08 01:10:43', NULL, NULL, '2026-02-08 01:10:43', '2026-02-08 01:10:43'),
(21, '193e51d4-5225-4cd0-8c54-5ea3b90a5539', 1, '74824326-6c95-4ff4-83c0-8ac729d97817', '2026-02-08 01:16:00', NULL, NULL, '2026-02-08 01:16:00', '2026-02-08 01:16:00'),
(22, '193e51d4-5225-4cd0-8c54-5ea3b90a5539', 1, '39b79652-beef-4877-8bd2-058eab653f6f', '2026-02-08 01:16:11', NULL, NULL, '2026-02-08 01:16:11', '2026-02-08 01:16:11'),
(23, '193e51d4-5225-4cd0-8c54-5ea3b90a5539', 1, 'ad61c349-7c87-42ec-9ab4-423f238fad87', '2026-02-08 01:19:24', NULL, NULL, '2026-02-08 01:19:24', '2026-02-08 01:19:24'),
(24, '193e51d4-5225-4cd0-8c54-5ea3b90a5539', 1, '96d9c4fa-a5cb-424e-85dc-506842dc2979', '2026-02-08 01:19:46', NULL, NULL, '2026-02-08 01:19:46', '2026-02-08 01:19:46'),
(25, '193e51d4-5225-4cd0-8c54-5ea3b90a5539', 1, '06e975df-9320-4b1a-af53-b5c8f6373b39', '2026-02-08 01:20:45', NULL, NULL, '2026-02-08 01:20:45', '2026-02-08 01:20:45'),
(26, '193e51d4-5225-4cd0-8c54-5ea3b90a5539', 1, '468070bb-5b1b-49bf-8e43-2265c4be08ef', '2026-02-08 01:21:59', NULL, NULL, '2026-02-08 01:21:59', '2026-02-08 01:21:59'),
(27, '193e51d4-5225-4cd0-8c54-5ea3b90a5539', 1, '85093754-da13-4d21-b53d-f76226b83e5e', '2026-02-08 01:22:10', NULL, NULL, '2026-02-08 01:22:10', '2026-02-08 01:22:10'),
(28, '193e51d4-5225-4cd0-8c54-5ea3b90a5539', 1, '38dc83c7-7eed-4fe8-86ba-cab830e47b7f', '2026-02-08 01:25:03', NULL, NULL, '2026-02-08 01:25:03', '2026-02-08 01:25:03'),
(29, '193e51d4-5225-4cd0-8c54-5ea3b90a5539', 1, '67d978be-f571-4ecf-b939-997daa4c16a6', '2026-02-08 01:26:14', NULL, NULL, '2026-02-08 01:26:14', '2026-02-08 01:26:14'),
(30, '193e51d4-5225-4cd0-8c54-5ea3b90a5539', 1, 'eaf79b81-447a-4a7a-8a08-f26571f298b1', '2026-02-08 01:28:08', NULL, NULL, '2026-02-08 01:28:08', '2026-02-08 01:28:08'),
(31, '193e51d4-5225-4cd0-8c54-5ea3b90a5539', 1, 'f8886dc8-36c1-4674-8aa3-cdf998cf32ce', '2026-02-08 01:28:53', NULL, NULL, '2026-02-08 01:28:53', '2026-02-08 01:28:53'),
(32, '193e51d4-5225-4cd0-8c54-5ea3b90a5539', 1, 'bd5f6b10-1640-4fe0-98a3-00f2eebf48f6', '2026-02-08 01:29:07', NULL, NULL, '2026-02-08 01:29:07', '2026-02-08 01:29:07'),
(33, '193e51d4-5225-4cd0-8c54-5ea3b90a5539', 1, 'fe50f29d-ddb5-4e6f-80e3-47cb34893a07', '2026-02-08 01:29:12', NULL, NULL, '2026-02-08 01:29:12', '2026-02-08 01:29:12'),
(34, '193e51d4-5225-4cd0-8c54-5ea3b90a5539', 1, '99cc4e46-477f-49ae-8716-ec4ac94a83f3', '2026-02-08 01:29:13', NULL, NULL, '2026-02-08 01:29:13', '2026-02-08 01:29:13'),
(35, '193e51d4-5225-4cd0-8c54-5ea3b90a5539', 1, '1209d5a1-5ffa-4f77-b073-99f7098ae404', '2026-02-08 01:30:11', NULL, NULL, '2026-02-08 01:30:11', '2026-02-08 01:30:11'),
(36, '193e51d4-5225-4cd0-8c54-5ea3b90a5539', 1, '1d26abd4-7ab2-49e4-9feb-decb8767a1d9', '2026-02-08 01:30:41', NULL, NULL, '2026-02-08 01:30:41', '2026-02-08 01:30:41'),
(37, '193e51d4-5225-4cd0-8c54-5ea3b90a5539', 1, '76428452-698b-4516-80ff-1f8223faf4e2', '2026-02-08 01:31:23', NULL, NULL, '2026-02-08 01:31:23', '2026-02-08 01:31:23'),
(38, '193e51d4-5225-4cd0-8c54-5ea3b90a5539', 1, 'a9abfce0-b6b0-41d0-be5d-8ce27662bace', '2026-02-08 01:31:46', NULL, NULL, '2026-02-08 01:31:46', '2026-02-08 01:31:46'),
(39, '193e51d4-5225-4cd0-8c54-5ea3b90a5539', 1, 'eb379eb9-994b-466e-95fb-ce9344b37311', '2026-02-08 01:33:12', NULL, NULL, '2026-02-08 01:33:12', '2026-02-08 01:33:12'),
(40, '193e51d4-5225-4cd0-8c54-5ea3b90a5539', 1, 'e608bf43-4e36-4823-8fda-b46399ad160f', '2026-02-08 01:33:33', NULL, NULL, '2026-02-08 01:33:33', '2026-02-08 01:33:33'),
(41, '193e51d4-5225-4cd0-8c54-5ea3b90a5539', 1, 'ee205576-2965-4202-b8a6-5f3f43c8e31a', '2026-02-08 01:33:52', NULL, NULL, '2026-02-08 01:33:52', '2026-02-08 01:33:52'),
(42, '193e51d4-5225-4cd0-8c54-5ea3b90a5539', 1, '8820cda8-50d1-4617-b741-f91ae252d253', '2026-02-08 01:33:58', NULL, NULL, '2026-02-08 01:33:58', '2026-02-08 01:33:58'),
(43, '193e51d4-5225-4cd0-8c54-5ea3b90a5539', 1, '22507230-3892-4e78-9681-81c0ec94d7d0', '2026-02-08 01:34:13', NULL, NULL, '2026-02-08 01:34:13', '2026-02-08 01:34:13');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `addressables`
--
ALTER TABLE `addressables`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_owner_address` (`owner_type`,`owner_id`,`address_id`),
  ADD KEY `addressables_owner_type_owner_id_index` (`owner_type`,`owner_id`),
  ADD KEY `idx_owner` (`owner_type`,`owner_id`),
  ADD KEY `idx_address` (`address_id`);

--
-- Indexes for table `addresses`
--
ALTER TABLE `addresses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_lat_lng` (`latitude`,`longitude`);

--
-- Indexes for table `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_expiration_index` (`expiration`);

--
-- Indexes for table `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_locks_expiration_index` (`expiration`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `contact_us_customer`
--
ALTER TABLE `contact_us_customer`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `contact_us_seller`
--
ALTER TABLE `contact_us_seller`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `interests`
--
ALTER TABLE `interests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `interests_user_id_unique` (`user_id`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Indexes for table `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `model_has_permissions`
--
ALTER TABLE `model_has_permissions`
  ADD PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  ADD KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`);

--
-- Indexes for table `model_has_roles`
--
ALTER TABLE `model_has_roles`
  ADD PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  ADD KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_status` (`user_id`,`status`),
  ADD KEY `idx_created` (`created_at`),
  ADD KEY `idx_unread` (`user_id`,`read_at`);

--
-- Indexes for table `notify_me`
--
ALTER TABLE `notify_me`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `otps`
--
ALTER TABLE `otps`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_phone_email` (`phone_or_email`),
  ADD KEY `idx_purpose` (`purpose`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_expires` (`expires_at`),
  ADD KEY `otps_user_id_foreign` (`user_id`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`);

--
-- Indexes for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  ADD KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  ADD KEY `personal_access_tokens_expires_at_index` (`expires_at`);

--
-- Indexes for table `profiles`
--
ALTER TABLE `profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `profiles_user_id_unique` (`user_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`);

--
-- Indexes for table `role_has_permissions`
--
ALTER TABLE `role_has_permissions`
  ADD PRIMARY KEY (`permission_id`,`role_id`),
  ADD KEY `role_has_permissions_role_id_foreign` (`role_id`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sessions_token_unique` (`token`),
  ADD KEY `idx_user_expires` (`user_id`,`expires_at`),
  ADD KEY `idx_token` (`token`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indexes for table `stores`
--
ALTER TABLE `stores`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_owner` (`owner_user_id`),
  ADD KEY `idx_subscription` (`subscription_tier`);

--
-- Indexes for table `store_categories`
--
ALTER TABLE `store_categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `store_followers`
--
ALTER TABLE `store_followers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_store` (`user_id`,`store_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_store` (`store_id`);

--
-- Indexes for table `store_hours`
--
ALTER TABLE `store_hours`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_store_day` (`store_id`,`day_of_week`);

--
-- Indexes for table `store_store_category`
--
ALTER TABLE `store_store_category`
  ADD PRIMARY KEY (`store_id`,`store_category_id`),
  ADD KEY `store_store_category_store_category_id_foreign` (`store_category_id`);

--
-- Indexes for table `store_verifications`
--
ALTER TABLE `store_verifications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `store_verifications_store_id_document_type_unique` (`store_id`,`document_type`),
  ADD KEY `store_verifications_store_id_index` (`store_id`),
  ADD KEY `store_verifications_status_index` (`status`),
  ADD KEY `store_verifications_document_type_index` (`document_type`);

--
-- Indexes for table `telescope_entries`
--
ALTER TABLE `telescope_entries`
  ADD PRIMARY KEY (`sequence`),
  ADD UNIQUE KEY `telescope_entries_uuid_unique` (`uuid`),
  ADD KEY `telescope_entries_batch_id_index` (`batch_id`),
  ADD KEY `telescope_entries_family_hash_index` (`family_hash`),
  ADD KEY `telescope_entries_created_at_index` (`created_at`),
  ADD KEY `telescope_entries_type_should_display_on_index_index` (`type`,`should_display_on_index`);

--
-- Indexes for table `telescope_entries_tags`
--
ALTER TABLE `telescope_entries_tags`
  ADD PRIMARY KEY (`entry_uuid`,`tag`),
  ADD KEY `telescope_entries_tags_tag_index` (`tag`);

--
-- Indexes for table `telescope_monitoring`
--
ALTER TABLE `telescope_monitoring`
  ADD PRIMARY KEY (`tag`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`),
  ADD UNIQUE KEY `users_phone_number_unique` (`phone_number`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_phone` (`phone_number`),
  ADD KEY `idx_provider` (`provider`,`provider_id`),
  ADD KEY `idx_status_created` (`status`,`created_at`),
  ADD KEY `idx_shard_key` (`shard_key`);

--
-- Indexes for table `user_points`
--
ALTER TABLE `user_points`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_points_user_id_unique` (`user_id`);

--
-- Indexes for table `user_roles`
--
ALTER TABLE `user_roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_role_store` (`user_id`,`role_id`,`store_id`),
  ADD KEY `idx_user_store` (`user_id`,`store_id`),
  ADD KEY `user_roles_role_id_foreign` (`role_id`),
  ADD KEY `user_roles_store_id_foreign` (`store_id`),
  ADD KEY `user_roles_granted_by_user_id_foreign` (`granted_by_user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `addressables`
--
ALTER TABLE `addressables`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT for table `addresses`
--
ALTER TABLE `addresses`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `contact_us_customer`
--
ALTER TABLE `contact_us_customer`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `contact_us_seller`
--
ALTER TABLE `contact_us_seller`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `interests`
--
ALTER TABLE `interests`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `notify_me`
--
ALTER TABLE `notify_me`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `otps`
--
ALTER TABLE `otps`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `profiles`
--
ALTER TABLE `profiles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `store_categories`
--
ALTER TABLE `store_categories`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `store_followers`
--
ALTER TABLE `store_followers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `store_hours`
--
ALTER TABLE `store_hours`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=288;

--
-- AUTO_INCREMENT for table `telescope_entries`
--
ALTER TABLE `telescope_entries`
  MODIFY `sequence` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `user_points`
--
ALTER TABLE `user_points`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_roles`
--
ALTER TABLE `user_roles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `addressables`
--
ALTER TABLE `addressables`
  ADD CONSTRAINT `addressables_address_id_foreign` FOREIGN KEY (`address_id`) REFERENCES `addresses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `interests`
--
ALTER TABLE `interests`
  ADD CONSTRAINT `interests_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `model_has_permissions`
--
ALTER TABLE `model_has_permissions`
  ADD CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `model_has_roles`
--
ALTER TABLE `model_has_roles`
  ADD CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `otps`
--
ALTER TABLE `otps`
  ADD CONSTRAINT `otps_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `profiles`
--
ALTER TABLE `profiles`
  ADD CONSTRAINT `profiles_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `role_has_permissions`
--
ALTER TABLE `role_has_permissions`
  ADD CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sessions`
--
ALTER TABLE `sessions`
  ADD CONSTRAINT `sessions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `stores`
--
ALTER TABLE `stores`
  ADD CONSTRAINT `stores_owner_user_id_foreign` FOREIGN KEY (`owner_user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `store_followers`
--
ALTER TABLE `store_followers`
  ADD CONSTRAINT `store_followers_store_id_foreign` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `store_followers_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `store_hours`
--
ALTER TABLE `store_hours`
  ADD CONSTRAINT `store_hours_store_id_foreign` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `store_store_category`
--
ALTER TABLE `store_store_category`
  ADD CONSTRAINT `store_store_category_store_category_id_foreign` FOREIGN KEY (`store_category_id`) REFERENCES `store_categories` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `store_store_category_store_id_foreign` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `store_verifications`
--
ALTER TABLE `store_verifications`
  ADD CONSTRAINT `store_verifications_store_id_foreign` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `telescope_entries_tags`
--
ALTER TABLE `telescope_entries_tags`
  ADD CONSTRAINT `telescope_entries_tags_entry_uuid_foreign` FOREIGN KEY (`entry_uuid`) REFERENCES `telescope_entries` (`uuid`) ON DELETE CASCADE;

--
-- Constraints for table `user_points`
--
ALTER TABLE `user_points`
  ADD CONSTRAINT `user_points_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_roles`
--
ALTER TABLE `user_roles`
  ADD CONSTRAINT `user_roles_granted_by_user_id_foreign` FOREIGN KEY (`granted_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `user_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_roles_store_id_foreign` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_roles_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
