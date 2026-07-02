-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 23, 2026 at 09:14 AM
-- Server version: 8.0.39
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `villatry`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--

CREATE TABLE `activity_log` (
  `log_id` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `admin_id` int NOT NULL,
  `activity_type` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `inquiry_id` int DEFAULT NULL,
  `reserve_id` int DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_log`
--

INSERT INTO `activity_log` (`log_id`, `admin_id`, `activity_type`, `description`, `inquiry_id`, `reserve_id`, `ip_address`, `user_agent`, `created_at`, `updated_at`) VALUES
('LOG_6835a0b7730ba2.05778956', 14, 'logout', 'Admin \'g ma\' logged out. Session duration: 00:00:00', NULL, NULL, '0', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-27 11:23:35', '2025-06-07 16:30:01'),
('LOG_6835a372926f34.26377368', 14, 'login', 'Admin \'g ma\' logged in successfully', NULL, NULL, '0', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-27 11:35:14', '2025-06-07 16:30:01'),
('LOG_6835a3c2cb1047.71229428', 14, 'login', 'Admin \'g ma\' logged in successfully', NULL, NULL, '0', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-27 11:36:34', '2025-06-07 16:30:01'),
('LOG_6835a3d6538701.05196136', 14, 'login', 'Admin \'g ma\' logged in successfully', NULL, NULL, '0', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-27 11:36:54', '2025-06-07 16:30:01'),
('LOG_6835a47b14c937.13741694', 14, 'logout', 'Admin \'g ma\' logged out. Session duration: 00:02:45', NULL, NULL, '0', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-27 11:39:39', '2025-06-07 16:30:01'),
('LOG_6835a5ab7045e0.21456033', 14, 'login', 'Admin \'g ma\' logged in successfully', NULL, NULL, '0', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-27 11:44:43', '2025-06-07 16:30:01'),
('LOG_6836b6a11220b2.32379875', 14, 'login', 'Admin \'g ma\' logged in successfully', NULL, NULL, '0', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-28 07:09:21', '2025-06-07 16:30:01'),
('LOG_6836c2e25afc59.80920327', 14, 'update_status', 'Admin \'g ma\' changed inquiry #25 status to \'In Progress\'', 25, NULL, '0', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-28 08:01:38', '2025-06-07 16:30:01'),
('LOG_683701daf2bc40.43606339', 14, 'login', 'Admin \'g ma\' logged in successfully', NULL, NULL, '0', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-28 12:30:18', '2025-06-07 16:30:01'),
('LOG_6838390931de10.01629380', 14, 'login', 'Admin \'g ma\' logged in successfully', NULL, NULL, '0', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-29 10:38:01', '2025-06-07 16:30:01'),
('LOG_68386e2f95c2b8.65114470', 14, 'update_status', 'Admin \'g ma\' changed inquiry #27 status to \'In Progress\'', 27, NULL, '0', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-29 14:24:47', '2025-06-07 16:30:01'),
('LOG_68393882070774.99380679', 16, 'login', 'Admin \'TV Five\' logged in successfully', NULL, NULL, '0', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-30 04:48:02', '2025-06-07 16:30:01'),
('LOG_6839461947a334.76796397', 16, 'update_status', 'Admin \'TV Five\' changed inquiry #26 status to \'Completed\'', 26, NULL, '0', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-30 05:46:01', '2025-06-07 16:30:01'),
('LOG_6839461948f472.17537118', 16, 'create_reservation', 'Reservation created for inquiry #26 by \'TV Five\'', 26, 1, '0', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-30 05:46:01', '2025-06-07 16:30:01'),
('LOG_683946ba8bdf17.74211326', 16, 'undo_reservation', 'Admin \'TV Five\' reverted inquiry #26 (deleted reservation #1)', 26, 1, '0', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-30 05:48:42', '2025-06-07 16:30:01'),
('LOG_683947c2329d76.94624041', 16, 'update_status', 'Admin \'TV Five\' changed inquiry #30 status to \'Pending\'', 30, NULL, '0', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-30 05:53:06', '2025-06-07 16:30:01'),
('LOG_683947c38b96b7.86420779', 16, 'update_status', 'Admin \'TV Five\' changed inquiry #31 status to \'Pending\'', 31, NULL, '0', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-30 05:53:07', '2025-06-07 16:30:01'),
('LOG_683947d99fc842.79113190', 16, 'update_status', 'Admin \'TV Five\' changed inquiry #14 status to \'Completed\'', 14, NULL, '0', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-30 05:53:29', '2025-06-07 16:30:01'),
('LOG_683947d9a13d04.26223604', 16, 'create_reservation', 'Reservation created for inquiry #14 by \'TV Five\'', 14, 2, '0', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-30 05:53:29', '2025-06-07 16:30:01'),
('LOG_683947db2086d2.14936357', 16, 'update_status', 'Admin \'TV Five\' changed inquiry #24 status to \'Cancelled\'', 24, NULL, '0', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-30 05:53:31', '2025-06-07 16:30:01'),
('LOG_683947dc78bf54.55761504', 16, 'update_status', 'Admin \'TV Five\' changed inquiry #24 status to \'Completed\'', 24, NULL, '0', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-30 05:53:32', '2025-06-07 16:30:01'),
('LOG_683947dc7a2867.02368999', 16, 'create_reservation', 'Reservation created for inquiry #24 by \'TV Five\'', 24, 3, '0', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-30 05:53:32', '2025-06-07 16:30:01'),
('LOG_683947df773b89.78509515', 16, 'update_status', 'Admin \'TV Five\' changed inquiry #17 status to \'Completed\'', 17, NULL, '0', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-30 05:53:35', '2025-06-07 16:30:01'),
('LOG_683947df785792.42097679', 16, 'create_reservation', 'Reservation created for inquiry #17 by \'TV Five\'', 17, 4, '0', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-30 05:53:35', '2025-06-07 16:30:01'),
('LOG_683947e11f7402.54688492', 16, 'update_status', 'Admin \'TV Five\' changed inquiry #20 status to \'Completed\'', 20, NULL, '0', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-30 05:53:37', '2025-06-07 16:30:01'),
('LOG_683947e12149c1.68314083', 16, 'create_reservation', 'Reservation created for inquiry #20 by \'TV Five\'', 20, 5, '0', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-30 05:53:37', '2025-06-07 16:30:01'),
('LOG_683947e2ae3ce2.02505596', 16, 'update_status', 'Admin \'TV Five\' changed inquiry #7 status to \'Completed\'', 7, NULL, '0', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-30 05:53:38', '2025-06-07 16:30:01'),
('LOG_683947e2af8633.73054596', 16, 'create_reservation', 'Reservation created for inquiry #7 by \'TV Five\'', 7, 6, '0', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-30 05:53:38', '2025-06-07 16:30:01'),
('LOG_683947e4669c02.35787151', 16, 'update_status', 'Admin \'TV Five\' changed inquiry #18 status to \'Completed\'', 18, NULL, '0', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-30 05:53:40', '2025-06-07 16:30:01'),
('LOG_683947e467e326.64625647', 16, 'create_reservation', 'Reservation created for inquiry #18 by \'TV Five\'', 18, 7, '0', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-30 05:53:40', '2025-06-07 16:30:01'),
('LOG_683da2f43f5c92.29258809', 14, 'login', 'Admin \'g ma\' logged in successfully', NULL, NULL, '0', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-06-02 13:11:16', '2025-06-07 16:30:01'),
('LOG_6841b3e9f3c3f3.19843225', 14, 'login', 'Admin \'g ma\' logged in successfully', NULL, NULL, '0', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-05 15:12:41', '2025-06-07 16:30:01'),
('LOG_68427be68a6a27.70784955', 14, 'login', 'Admin \'g ma\' logged in successfully', NULL, NULL, '0', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-06 05:25:58', '2025-06-07 16:30:01'),
('LOG_68427c0a154605.46212096', 14, 'login', 'Admin \'g ma\' logged in successfully', NULL, NULL, '0', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-06 05:26:34', '2025-06-07 16:30:01'),
('LOG_6844593261a404.13392553', 14, 'login', 'Admin \'g ma\' logged in successfully', NULL, NULL, '0', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-07 15:22:26', '2025-06-07 16:30:01'),
('LOG_684872f0aebb67.79469734', 14, 'login', 'Admin logged in successfully', NULL, NULL, NULL, NULL, '2025-06-10 10:01:20', '2025-06-10 10:01:20'),
('LOG_684872faf316e8.29617845', 14, 'login', 'Admin logged in successfully', NULL, NULL, NULL, NULL, '2025-06-10 10:01:30', '2025-06-10 10:01:30'),
('LOG_6848742289c159.56707309', 14, 'logout', 'Admin \'g ma\' logged out. Session duration: 00:00:00', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-10 10:06:26', '2025-06-10 10:06:26'),
('LOG_684876ebad39b4.68254837', 14, 'login', 'Admin \'g ma\' logged in successfully', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-10 10:18:19', '2025-06-10 10:18:19'),
('LOG_68487701c21151.25447880', 14, 'login', 'Admin \'g ma\' logged in successfully', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-10 10:18:41', '2025-06-10 10:18:41'),
('LOG_68487716218706.98935746', 14, 'logout', 'Admin \'g ma\' logged out. Session duration: 00:00:20', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-10 10:19:02', '2025-06-10 10:19:02'),
('LOG_6848790ce77040.93478292', 14, 'login', 'Admin \'g ma\' logged in successfully', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-10 10:27:24', '2025-06-10 10:27:24'),
('LOG_6848791c65ff02.11205736', 14, 'login', 'Admin \'g ma\' logged in successfully', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-10 10:27:40', '2025-06-10 10:27:40'),
('LOG_68487923004c52.80659969', 14, 'logout', 'Admin \'g ma\' logged out. Session duration: 00:00:06', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-10 10:27:47', '2025-06-10 10:27:47'),
('LOG_68487dba2cec47.67952032', 14, 'login', 'Admin \'g ma\' logged in successfully', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-10 10:47:22', '2025-06-10 10:47:22'),
('LOG_68493f13920051.41126765', 14, 'login', 'Admin \'g ma\' logged in successfully', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-11 00:32:19', '2025-06-11 00:32:19'),
('LOG_684951de59f731.93234490', 14, 'Created Inquiry', 'Admin g ma created inquiry #47', 47, NULL, NULL, NULL, '2025-06-11 01:52:30', '2025-06-11 01:52:30'),
('LOG_6849c7a8baf8c2.84016574', 14, 'login', 'Admin \'g ma\' logged in successfully', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-11 10:15:04', '2025-06-11 10:15:04'),
('LOG_684a313ac1ecf8.55841028', 14, 'login', 'Admin \'g ma\' logged in successfully', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-11 17:45:30', '2025-06-11 17:45:30'),
('LOG_684a788ddaeee9.78438815', 14, 'login', 'Admin \'g ma\' logged in successfully', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-11 22:49:49', '2025-06-11 22:49:49'),
('LOG_684a83d5592567.04567180', 14, 'login', 'Admin \'g ma\' logged in successfully', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-11 23:37:57', '2025-06-11 23:37:57'),
('LOG_684a934b63ca01.72430514', 14, 'System Action', 'Started editing admin profile information', NULL, NULL, NULL, NULL, '2025-06-12 00:43:55', '2025-06-12 00:43:55'),
('LOG_684a934ccf6ea6.08562352', 14, 'System Action', 'Started editing admin profile information', NULL, NULL, NULL, NULL, '2025-06-12 00:43:56', '2025-06-12 00:43:56'),
('LOG_684a936805ab12.62590027', 14, 'System Action', 'Invalid phone format entered: 2115-616-165', NULL, NULL, NULL, NULL, '2025-06-12 00:44:24', '2025-06-12 00:44:24'),
('LOG_684a936962dd96.98271774', 14, 'System Action', 'Invalid phone format entered: 2115-616-165', NULL, NULL, NULL, NULL, '2025-06-12 00:44:25', '2025-06-12 00:44:25'),
('LOG_684a936aac2e77.63506876', 14, 'System Action', 'Started editing admin profile information', NULL, NULL, NULL, NULL, '2025-06-12 00:44:26', '2025-06-12 00:44:26'),
('LOG_684a9381dbe998.14338835', 14, 'System Action', 'Started editing admin profile information', NULL, NULL, NULL, NULL, '2025-06-12 00:44:49', '2025-06-12 00:44:49'),
('LOG_684a93a1f3d402.99112615', 14, 'Profile Change', 'Name: \"\" → \"John Mayer\"; Email: \"haynako@gmail.com\" → \"jmayer@gmail.com\"; Phone: \"21156161654\" → \"0946-572-3981\"; Username: \"haynako\" → \"ADMIN John\"', NULL, NULL, NULL, NULL, '2025-06-12 00:45:21', '2025-06-12 00:45:21'),
('LOG_684a94e2507a42.74286631', 14, 'System Action', 'Started editing admin profile information', NULL, NULL, NULL, NULL, '2025-06-12 00:50:42', '2025-06-12 00:50:42'),
('LOG_684a94e897aca0.83638418', 14, 'System Action', 'Started editing admin profile information', NULL, NULL, NULL, NULL, '2025-06-12 00:50:48', '2025-06-12 00:50:48'),
('LOG_684a95065a5f06.31676814', 14, 'Profile Change', 'Name: \"\" → \"John Doe\"; Email: \"haynako@gmail.com\" → \"doejohn@gmail.com\"; Phone: \"21156161654\" → \"0948-864-2607\"; Username: \"haynako\" → \"ADMIN John\"', NULL, NULL, NULL, NULL, '2025-06-12 00:51:18', '2025-06-12 00:51:18'),
('LOG_684a95af5aaa49.88182566', 14, 'System Action', 'Started editing admin profile information', NULL, NULL, NULL, NULL, '2025-06-12 00:54:07', '2025-06-12 00:54:07'),
('LOG_684a95b2a783d5.35444530', 14, 'Profile Change', 'No changes were made to profile information', NULL, NULL, NULL, NULL, '2025-06-12 00:54:10', '2025-06-12 00:54:10'),
('LOG_684a9628f009d2.45912541', 14, 'System Action', 'Started editing admin profile information', NULL, NULL, NULL, NULL, '2025-06-12 00:56:08', '2025-06-12 00:56:08'),
('LOG_684a964f660561.17890064', 14, 'Profile Change', 'Name: \"\" → \"John Doe\"; Email: \"haynako@gmail.com\" → \"jdoe@gmail.com\"; Phone: \"21156161654\" → \"0948-864-2607\"; Username: \"haynako\" → \"ADMIN John\"', NULL, NULL, NULL, NULL, '2025-06-12 00:56:47', '2025-06-12 00:56:47'),
('LOG_684a96758decd9.68182078', 14, 'System Action', 'Started editing admin profile information', NULL, NULL, NULL, NULL, '2025-06-12 00:57:25', '2025-06-12 00:57:25'),
('LOG_684a96816e4759.74482552', 14, 'System Action', 'Started editing admin profile information', NULL, NULL, NULL, NULL, '2025-06-12 00:57:37', '2025-06-12 00:57:37'),
('LOG_684a969567a603.35283969', 14, 'Profile Change', 'Name: \"\" → \"John Doe\"; Email: \"haynako@gmail.com\" → \"jdoe@gmail.com\"; Phone: \"21156161654\" → \"0948-864-2607\"; Username: \"haynako\" → \"ADMIN John\"', NULL, NULL, NULL, NULL, '2025-06-12 00:57:57', '2025-06-12 00:57:57'),
('LOG_684a96f1d2b108.44630521', 14, 'System Action', 'Started editing admin profile information', NULL, NULL, NULL, NULL, '2025-06-12 00:59:29', '2025-06-12 00:59:29'),
('LOG_684a97073818c3.89027849', 14, 'Profile Change', 'Name: \"\" → \"John Doe\"; Email: \"haynako@gmail.com\" → \"jdoe@gmail.com\"; Phone: \"21156161654\" → \"0948-864-2607\"; Username: \"haynako\" → \"ADMIN John\"', NULL, NULL, NULL, NULL, '2025-06-12 00:59:51', '2025-06-12 00:59:51'),
('LOG_684a98379e3862.20254363', 14, 'System Action', 'Started editing admin profile information', NULL, NULL, NULL, NULL, '2025-06-12 01:04:55', '2025-06-12 01:04:55'),
('LOG_684a9846069831.22932456', 14, 'Profile Change', 'Name: \"\" → \"John Doe\"; Email: \"haynako@gmail.com\" → \"jdoe@gmail.com\"; Phone: \"21156161654\" → \"0948-864-2607\"; Username: \"haynako\" → \"ADMIN John\"', NULL, NULL, NULL, NULL, '2025-06-12 01:05:10', '2025-06-12 01:05:10'),
('LOG_684a9d81bacb39.86759472', 14, 'login', 'Admin \'g ma\' logged in successfully', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-12 01:27:29', '2025-06-12 01:27:29'),
('LOG_684a9d9672a6e2.67504985', 14, 'System Action', 'Started editing admin profile information', NULL, NULL, NULL, NULL, '2025-06-12 01:27:50', '2025-06-12 01:27:50'),
('LOG_684a9dc212c8b5.41823357', 14, 'System Action', 'Started editing admin profile information', NULL, NULL, NULL, NULL, '2025-06-12 01:28:34', '2025-06-12 01:28:34'),
('LOG_684a9dcb099886.95719893', 14, 'System Action', 'Started editing admin profile information', NULL, NULL, NULL, NULL, '2025-06-12 01:28:43', '2025-06-12 01:28:43'),
('LOG_684a9dfb962d98.61893663', 14, 'Profile Change', 'Name: \"g ma\" → \"John Doe\"; Email: \"haynako@gmail.com\" → \"jdoe@gmail.com\"; Phone: \"21156161654\" → \"0948-864-2607\"', NULL, NULL, NULL, NULL, '2025-06-12 01:29:31', '2025-06-12 01:29:31'),
('LOG_684aa637328ee3.66706893', 14, 'Security', 'Opened password change form', NULL, NULL, NULL, NULL, '2025-06-12 02:04:39', '2025-06-12 02:04:39'),
('LOG_684aa653057c64.04065894', 14, 'Security', 'Admin account password updated', NULL, NULL, NULL, NULL, '2025-06-12 02:05:07', '2025-06-12 02:05:07'),
('LOG_684aa66e6e7ed0.94755900', 14, 'Logout', 'Admin logged out of the system', NULL, NULL, NULL, NULL, '2025-06-12 02:05:34', '2025-06-12 02:05:34'),
('LOG_684aa66ea5df09.53239179', 14, 'logout', 'Admin \'John Doe\' logged out. Session duration: 00:38:04', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-12 02:05:34', '2025-06-12 02:05:34'),
('LOG_684aa6999d5a42.03349316', 14, 'login', 'Admin \'John Doe\' logged in successfully', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-12 02:06:17', '2025-06-12 02:06:17'),
('LOG_684aa983bbff55.81029679', 14, 'Security', 'Opened password change form', NULL, NULL, NULL, NULL, '2025-06-12 02:18:43', '2025-06-12 02:18:43'),
('LOG_684aabd8a0fbc5.75351155', 14, 'Security', 'Opened password change form', NULL, NULL, NULL, NULL, '2025-06-12 02:28:40', '2025-06-12 02:28:40'),
('LOG_684aaf834eb323.83629583', 14, 'Security', 'Opened password change form', NULL, NULL, NULL, NULL, '2025-06-12 02:44:19', '2025-06-12 02:44:19'),
('LOG_684aaf9c333008.22749827', 14, 'Logout', 'Admin logged out of the system', NULL, NULL, NULL, NULL, '2025-06-12 02:44:44', '2025-06-12 02:44:44'),
('LOG_684aaf9c8f0c87.36131049', 14, 'logout', 'Admin \'John Doe\' logged out. Session duration: 00:38:26', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-12 02:44:44', '2025-06-12 02:44:44'),
('LOG_684aafaa303966.39041854', 14, 'login', 'Admin \'John Doe\' logged in successfully', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-12 02:44:58', '2025-06-12 02:44:58'),
('LOG_684aafaf287027.67941860', 14, 'Security', 'Opened password change form', NULL, NULL, NULL, NULL, '2025-06-12 02:45:03', '2025-06-12 02:45:03'),
('LOG_684aafcb1d2843.38132830', 14, 'Security', 'JS fetch exception thrown', NULL, NULL, NULL, NULL, '2025-06-12 02:45:31', '2025-06-12 02:45:31'),
('LOG_684ab20b6e42c2.30399624', 14, 'Security', 'Opened password change form', NULL, NULL, NULL, NULL, '2025-06-12 02:55:07', '2025-06-12 02:55:07'),
('LOG_684ab219207654.84862285', 14, 'Security', 'JS fetch exception thrown', NULL, NULL, NULL, NULL, '2025-06-12 02:55:21', '2025-06-12 02:55:21'),
('LOG_684ab2891894e8.51589280', 14, 'Security', 'Opened password change form', NULL, NULL, NULL, NULL, '2025-06-12 02:57:13', '2025-06-12 02:57:13'),
('LOG_684ab296c96ad0.31027539', 14, 'Security', 'JS fetch exception thrown', NULL, NULL, NULL, NULL, '2025-06-12 02:57:26', '2025-06-12 02:57:26'),
('LOG_684ab2fdbf37a0.84875745', 14, 'Security', 'Opened password change form', NULL, NULL, NULL, NULL, '2025-06-12 02:59:09', '2025-06-12 02:59:09'),
('LOG_684ab328ebbd00.11651581', 14, 'Security', 'JS fetch exception thrown', NULL, NULL, NULL, NULL, '2025-06-12 02:59:52', '2025-06-12 02:59:52'),
('LOG_684ab45ab5e555.11911632', 14, 'Security', 'Opened password change form', NULL, NULL, NULL, NULL, '2025-06-12 03:04:58', '2025-06-12 03:04:58'),
('LOG_684ab46916ffd8.49601882', 14, 'Security', 'Admin account password updated', NULL, NULL, NULL, NULL, '2025-06-12 03:05:13', '2025-06-12 03:05:13'),
('LOG_684ab478e82853.95682440', 14, 'Logout', 'Admin logged out of the system', NULL, NULL, NULL, NULL, '2025-06-12 03:05:28', '2025-06-12 03:05:28'),
('LOG_684ab47953d1c7.54127262', 14, 'logout', 'Admin \'John Doe\' logged out. Session duration: 00:20:31', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-12 03:05:29', '2025-06-12 03:05:29'),
('LOG_684ab487059fd9.43522887', 14, 'login', 'Admin \'John Doe\' logged in successfully', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-12 03:05:43', '2025-06-12 03:05:43'),
('LOG_684ab701298617.63419044', 14, 'System Action', 'Opened image selection modal', NULL, NULL, NULL, NULL, '2025-06-12 03:16:17', '2025-06-12 03:16:17'),
('LOG_684ab711ca8617.05782137', 14, 'System Action', 'Started editing admin profile information', NULL, NULL, NULL, NULL, '2025-06-12 03:16:33', '2025-06-12 03:16:33'),
('LOG_684ab7d4223748.97663725', 14, 'System Action', 'Opened image selection modal', NULL, NULL, NULL, NULL, '2025-06-12 03:19:48', '2025-06-12 03:19:48'),
('LOG_684ab7d7d3bca1.40470216', 14, 'Security', 'Opened password change form', NULL, NULL, NULL, NULL, '2025-06-12 03:19:51', '2025-06-12 03:19:51'),
('LOG_684ab7f9436c07.52083657', 14, 'System Action', 'Opened image selection modal', NULL, NULL, NULL, NULL, '2025-06-12 03:20:25', '2025-06-12 03:20:25'),
('LOG_684ab7fdbc97d8.61859645', 14, 'Security', 'Opened password change form', NULL, NULL, NULL, NULL, '2025-06-12 03:20:29', '2025-06-12 03:20:29'),
('LOG_684ab800abd4e2.49821820', 14, 'System Action', 'Started editing admin profile information', NULL, NULL, NULL, NULL, '2025-06-12 03:20:32', '2025-06-12 03:20:32'),
('LOG_684ab804859b91.47935871', 14, 'System Action', 'Cancelled profile editing without saving', NULL, NULL, NULL, NULL, '2025-06-12 03:20:36', '2025-06-12 03:20:36'),
('LOG_684ab84ed66878.57171399', 14, 'System Action', 'Opened image selection modal', NULL, NULL, NULL, NULL, '2025-06-12 03:21:50', '2025-06-12 03:21:50'),
('LOG_684ab852c7d780.84464223', 14, 'System Action', 'Opened image selection modal', NULL, NULL, NULL, NULL, '2025-06-12 03:21:54', '2025-06-12 03:21:54'),
('LOG_684ab91a581044.72972749', 14, 'System Action', 'Started editing admin profile information', NULL, NULL, NULL, NULL, '2025-06-12 03:25:14', '2025-06-12 03:25:14'),
('LOG_684ab91d69c136.06802671', 14, 'Security', 'Opened password change form', NULL, NULL, NULL, NULL, '2025-06-12 03:25:17', '2025-06-12 03:25:17'),
('LOG_684ab91e8d5fa8.97625267', 14, 'Security', 'Cancelled password change without saving', NULL, NULL, NULL, NULL, '2025-06-12 03:25:18', '2025-06-12 03:25:18'),
('LOG_684ab91f7e93d5.24882390', 14, 'System Action', 'Opened image selection modal', NULL, NULL, NULL, NULL, '2025-06-12 03:25:19', '2025-06-12 03:25:19'),
('LOG_684ab926c41114.03509523', 14, 'System Action', 'Started editing admin profile information', NULL, NULL, NULL, NULL, '2025-06-12 03:25:26', '2025-06-12 03:25:26'),
('LOG_684aba863fa075.70508627', 14, 'login', 'Admin \'John Doe\' logged in successfully', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-12 03:31:18', '2025-06-12 03:31:18'),
('LOG_684aba91246855.78316644', 14, 'System Action', 'Opened image selection modal', NULL, NULL, NULL, NULL, '2025-06-12 03:31:29', '2025-06-12 03:31:29'),
('LOG_684aba93df2728.04840486', 14, 'Security', 'Opened password change form', NULL, NULL, NULL, NULL, '2025-06-12 03:31:31', '2025-06-12 03:31:31'),
('LOG_684aba95ea6764.43416996', 14, 'System Action', 'Started editing admin profile information', NULL, NULL, NULL, NULL, '2025-06-12 03:31:33', '2025-06-12 03:31:33'),
('LOG_684aecb4709509.34624192', 14, 'login', 'Admin \'John Doe\' logged in successfully', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-12 07:05:24', '2025-06-12 07:05:24'),
('LOG_684b2151e05f93.38795660', 14, 'Logout', 'Admin logged out of the system', NULL, NULL, NULL, NULL, '2025-06-12 10:49:53', '2025-06-12 10:49:53'),
('LOG_684b21522130b6.02544817', 14, 'logout', 'Admin \'John Doe\' logged out. Session duration: 03:44:29', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-12 10:49:54', '2025-06-12 10:49:54'),
('LOG_684b245589a2a5.04441720', 14, 'login', 'Admin \'John Doe\' logged in successfully', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-12 11:02:45', '2025-06-12 11:02:45'),
('LOG_684b27a7468e77.52599698', 14, 'login', 'Admin \'John Doe\' logged in successfully', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-12 11:16:55', '2025-06-12 11:16:55'),
('LOG_684b28be2f5744.32508707', 18, 'login', 'Admin \'Admin Admin\' logged in successfully', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-12 11:21:34', '2025-06-12 11:21:34'),
('LOG_684b2bb1aae590.93339084', 18, 'Created Inquiry', 'Admin Admin Admin created inquiry #52', 52, NULL, NULL, NULL, '2025-06-12 11:34:09', '2025-06-12 11:34:09'),
('LOG_684b3153d9ab93.32188584', 18, 'Created Inquiry', 'Admin Admin Admin created inquiry #53', 53, NULL, NULL, NULL, '2025-06-12 11:58:11', '2025-06-12 11:58:11'),
('LOG_684b31bc18f4a6.94394249', 18, 'Created Inquiry', 'Admin Admin Admin created inquiry #54', 54, NULL, NULL, NULL, '2025-06-12 11:59:56', '2025-06-12 11:59:56'),
('LOG_684b571fe85d97.77398520', 14, 'login', 'Admin \'John Doe\' logged in successfully', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-12 14:39:27', '2025-06-12 14:39:27'),
('LOG_6850fa460ecd87.90741190', 14, 'login', 'Admin \'John Doe\' logged in successfully', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-16 21:16:54', '2025-06-16 21:16:54'),
('LOG_6850fc2fdbcbc0.66077341', 14, 'Logout', 'Admin logged out of the system', NULL, NULL, NULL, NULL, '2025-06-16 21:25:03', '2025-06-16 21:25:03'),
('LOG_6850fc306aa4e8.32854975', 14, 'logout', 'Admin \'John Doe\' logged out. Session duration: 00:08:10', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-16 21:25:04', '2025-06-16 21:25:04'),
('LOG_6850fcbad6ab22.26134301', 14, 'login', 'Admin \'John Doe\' logged in successfully', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-16 21:27:22', '2025-06-16 21:27:22'),
('LOG_6852b825b14257.92177344', 16, 'login', 'Admin \'TV Five\' logged in successfully', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-18 04:59:17', '2025-06-18 04:59:17'),
('LOG_68537fb92fd6c3.69757811', 19, 'login', 'Admin \'Marc AdminGasta\' logged in successfully', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-18 19:10:49', '2025-06-18 19:10:49'),
('LOG_6853ab018a1e52.54950953', 20, 'login', 'Admin \'Jack Marston\' logged in successfully', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-18 22:15:29', '2025-06-18 22:15:29'),
('LOG_6853cc95563ac0.18329237', 21, 'login', 'Admin \'Daph Ney\' logged in successfully', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-19 00:38:45', '2025-06-19 00:38:45'),
('LOG_685bd10cc88190.55946189', 21, 'login', 'Admin \'Daph Ney\' logged in successfully', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-25 02:35:56', '2025-06-25 02:35:56'),
('LOG_685bd7ca51c096.96559040', 21, 'System Action', 'Opened image selection modal', NULL, NULL, NULL, NULL, '2025-06-25 03:04:42', '2025-06-25 03:04:42'),
('LOG_685bd7cb628e78.16460083', 21, 'Profile Change', 'Changed to: Professional', NULL, NULL, NULL, NULL, '2025-06-25 03:04:43', '2025-06-25 03:04:43'),
('LOG_685bd7e34d48c3.34299770', 21, 'System Action', 'All admin history entries were deleted', NULL, NULL, NULL, NULL, '2025-06-25 03:05:07', '2025-06-25 03:05:07'),
('LOG_685bd7e5b13924.26292833', 21, 'System Action', 'All admin history entries were deleted', NULL, NULL, NULL, NULL, '2025-06-25 03:05:09', '2025-06-25 03:05:09'),
('LOG_685bd7f974c988.82082035', 21, 'System Action', 'Started editing admin profile information', NULL, NULL, NULL, NULL, '2025-06-25 03:05:29', '2025-06-25 03:05:29'),
('LOG_685bd7ff986da7.67833353', 21, 'System Action', 'Cancelled profile editing without saving', NULL, NULL, NULL, NULL, '2025-06-25 03:05:35', '2025-06-25 03:05:35'),
('LOG_685bd801a3f8f2.84949898', 21, 'Security', 'Opened password change form', NULL, NULL, NULL, NULL, '2025-06-25 03:05:37', '2025-06-25 03:05:37'),
('LOG_685bd806dd7279.51237033', 21, 'Security', 'Cancelled password change without saving', NULL, NULL, NULL, NULL, '2025-06-25 03:05:42', '2025-06-25 03:05:42'),
('LOG_685bd80c909f80.43145321', 21, 'System Action', 'Opened image selection modal', NULL, NULL, NULL, NULL, '2025-06-25 03:05:48', '2025-06-25 03:05:48'),
('LOG_685bd8274b6e84.77474879', 21, 'Logout', 'Admin logged out of the system', NULL, NULL, NULL, NULL, '2025-06-25 03:06:15', '2025-06-25 03:06:15'),
('LOG_685bd8277efab7.87011329', 21, 'logout', 'Admin \'Daph Ney\' logged out. Session duration: 00:30:18', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-25 03:06:15', '2025-06-25 03:06:15');

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `admin_id` int NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `f_name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `l_name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `phone` varchar(15) COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `profile_picture` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'default.png'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`admin_id`, `email`, `f_name`, `l_name`, `password`, `phone`, `created_at`, `updated_at`, `profile_picture`) VALUES
(3, 'test@gmail.com', 'A', 'A', '$2y$10$Uj/0ZQoMxgrNqL4x13CY/OPCsunCQD6TTOAKb8Vs8EdX/S13ZfvMa', '1234', '2025-03-13 13:05:14', '2025-03-13 13:05:14', 'default.png'),
(4, 'test1@gmail.com', 'B', 'B', '$2y$10$esmV/ZRp4W9qiRy5tNMg2uB.fqs/GIa4QwR8dIOOvtYU1o9YeZ/kW', '5678', '2025-03-13 13:15:44', '2025-03-13 13:15:44', 'default.png'),
(6, 'try1@gmail.com', 'C', 'C', '$2y$10$iyqN8KXQJW.Qc8vk3kJJqehgbygTLLXcgvz2QKKIi4PJ5OnzdEhXO', '222222222222', '2025-03-14 10:34:22', '2025-03-14 10:34:22', 'default.png'),
(7, 'try2@gmail.com', 'D', 'D', '$2y$10$SMwBVTpcGEuwQ2HQokIDIunZlXH2Ctkjv3GmWyy.iroKYTIKp0NSW', '123', '2025-03-14 10:46:07', '2025-03-14 10:46:07', 'default.png'),
(8, 'testing@gmail.com', 'E', 'E', '$2y$10$pZJ/44TURLzj9wqiC1NIGu1pUfIR/dlNlGnGhs8O48RLvoyX5nbwW', '12345678910', '2025-03-14 10:51:31', '2025-03-14 10:51:31', 'default.png'),
(9, 'beta@gmail.com', 'Beta', 'beta', '$2y$10$5nnEA3U/2gW/IrECze7Zvu39TZBVu1ULxaZEGvgUXMpcDHEW2GlJm', '77777777777', '2025-03-14 11:19:56', '2025-03-14 11:19:56', 'default.png'),
(10, 'pretzel@gmail.com', 'TesterAlpha', 'TesterAlpha', '$2y$10$8KpBLtWKfaOx8a3iRm3LgewN1rCQ9FH3/X5.hGNVaBryS5tAXZsYK', '09488642607', '2025-03-20 09:21:02', '2025-03-20 09:21:02', 'default.png'),
(14, 'jdoe@gmail.com', 'John', 'Doe', '$2y$12$Telau4NflNRE33FjQHOpousRzrVkbKHFIB6K0usMs6oDrsspQcWZe', '0948-864-2607', '2025-05-25 10:34:02', '2025-06-12 03:05:12', 'default.png'),
(15, 'freetzel@gmail.com', 'Marc', 'Gonzales', '$2y$10$dy606m5UD4xlapP4NcxwqujmgrZSR0kmII9QJ.NfVOnuJa9EZWuJG', '09488642607', '2025-05-26 01:53:38', '2025-05-26 01:53:38', 'default.png'),
(16, 'mouse@gmail.com', 'TV', 'Five', '$2y$12$h/FgWgKhnNeExToZ221S..de1QpBUlFD.5L6xZuToQ3vbd97PjclO', '09465723981', '2025-05-30 04:47:39', '2025-06-18 04:59:17', 'default.png'),
(17, 'palipate@gmail.com', 'Pal', 'Pitate', '$2y$12$qlHusOXjg3ixU8r1lNWiXO7E2Cs4uH/JPeTb1uWsaca47cwZkNfo.', '09123456789', '2025-06-08 04:14:28', '2025-06-08 04:14:28', 'default.png'),
(18, 'coheredit@gmail.com', 'Admin', 'Admin', '$2y$12$66LAioyIRrUZVaS88oViJOhk7D11tYSJsIc.7LMqxwG/lRvTOHVQW', '09488642607', '2025-06-12 11:01:18', '2025-06-12 11:01:18', 'default.png'),
(19, 'iwantcakex@gmail.com', 'Marc', 'AdminGasta', '$2y$12$vVoHwLJrNudGv9dDe1t.7u5iayJHtFETfUuynp00i2Sfkohg1zgLe', '09488642607', '2025-06-18 19:10:40', '2025-06-18 19:10:40', 'default.png'),
(20, 'marstonjack@gmail.com', 'Jack', 'Marston', '$2y$12$5yjvD4O43umL27Oe3HCtD.d6Ri3UPOxMDpvWUes5Yl8k3GYC03TjC', '01234567890', '2025-06-18 22:15:17', '2025-06-18 22:15:17', 'default.png'),
(21, 'daphne@gmail.com', 'Daph', 'Ney', '$2y$12$tVXxxnnGqc0J2hertPPwzubh6l1EzjkokQToEWRkHrEOWWIWC4Zma', '01234567890', '2025-06-19 00:38:33', '2025-06-19 00:38:33', 'default.png');

-- --------------------------------------------------------

--
-- Table structure for table `availabilities`
--

CREATE TABLE `availabilities` (
  `id` bigint UNSIGNED NOT NULL,
  `date` date NOT NULL,
  `status` enum('Available','Half','Nearly','Full','Closed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Available',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `availabilities`
--

INSERT INTO `availabilities` (`id`, `date`, `status`, `created_at`, `updated_at`) VALUES
(1, '2025-06-06', 'Available', '2025-06-09 01:42:03', '2025-06-09 01:42:03'),
(2, '2025-06-01', 'Available', '2025-06-09 01:42:40', '2025-06-09 01:42:40'),
(3, '2025-06-04', 'Available', '2025-06-09 01:43:15', '2025-06-09 01:43:15'),
(4, '2025-06-02', 'Available', '2025-06-09 02:12:16', '2025-06-09 02:12:16'),
(5, '2025-06-05', 'Available', '2025-06-09 02:13:54', '2025-06-09 02:13:54'),
(6, '2025-06-12', 'Available', '2025-06-09 02:13:58', '2025-06-09 02:18:50'),
(7, '2025-06-18', 'Full', '2025-06-09 02:14:01', '2025-06-17 02:41:39'),
(8, '2025-06-19', 'Available', '2025-06-09 02:14:04', '2025-06-09 02:14:04'),
(9, '2025-06-10', 'Half', '2025-06-09 02:16:44', '2025-06-11 03:45:05'),
(10, '2025-06-09', 'Available', '2025-06-09 02:18:40', '2025-06-09 02:18:40'),
(11, '2025-06-13', 'Available', '2025-06-09 02:19:03', '2025-06-09 02:19:03'),
(12, '2025-06-30', 'Available', '2025-06-09 05:09:57', '2025-06-09 05:09:57'),
(13, '2025-06-28', 'Nearly', '2025-06-09 05:10:01', '2025-06-09 05:10:01'),
(14, '2025-06-22', 'Full', '2025-06-11 05:42:32', '2025-06-11 05:42:32'),
(15, '2025-06-17', 'Full', '2025-06-17 03:10:42', '2025-06-17 03:10:42'),
(16, '2025-06-15', 'Available', '2025-06-25 03:01:05', '2025-06-25 03:01:05');

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint UNSIGNED NOT NULL,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `id` int UNSIGNED NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `feedback`
--

INSERT INTO `feedback` (`id`, `name`, `email`, `message`, `created_at`, `updated_at`) VALUES
(1, 'John Doe', 'doe@gmail.com', 'testing john doe tester', '2025-06-07 11:33:52', '2025-06-07 11:33:52');

-- --------------------------------------------------------

--
-- Table structure for table `inquiry`
--

CREATE TABLE `inquiry` (
  `inquiry_id` int NOT NULL,
  `created_by_type` enum('admin','patron') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'patron',
  `admin_id` int DEFAULT NULL,
  `time` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `patron_id` int DEFAULT NULL,
  `tracking_code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date` date NOT NULL,
  `venue` enum('Villa I','Villa II','Private Pool','Elizabeth Hall','Others') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `event_type` enum('Baptismal Package','Birthday Package','Standard Package','Kiddie Package','Debut Package','Wedding Package','Others') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `theme_motif` enum('Floral','Rustic','Beach','Modern','Elegant','Others') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `other_event_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `other_theme_motif` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `other_venue` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` enum('Pending','In Progress','Completed','Cancelled') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'Pending',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `inquiry`
--

INSERT INTO `inquiry` (`inquiry_id`, `created_by_type`, `admin_id`, `time`, `patron_id`, `tracking_code`, `message`, `created_at`, `date`, `venue`, `event_type`, `theme_motif`, `other_event_type`, `other_theme_motif`, `other_venue`, `status`, `updated_at`) VALUES
(7, 'patron', NULL, '05:06:00', 16, NULL, 'Kumain kana lab?', '2025-03-28 13:01:41', '2025-03-21', 'Villa I', NULL, 'Floral', NULL, NULL, NULL, 'Completed', '2025-06-07 16:47:06'),
(14, 'patron', NULL, '21:04:00', 23, NULL, 'sup', '2025-03-28 13:12:57', '2025-04-05', 'Villa I', NULL, 'Rustic', 'Graduation', 'EY', NULL, 'Completed', '2025-06-07 16:47:06'),
(15, 'patron', NULL, '21:04:00', 24, NULL, 'sup', '2025-03-28 13:13:05', '2025-04-05', 'Villa I', NULL, 'Floral', 'Graduation', 'EY', NULL, 'Pending', '2025-06-07 16:47:06'),
(16, 'patron', NULL, '21:04:00', 25, NULL, 'sup', '2025-03-28 13:13:08', '2025-04-05', 'Villa I', NULL, 'Floral', 'Graduation', 'EY', NULL, 'Pending', '2025-06-07 16:47:06'),
(17, 'patron', NULL, '21:04:00', 26, NULL, 'sup', '2025-03-28 13:13:39', '2025-04-05', 'Villa I', NULL, 'Floral', 'Graduation', 'EY', NULL, 'Completed', '2025-06-07 16:47:06'),
(18, 'patron', NULL, '23:20:00', 32, NULL, 'testing 123', '2025-03-28 13:22:21', '2025-04-10', 'Villa I', 'Baptismal Package', 'Floral', 'grad', 'B', 'House', 'Completed', '2025-06-07 16:47:06'),
(19, 'patron', NULL, '09:00:00', 33, NULL, 'mama mo', '2025-03-28 21:58:28', '2025-03-31', 'Villa I', 'Birthday Package', 'Beach', NULL, NULL, 'bahay ', 'Pending', '2025-06-07 16:47:06'),
(20, 'patron', NULL, '09:00:00', 34, NULL, 'mama mo', '2025-03-28 21:59:35', '2025-03-31', 'Villa I', 'Birthday Package', 'Beach', NULL, NULL, 'bahay ', 'Completed', '2025-06-07 16:47:06'),
(21, 'patron', NULL, '00:39:00', 35, NULL, 'hi', '2025-04-05 04:38:24', '2025-04-11', 'Villa II', 'Birthday Package', 'Floral', NULL, NULL, NULL, 'Pending', '2025-06-07 16:47:06'),
(22, 'patron', NULL, '13:50:00', 36, NULL, 'hhaha', '2025-04-05 04:49:29', '2025-04-06', 'Private Pool', 'Standard Package', 'Modern', NULL, NULL, NULL, 'Pending', '2025-06-07 16:47:06'),
(24, 'patron', NULL, '07:12:00', 38, NULL, 'want to inquiry', '2025-04-05 10:15:15', '2025-04-15', 'Villa I', 'Baptismal Package', 'Floral', NULL, NULL, NULL, 'Completed', '2025-06-07 16:47:06'),
(25, 'patron', NULL, '10:30:00', 39, NULL, 'Minimalistic ', '2025-04-07 16:21:37', '2025-04-16', 'Villa I', 'Kiddie Package', 'Modern', NULL, NULL, 'House', 'In Progress', '2025-06-07 16:47:06'),
(26, 'patron', NULL, '10:30:00', 40, NULL, 'Minimalistic ', '2025-04-07 16:24:42', '2025-04-16', 'Villa I', 'Kiddie Package', 'Modern', NULL, NULL, 'House', 'In Progress', '2025-06-07 16:47:06'),
(27, 'patron', NULL, '19:35:00', 41, NULL, 'minimal', '2025-04-25 18:33:30', '2025-04-24', 'Elizabeth Hall', 'Birthday Package', 'Modern', NULL, NULL, NULL, 'In Progress', '2025-06-07 16:47:06'),
(28, 'patron', NULL, '11:04:00', 42, NULL, 'wala naman na', '2025-05-26 15:20:28', '2025-05-06', 'Villa II', 'Birthday Package', 'Elegant', NULL, NULL, NULL, 'Cancelled', '2025-06-07 16:47:06'),
(29, 'patron', NULL, '23:25:00', 43, NULL, 'none', '2025-05-26 15:22:03', '2031-11-28', 'Elizabeth Hall', 'Baptismal Package', 'Rustic', 'grad', NULL, NULL, 'In Progress', '2025-06-07 16:47:06'),
(30, 'admin', NULL, '00:32:00', 45, NULL, 'lobo', '2025-05-26 15:37:14', '2025-05-01', 'Private Pool', 'Standard Package', 'Beach', NULL, NULL, NULL, 'Pending', '2025-06-07 16:47:06'),
(31, 'patron', NULL, '12:00:00', 46, NULL, 'madaming pera na ibibigay sa kaniya tapos hati kame', '2025-05-26 15:38:49', '2025-08-05', 'Private Pool', 'Birthday Package', 'Elegant', NULL, NULL, NULL, 'Pending', '2025-06-07 16:47:06'),
(32, 'admin', NULL, '08:28:00', 47, NULL, 'Wala naman', '2025-06-05 15:22:51', '2025-06-26', 'Elizabeth Hall', 'Debut Package', 'Rustic', NULL, NULL, NULL, 'Pending', '2025-06-07 16:47:06'),
(33, 'admin', NULL, '17:49:00', 48, NULL, 'all goods', '2025-06-05 15:43:27', '2025-06-24', 'Elizabeth Hall', 'Kiddie Package', 'Elegant', NULL, NULL, NULL, 'Pending', '2025-06-07 16:47:06'),
(34, 'patron', NULL, '10:00:00', 56, NULL, 'Test', '2025-06-07 12:05:56', '2025-06-17', 'Villa I', 'Baptismal Package', 'Floral', NULL, NULL, NULL, 'In Progress', '2025-06-09 00:32:14'),
(35, 'patron', NULL, '10:10:00', 57, NULL, 'Test', '2025-06-07 12:07:01', '2025-06-19', 'Villa II', 'Baptismal Package', 'Rustic', NULL, NULL, NULL, 'In Progress', '2025-06-09 00:31:39'),
(36, 'patron', NULL, '9am–11am', NULL, 'VS-224D44', 'Test', '2025-06-09 08:43:30', '2025-06-20', 'Villa I', 'Baptismal Package', 'Floral', NULL, NULL, NULL, 'Pending', '2025-06-09 08:43:30'),
(37, 'patron', NULL, '10am–12pm', 60, 'VS-8C063B', 'Test', '2025-06-09 10:30:16', '2025-06-28', 'Villa II', 'Debut Package', 'Rustic', NULL, NULL, NULL, 'Completed', '2025-06-12 14:01:51'),
(38, 'patron', NULL, '2pm–4pm', 61, 'VS-BD70A0', 'Test', '2025-06-10 10:49:15', '2025-06-19', 'Elizabeth Hall', 'Debut Package', 'Floral', NULL, NULL, NULL, 'Completed', '2025-06-12 14:02:22'),
(39, 'patron', NULL, '10am–12pm', 62, 'VS-E4BA3F', 'test', '2025-06-10 11:04:14', '2025-06-27', 'Private Pool', 'Baptismal Package', 'Rustic', NULL, NULL, NULL, 'Completed', '2025-06-12 14:02:54'),
(40, 'patron', NULL, '2pm–4pm', 62, 'VS-619B6F', 'testing testing', '2025-06-10 11:05:26', '2025-06-13', 'Private Pool', 'Birthday Package', 'Beach', NULL, NULL, NULL, 'Completed', '2025-06-12 14:06:01'),
(41, 'patron', NULL, '9am–11am', 62, 'VS-21B1A5', 'testing testing 1', '2025-06-10 11:12:50', '2025-06-25', 'Villa I', 'Baptismal Package', 'Elegant', NULL, NULL, NULL, 'Completed', '2025-06-12 14:12:53'),
(42, 'patron', NULL, '2pm–4pm', 62, 'VS-101093', 'testing testing 2', '2025-06-10 11:32:49', '2025-06-13', 'Villa I', 'Birthday Package', 'Rustic', NULL, NULL, NULL, 'Completed', '2025-06-12 14:15:18'),
(43, 'patron', NULL, '9am–11am', 62, 'VS-60ED93', 'testing testing 3', '2025-06-10 11:40:22', '2025-06-14', 'Elizabeth Hall', 'Wedding Package', 'Rustic', NULL, NULL, NULL, 'Completed', '2025-06-12 14:17:40'),
(44, 'patron', NULL, '9am–11am', NULL, 'VS-B1B3CA', 'testing testing 4', '2025-06-10 11:59:55', '2025-07-12', 'Villa I', 'Baptismal Package', 'Floral', NULL, NULL, NULL, 'Pending', '2025-06-10 11:59:55'),
(45, 'patron', 14, '4pm–6pm', NULL, 'VS-23572B', 'testing testing 5', '2025-06-10 12:02:10', '2025-06-26', 'Private Pool', 'Birthday Package', 'Beach', NULL, NULL, NULL, 'Pending', '2025-06-10 12:02:10'),
(46, 'admin', 14, '9am–11am', NULL, 'VS-03CA64', 'testing testing 6', '2025-06-10 12:06:56', '2025-06-20', 'Private Pool', 'Birthday Package', 'Beach', NULL, NULL, NULL, 'Pending', '2025-06-10 12:06:56'),
(47, 'admin', 14, '2pm–4pm', NULL, 'VS-E57444', 'testing 7', '2025-06-11 01:52:30', '2025-06-13', 'Others', 'Others', 'Others', 'test', 'test', 'test', 'Pending', '2025-06-11 01:52:30'),
(48, 'patron', NULL, '2pm–4pm', 66, 'VS-714101', 'testting 8', '2025-06-11 05:07:19', '2025-06-20', 'Others', 'Others', 'Others', 'test', 'test', 'test', 'Completed', '2025-06-12 14:22:41'),
(49, 'patron', NULL, '2pm–4pm', 67, 'VS-D9EFA5', 'testing 8', '2025-06-11 05:09:33', '2025-06-12', 'Villa I', 'Baptismal Package', 'Floral', NULL, NULL, NULL, 'In Progress', '2025-06-11 08:05:20'),
(50, 'patron', NULL, '9am–11am', 68, 'VS-93FEEB', 'testing', '2025-06-11 05:26:49', '2025-06-27', 'Others', 'Others', 'Others', 'test', 'test', 'testting', 'Completed', '2025-06-11 08:04:25'),
(51, 'patron', NULL, '9am–11am', 69, 'VS-6E534D', 'Taru tatu taru', '2025-06-11 22:52:38', '2025-06-25', 'Villa I', 'Baptismal Package', 'Elegant', NULL, NULL, NULL, 'Completed', '2025-06-12 15:27:54'),
(52, 'admin', 18, '3pm–5pm', NULL, 'VS-1A9FE5', 'none', '2025-06-12 11:34:09', '2025-06-20', 'Private Pool', 'Baptismal Package', 'Rustic', NULL, NULL, NULL, 'Pending', '2025-06-12 11:34:09'),
(53, 'admin', 18, '2pm–4pm', 71, 'VS-3D8764', 'None 4', '2025-06-12 11:58:11', '2025-06-18', 'Villa I', 'Baptismal Package', 'Floral', NULL, NULL, NULL, 'Pending', '2025-06-12 11:58:11'),
(54, 'admin', 18, '9am–11am', 72, 'VS-C17EB4', 'None 5', '2025-06-12 11:59:56', '2025-06-23', 'Villa II', 'Birthday Package', 'Rustic', NULL, NULL, NULL, 'Pending', '2025-06-12 11:59:56'),
(55, 'patron', NULL, '9am–11am', 73, 'VS-44A5A2', 'sample other request', '2025-06-16 21:26:12', '2025-06-21', 'Villa II', 'Wedding Package', 'Elegant', NULL, NULL, NULL, 'In Progress', '2025-06-16 21:27:31'),
(56, 'patron', NULL, '9am–1pm', 73, 'VS-39B7FF', 'Sample Other Request', '2025-06-17 00:19:47', '2025-06-17', 'Elizabeth Hall', 'Wedding Package', 'Elegant', NULL, NULL, NULL, 'Pending', '2025-06-17 00:19:47'),
(57, 'patron', NULL, '11am–3pm', 73, 'VS-0D3ECA', 'Other dsadsa', '2025-06-17 00:43:12', '2025-06-13', 'Private Pool', 'Kiddie Package', 'Elegant', NULL, NULL, NULL, 'Pending', '2025-06-17 00:43:12'),
(58, 'patron', NULL, '4pm-8pm', 73, 'VS-C47F2D', 'dsadsas', '2025-06-17 00:43:56', '2025-06-30', 'Villa II', 'Standard Package', 'Rustic', NULL, NULL, NULL, 'Pending', '2025-06-17 00:43:56'),
(59, 'patron', NULL, '9am–1pm', 74, 'VS-6A71A7', 'May I request for additional 10 (ten) tables. Thank you!', '2025-06-17 01:47:34', '2025-06-17', 'Elizabeth Hall', 'Wedding Package', 'Elegant', NULL, NULL, NULL, 'In Progress', '2025-06-17 01:50:54'),
(60, 'patron', NULL, '4pm–6pm', 73, 'VS-3A5970', 'Sample Outpt', '2025-06-17 03:27:15', '2025-06-17', 'Private Pool', 'Kiddie Package', 'Elegant', NULL, NULL, NULL, 'Pending', '2025-06-17 03:27:15'),
(61, 'patron', NULL, '10am–2pm', 73, 'VS-1C903A', 'Request for additional 10 tables and chairs. Thank you!', '2025-06-17 03:40:49', '2025-06-30', 'Private Pool', 'Kiddie Package', 'Beach', NULL, NULL, NULL, 'Pending', '2025-06-17 03:40:49'),
(62, 'patron', NULL, '5pm–9pm', 73, 'VS-46C562', 'Request for additional 10 (ten) tables and chairs. Thank you!', '2025-06-17 03:45:24', '2025-06-30', 'Private Pool', 'Birthday Package', 'Beach', NULL, NULL, NULL, 'In Progress', '2025-06-17 03:54:59'),
(63, 'patron', NULL, '5pm–9pm', 74, 'VS-EC0163', 'Request for RGB light and additional ten (10) tables and 5 chairs each. Thank you!', '2025-06-17 04:29:18', '2025-06-28', 'Elizabeth Hall', 'Debut Package', 'Modern', NULL, NULL, NULL, 'Completed', '2025-06-17 04:31:33'),
(64, 'patron', NULL, '10am–2pm', 75, 'VS-DBD37B', 'Request for additional ten (10) tables and five (5) chairs for each table. Thank you!', '2025-06-18 04:53:01', '2025-06-30', 'Elizabeth Hall', 'Kiddie Package', 'Elegant', NULL, NULL, NULL, 'Pending', '2025-06-18 04:53:01'),
(65, 'patron', NULL, '10am–2pm', 73, 'VS-621573', 'testing request', '2025-06-18 04:55:50', '2025-06-28', 'Private Pool', 'Wedding Package', 'Rustic', NULL, NULL, NULL, 'Pending', '2025-06-18 04:55:50'),
(66, 'patron', NULL, '10am–2pm', 75, 'VS-9198D8', 'Request for additional ten (10) tables and five (5) chairs for each table. Thank you!', '2025-06-18 04:57:29', '2025-06-28', 'Elizabeth Hall', 'Standard Package', 'Beach', NULL, NULL, NULL, 'In Progress', '2025-06-18 05:00:23'),
(67, 'patron', NULL, '9am–1pm', 73, 'VS-84AD78', 'Request for additional ten (10) tables and five (5) chairs for each table. Thank you!', '2025-06-18 05:02:00', '2025-06-27', 'Elizabeth Hall', 'Wedding Package', 'Beach', NULL, NULL, NULL, 'Pending', '2025-06-18 05:02:00'),
(68, 'patron', NULL, '10am–2pm', 74, 'VS-F184F7', 'Request for additional ten (10) tables and five (5) chairs for each table. Thank you!', '2025-06-18 05:04:15', '2025-06-27', 'Private Pool', 'Birthday Package', 'Elegant', NULL, NULL, NULL, 'In Progress', '2025-06-18 05:04:32'),
(69, 'patron', NULL, '2pm–4pm', 76, 'VS-994D73', 'Nothing special', '2025-06-18 19:19:05', '2025-06-25', 'Private Pool', 'Standard Package', 'Beach', NULL, NULL, NULL, 'Pending', '2025-06-18 19:19:05'),
(70, 'patron', NULL, '9am–11am', 77, 'VS-484814', 'More drinks and chairs', '2025-06-18 19:20:52', '2025-09-09', 'Others', 'Others', 'Others', 'Graduation', 'Party', 'Tagaytay', 'Completed', '2025-06-18 19:21:53'),
(71, 'patron', NULL, '9am–1pm', 78, 'VS-44A1F6', 'None', '2025-06-18 22:17:24', '2025-06-26', 'Villa II', 'Debut Package', 'Rustic', NULL, NULL, NULL, 'Completed', '2025-06-18 22:18:28'),
(72, 'patron', NULL, '9am–1pm', 77, 'VS-E14389', 'non (testing)', '2025-06-19 00:40:30', '2025-06-29', 'Villa I', 'Debut Package', 'Floral', NULL, NULL, NULL, 'Pending', '2025-06-19 00:40:30'),
(73, 'patron', NULL, '9am–1pm', 77, 'VS-953D75', 'None', '2025-06-19 02:10:17', '2025-06-29', 'Villa I', 'Baptismal Package', 'Floral', NULL, NULL, NULL, 'Pending', '2025-06-19 02:10:17');

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint UNSIGNED NOT NULL,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint UNSIGNED NOT NULL,
  `reserved_at` int UNSIGNED DEFAULT NULL,
  `available_at` int UNSIGNED NOT NULL,
  `created_at` int UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int UNSIGNED NOT NULL,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1);

-- --------------------------------------------------------

--
-- Table structure for table `packages`
--

CREATE TABLE `packages` (
  `id` int UNSIGNED NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `price` decimal(10,2) NOT NULL,
  `inclusions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `image_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `image_2_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `image_3_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL
) ;

--
-- Dumping data for table `packages`
--

INSERT INTO `packages` (`id`, `name`, `description`, `price`, `inclusions`, `image_path`, `image_2_path`, `image_3_path`, `created_at`, `updated_at`, `deleted_at`) VALUES
(2, 'Wedding Package', 'An elegant setup tailored for weddings with full-service amenities.', 25000.00, '[]', '/images/wedding_package.jpg', '/images/wedding_package2.jpg', '/images/wedding_package3.jpg', '2025-06-11 18:05:32', '2025-06-25 02:56:58', NULL),
(3, 'Debut Package', 'Celebrate 18 in style with stage, lights, and a memorable party setup.', 20000.00, '[\"Stage decor\", \"Host & DJ\", \"LED Wall\"]', '/images/debut_package.jpg', '/images/debut_package2.jpg', '/images/debut_package3.jpg', '2025-06-11 18:05:45', '2025-06-11 18:05:45', NULL),
(4, 'Baptism Package', 'A wholesome and joyful setup for your babys special day.', 15000.00, '[\"Theme decor\", \"Balloon setup\", \"Simple catering\"]', '/images/baptism_package.jpg', '/images/baptism_package2.jpg', '/images/baptism_package3.jpg', '2025-06-11 18:06:35', '2025-06-11 18:06:35', NULL),
(5, 'Kiddie Party Package', 'Fun and playful designs for an unforgettable kids celebration.', 18000.00, '[\"Clown or mascot\", \"Games & prizes\", \"Theme cakes\"]', '/images/kiddie_package.jpg', '/images/kiddie_package2.jpg', '/images/kiddie_package3.jpg', '2025-06-11 18:06:42', '2025-06-11 18:06:42', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `patron`
--

CREATE TABLE `patron` (
  `patron_id` int NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `contact_number` varchar(30) COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `patron`
--

INSERT INTO `patron` (`patron_id`, `email`, `name`, `contact_number`, `created_at`, `updated_at`) VALUES
(5, 'testing@gmail.com', 'tester1', '09488642607', '2025-03-28 12:41:07', '2025-03-28 12:41:07'),
(6, 'test@gmail.com', 'tester1', '09488642607', '2025-03-28 12:45:38', '2025-03-28 12:45:38'),
(7, 'test@gmail.com', 'tester1', '09488642607', '2025-03-28 12:51:31', '2025-03-28 12:51:31'),
(8, 'test@gmail.com', 'tester1', '09488642607', '2025-03-28 12:51:48', '2025-03-28 12:51:48'),
(9, 'test@gmail.com', 'tester1', '09488642607', '2025-03-28 12:52:16', '2025-03-28 12:52:16'),
(10, 'test@gmail.com', 'tester1', '09488642607', '2025-03-28 12:52:18', '2025-03-28 12:52:18'),
(11, 'test@gmail.com', 'tester1', '09488642607', '2025-03-28 12:56:10', '2025-03-28 12:56:10'),
(12, 'test@gmail.com', 'tester1', '09488642607', '2025-03-28 12:59:19', '2025-03-28 12:59:19'),
(13, 'test@gmail.com', 'tester1', '09488642607', '2025-03-28 12:59:22', '2025-03-28 12:59:22'),
(14, 'test@gmail.com', 'tester1', '09488642607', '2025-03-28 13:00:08', '2025-03-28 13:00:08'),
(15, 'test@gmail.com', 'tester1', '09488642607', '2025-03-28 13:01:14', '2025-03-28 13:01:14'),
(16, 'pretzel@gmail.com', 'Macky', '1111111111111', '2025-03-28 13:01:41', '2025-03-28 13:01:41'),
(17, 'testing@gmail.com', 'tester1', '09488642607', '2025-03-28 13:04:22', '2025-03-28 13:04:22'),
(18, 'testing@gmail.com', 'tester1', '09488642607', '2025-03-28 13:06:44', '2025-03-28 13:06:44'),
(19, 'testing@gmail.com', 'tester1', '09488642607', '2025-03-28 13:07:58', '2025-03-28 13:07:58'),
(20, 'testing@gmail.com', 'tester1', '09488642607', '2025-03-28 13:08:27', '2025-03-28 13:08:27'),
(21, 'testing@gmail.com', 'tester1', '09488642607', '2025-03-28 13:08:57', '2025-03-28 13:08:57'),
(22, 'testing@gmail.com', 'tester1', '09488642607', '2025-03-28 13:12:39', '2025-03-28 13:12:39'),
(23, 'testing@gmail.com', 'tester1', '09488642607', '2025-03-28 13:12:57', '2025-03-28 13:12:57'),
(24, 'testing@gmail.com', 'tester1', '09488642607', '2025-03-28 13:13:05', '2025-03-28 13:13:05'),
(25, 'testing@gmail.com', 'tester1', '09488642607', '2025-03-28 13:13:08', '2025-03-28 13:13:08'),
(26, 'testing@gmail.com', 'tester1', '09488642607', '2025-03-28 13:13:39', '2025-03-28 13:13:39'),
(27, 'akosi100@gmail.com', 'tester100', '78787878787887', '2025-03-28 13:14:35', '2025-03-28 13:14:35'),
(28, 'akosi100@gmail.com', 'tester100', '78787878787887', '2025-03-28 13:17:10', '2025-03-28 13:17:10'),
(29, 'akosi100@gmail.com', 'tester100', '78787878787887', '2025-03-28 13:17:15', '2025-03-28 13:17:15'),
(30, 'akosi100@gmail.com', 'tester100', '78787878787887', '2025-03-28 13:17:22', '2025-03-28 13:17:22'),
(31, 'akosi100@gmail.com', 'tester100', '78787878787887', '2025-03-28 13:17:33', '2025-03-28 13:17:33'),
(32, 'akosi100@gmail.com', 'tester100', '78787878787887', '2025-03-28 13:22:21', '2025-03-28 13:22:21'),
(33, 'testing1@gmail.com', 'ange', '09876543211', '2025-03-28 21:58:28', '2025-03-28 21:58:28'),
(34, 'testing1@gmail.com', 'ange', '09876543211', '2025-03-28 21:59:35', '2025-03-28 21:59:35'),
(35, 'gawa@gmail.com', 'gawa', '875635875238', '2025-04-05 04:38:24', '2025-04-05 04:38:24'),
(36, 'rsr@gmail.com', 'ange', '09876543211', '2025-04-05 04:49:29', '2025-04-05 04:49:29'),
(37, 'testing2@gmail.com', 'ange', '09876543211', '2025-04-05 10:11:43', '2025-04-05 10:11:43'),
(38, 'testing2@gmail.com', 'ange', '09876543211', '2025-04-05 10:15:15', '2025-04-05 10:15:15'),
(39, 'gawa@gmail.com', 'Lyka', '09876543211', '2025-04-07 16:21:30', '2025-04-07 16:21:30'),
(40, 'gawa@gmail.com', 'Lyka', '09876543211', '2025-04-07 16:24:40', '2025-04-07 16:24:40'),
(41, 'testing1@gmail.com', 'lyka rsr', '09876543211', '2025-04-25 18:33:29', '2025-04-25 18:33:29'),
(42, 'agoi@gmail.com', 'Agoi', '12312312312', '2025-05-26 15:20:28', '2025-05-26 15:20:28'),
(43, 'dos@gmail.com', 'uno', '12345678912', '2025-05-26 15:22:03', '2025-05-26 15:22:03'),
(44, 'isda@gmail.com', 'a', '78945612301', '2025-05-26 15:31:31', '2025-05-26 15:31:31'),
(45, 'isda@gmail.com', 'a', '78945612301', '2025-05-26 15:37:14', '2025-05-26 15:37:14'),
(46, 'naiinisna@gmail.com', 'naiinis ', '12312312303', '2025-05-26 15:38:49', '2025-05-26 15:38:49'),
(47, 'mamamo@gmail.com', 'Ma', '12345678901', '2025-06-05 15:22:51', '2025-06-05 15:22:51'),
(48, 'papamo@gmail.com', 'pa', '78945612303', '2025-06-05 15:43:27', '2025-06-05 15:43:27'),
(49, 'doe@gmail.com', 'Johny Doe', '09123456789', '2025-06-07 11:55:38', '2025-06-07 11:55:38'),
(50, 'doe@gmail.com', 'Johny Doe', '09123456789', '2025-06-07 11:57:54', '2025-06-07 11:57:54'),
(51, 'doe@gmail.com', 'Johny Doe', '09123456789', '2025-06-07 11:58:00', '2025-06-07 11:58:00'),
(52, 'doe@gmail.com', 'Johny Doe', '09123456789', '2025-06-07 11:59:02', '2025-06-07 11:59:02'),
(53, 'doe@gmail.com', 'Johny Doe', '09123456789', '2025-06-07 12:02:15', '2025-06-07 12:02:15'),
(54, 'doe@gmail.com', 'Johny Doe', '09123456789', '2025-06-07 12:02:46', '2025-06-07 12:02:46'),
(55, 'doe@gmail.com', 'Johny Doe', '09123456789', '2025-06-07 12:04:29', '2025-06-07 12:04:29'),
(56, 'doe@gmail.com', 'Johny Doe', '09123456789', '2025-06-07 12:05:56', '2025-06-07 12:05:56'),
(57, 'doe@gmail.com', 'Johny Doe', '09123456789', '2025-06-07 12:07:01', '2025-06-07 12:07:01'),
(58, 'amorgan@gmail.com', 'Arthur Morgan', '(091) 234-5678', '2025-06-09 16:28:05', '2025-06-09 16:28:05'),
(59, 'mbell@gmail.com', 'Micah Bell', '(091) 234-5678', '2025-06-09 18:17:28', '2025-06-09 18:17:28'),
(60, 'jmarston@gmail.com', 'Jack Marston', '(091) 234-5678', '2025-06-09 18:30:16', '2025-06-09 18:30:16'),
(61, 'lenny@gmail.com', 'Lenny', '(094) 886-4260', '2025-06-10 18:49:15', '2025-06-10 18:49:15'),
(62, 'cold@gmail.com', 'Colm O\'Driscoll', '(094) 745-5874', '2025-06-10 19:04:14', '2025-06-10 19:04:14'),
(63, 'jescuella@gmail.com', 'Javier Escuella', '(321) 651-6156', '2025-06-11 08:35:43', '2025-06-11 08:35:43'),
(64, 'charles@gmail.com', 'Charles', '(689) 674-6546', '2025-06-11 08:49:09', '2025-06-11 08:49:09'),
(65, 'sean@gmail.com', 'Sean', '(879) 841-6515', '2025-06-11 08:57:00', '2025-06-11 08:57:00'),
(66, 'djohn@gmail.com', 'Doe John', '09123456789', '2025-06-11 13:07:19', '2025-06-11 13:07:19'),
(67, 'aarceta@gmail.com', 'Aiah', '(091) 234-5678', '2025-06-11 13:09:33', '2025-06-11 13:09:33'),
(68, 'cvergara@gmail.com', 'Colet', '09488642607', '2025-06-11 13:26:49', '2025-06-11 13:26:49'),
(69, 'mricalde@gmail.com', 'Maloi', '51819819849849', '2025-06-12 06:52:38', '2025-06-12 06:52:38'),
(70, 'nerya@gmail.com', 'Arthur Nery', '(094) 886-4260', '2025-06-12 19:34:09', '2025-06-12 19:34:09'),
(71, 'adie@gmail.com', 'Adie', '(161) 516-1616', '2025-06-12 19:44:05', '2025-06-12 19:58:11'),
(72, 'tmonterde@gmail.com', 'Tj Monterde', '(094) 886-4260', '2025-06-12 19:59:56', '2025-06-12 19:59:56'),
(73, 'pelagiolander68@gmail.com', 'Lander Buelo Pelagio', '09636775414', '2025-06-17 05:26:12', '2025-06-17 05:26:12'),
(74, 'angelicalykarosario@gmail.com', 'Angelica Lyka Rosario', '09099852202', '2025-06-17 09:47:34', '2025-06-17 09:47:34'),
(75, 'marcolivergastagonzales@gmail.com', 'Marc Gasta', '09099852202', '2025-06-18 12:53:01', '2025-06-18 12:53:01'),
(76, 'kz@gmail.com', 'Kz Tandingan', '(161) 516-1616', '2025-06-19 03:19:05', '2025-06-19 03:19:05'),
(77, 'iwantcakex@gmail.com', 'Adie', '(141) 598-4915', '2025-06-19 03:20:52', '2025-06-19 03:20:52'),
(78, 'olivermarcgasta@gmail.com', 'Sean Mcguirre', '09488642607', '2025-06-19 06:17:24', '2025-06-19 06:17:24');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` int NOT NULL,
  `full_name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `payment_type` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `payment_method` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `tracking_code` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `reservation_code` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `receipt_path` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`payment_id`, `full_name`, `payment_type`, `payment_method`, `tracking_code`, `reservation_code`, `receipt_path`, `email`, `created_at`, `updated_at`) VALUES
(1, 'Lander Pelagio', 'full', 'gcash', 'VS-144560-7175', 'VS-44A5A2', 'receipts/I6vY4zKoRkUfnpyY8DR9pa2PirKMga6dJgXvBuAp.png', 'pelagiolander68@gmail.com', '2025-06-16 23:16:00', '2025-06-16 23:16:00'),
(2, 'Lander Pelagio', 'full', 'gcash', 'VS-144700-6670', 'VS-44A5A2', 'receipts/R7cFMNN7HkCou4IxRWMu2cVvFzoLUnohVNnXy0Qw.png', 'pelagiolander68@gmail.com', '2025-06-16 23:18:20', '2025-06-16 23:18:20'),
(3, 'Sean Mcguirre', 'half', 'gcash', 'VS-313950-1253', 'VS-44A1F6', 'receipts/SjT73YWjAIPhDY36wBc5WaqhkwXbJbEM4r3Oe8Nu.png', 'olivermarcgasta@gmail.com', '2025-06-18 22:19:10', '2025-06-18 22:19:10'),
(4, 'daphy', 'full', 'gcash', 'VS-322573-5009', 'VS-E14389', 'receipts/61uxiL4Us5IkoCulmBYwziVNKzbiIoymineNuHej.png', 'iwantcakex@gmail.com', '2025-06-19 00:42:53', '2025-06-19 00:42:53');

-- --------------------------------------------------------

--
-- Table structure for table `reservation`
--

CREATE TABLE `reservation` (
  `reserve_id` int NOT NULL,
  `inquiry_id` int NOT NULL,
  `patron_id` int NOT NULL,
  `admin_id` int DEFAULT NULL,
  `status` enum('active','cancelled','completed') COLLATE utf8mb4_general_ci DEFAULT 'active',
  `date` date DEFAULT NULL,
  `time` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `message` text COLLATE utf8mb4_general_ci,
  `venue` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `event_type` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `theme_motif` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reservation`
--

INSERT INTO `reservation` (`reserve_id`, `inquiry_id`, `patron_id`, `admin_id`, `status`, `date`, `time`, `created_at`, `message`, `venue`, `event_type`, `theme_motif`, `updated_at`) VALUES
(3, 24, 38, 16, 'active', '2025-04-15', '07:12:00', '2025-05-30 05:53:32', 'want to inquiry', 'Villa I', 'Baptismal Package', 'Floral', '2025-06-07 16:55:50'),
(4, 17, 26, 16, 'active', '2025-04-05', '21:04:00', '2025-05-30 05:53:35', 'sup', 'Villa I', NULL, 'Floral', '2025-06-07 16:55:50'),
(5, 20, 34, 16, 'active', '2025-03-31', '09:00:00', '2025-05-30 05:53:37', 'mama mo', 'Villa I', 'Birthday Package', 'Beach', '2025-06-07 16:55:50'),
(6, 7, 16, 16, 'active', '2025-03-21', '05:06:00', '2025-05-30 05:53:38', 'Kumain kana lab?', 'Villa I', NULL, 'Floral', '2025-06-07 16:55:50'),
(7, 18, 32, 16, 'active', '2025-04-10', '23:20:00', '2025-05-30 05:53:40', 'testing 123', 'Villa I', 'Baptismal Package', 'Floral', '2025-06-07 16:55:50'),
(8, 48, 66, NULL, 'active', '2025-06-20', '2pm–4pm', '2025-06-12 22:22:41', 'testting 8', 'Others', 'Others', 'Others', '2025-06-12 22:22:41'),
(9, 51, 69, NULL, 'active', '2025-06-25', '9am–11am', '2025-06-12 23:27:54', 'Taru tatu taru', 'Villa I', 'Baptismal Package', 'Elegant', '2025-06-12 23:27:54'),
(10, 63, 74, NULL, 'active', '2025-06-28', '5pm–9pm', '2025-06-17 12:31:33', 'Request for RGB light and additional ten (10) tables and 5 chairs each. Thank you!', 'Elizabeth Hall', 'Debut Package', 'Modern', '2025-06-17 12:31:33'),
(11, 70, 77, NULL, 'active', '2025-09-09', '9am–11am', '2025-06-19 03:21:53', 'More drinks and chairs', 'Others', 'Others', 'Others', '2025-06-19 03:21:53'),
(12, 71, 78, NULL, 'active', '2025-06-26', '9am–1pm', '2025-06-19 06:18:28', 'None', 'Villa II', 'Debut Package', 'Rustic', '2025-06-19 06:18:28');

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('DCl40C8r3FTTgUKKNTMt5k2XoGRrQWv8rvgxLEnu', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoieFBEcE5lS0Y3V3BoTUt2aEZnTXI0QUVRZWpPTU5jUDdwakpGdnV3VCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjY6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9ob21lIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1765801755),
('uGVXKk5A80geGL1vJgJNiVz9Bro8C0RKeOyKwVqC', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiUDV1QWprTWI2WGhSZUgxZ1MzNU5ma0pmZlBxMVB6cnREMG9CVVRZSSI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMCI7fX0=', 1750850298);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `admin_id` (`admin_id`),
  ADD KEY `activity_type` (`activity_type`),
  ADD KEY `time_created` (`created_at`),
  ADD KEY `inquiry_id` (`inquiry_id`),
  ADD KEY `reserve_id` (`reserve_id`);

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`admin_id`);

--
-- Indexes for table `availabilities`
--
ALTER TABLE `availabilities`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `date` (`date`);

--
-- Indexes for table `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `inquiry`
--
ALTER TABLE `inquiry`
  ADD PRIMARY KEY (`inquiry_id`),
  ADD UNIQUE KEY `tracking_code` (`tracking_code`),
  ADD KEY `fk_inquiry_patron` (`patron_id`),
  ADD KEY `idx_inquiry_creator_type` (`created_by_type`),
  ADD KEY `idx_inquiry_admin_id` (`admin_id`);

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
-- Indexes for table `packages`
--
ALTER TABLE `packages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `patron`
--
ALTER TABLE `patron`
  ADD PRIMARY KEY (`patron_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD UNIQUE KEY `tracking_code` (`tracking_code`);

--
-- Indexes for table `reservation`
--
ALTER TABLE `reservation`
  ADD PRIMARY KEY (`reserve_id`),
  ADD KEY `admin_id` (`admin_id`),
  ADD KEY `fk_res_patron` (`patron_id`),
  ADD KEY `fk_res_inquiry` (`inquiry_id`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `admin_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `availabilities`
--
ALTER TABLE `availabilities`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `inquiry`
--
ALTER TABLE `inquiry`
  MODIFY `inquiry_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=74;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `packages`
--
ALTER TABLE `packages`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `patron`
--
ALTER TABLE `patron`
  MODIFY `patron_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=79;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `reservation`
--
ALTER TABLE `reservation`
  MODIFY `reserve_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `reservation`
--
ALTER TABLE `reservation`
  ADD CONSTRAINT `fk_res_inquiry` FOREIGN KEY (`inquiry_id`) REFERENCES `inquiry` (`inquiry_id`),
  ADD CONSTRAINT `fk_res_patron` FOREIGN KEY (`patron_id`) REFERENCES `patron` (`patron_id`),
  ADD CONSTRAINT `reservation_ibfk_1` FOREIGN KEY (`inquiry_id`) REFERENCES `inquiry` (`inquiry_id`),
  ADD CONSTRAINT `reservation_ibfk_2` FOREIGN KEY (`patron_id`) REFERENCES `patron` (`patron_id`),
  ADD CONSTRAINT `reservation_ibfk_3` FOREIGN KEY (`admin_id`) REFERENCES `admin` (`admin_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
