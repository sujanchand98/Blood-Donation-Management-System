-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 19, 2025 at 06:36 AM
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
-- Database: `blood_donation`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_activity_log`
--

CREATE TABLE `admin_activity_log` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `activity_type` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_activity_log`
--

INSERT INTO `admin_activity_log` (`id`, `admin_id`, `activity_type`, `description`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 1, 'login', 'Admin logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-18 04:02:57');

-- --------------------------------------------------------

--
-- Table structure for table `admin_sessions`
--

CREATE TABLE `admin_sessions` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `session_id` varchar(128) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `login_time` datetime DEFAULT current_timestamp(),
  `last_activity` datetime DEFAULT current_timestamp(),
  `expires_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_sessions`
--

INSERT INTO `admin_sessions` (`id`, `admin_id`, `session_id`, `ip_address`, `user_agent`, `login_time`, `last_activity`, `expires_at`) VALUES
(1, 1, 'ig93g9jpuo0bad7ba5bvbbn6qg', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-18 09:47:57', '2025-11-18 09:47:57', '2025-11-18 13:02:57');

-- --------------------------------------------------------

--
-- Table structure for table `admin_users`
--

CREATE TABLE `admin_users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role` enum('super_admin','admin','moderator') DEFAULT 'admin',
  `status` enum('active','inactive','suspended') DEFAULT 'active',
  `last_login` datetime DEFAULT NULL,
  `login_attempts` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_users`
--

INSERT INTO `admin_users` (`id`, `username`, `password`, `full_name`, `email`, `role`, `status`, `last_login`, `login_attempts`, `created_at`, `updated_at`) VALUES
(1, 'admin', '$2y$10$190SyOHvzGKVOy6ZPxi0cuisFAsfqyA8P.dUk3w/EzTKx86Xzkw5C', 'System Administrator', 'admin@bloodbank.com', 'super_admin', 'active', '2025-11-18 09:47:57', 0, '2025-11-18 04:02:14', '2025-11-18 04:02:57');

-- --------------------------------------------------------

--
-- Table structure for table `blood_inventory`
--

CREATE TABLE `blood_inventory` (
  `id` int(11) NOT NULL,
  `blood_group` enum('A+','A-','B+','B-','AB+','AB-','O+','O-') NOT NULL,
  `quantity_ml` int(11) DEFAULT 0,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `blood_inventory`
--

INSERT INTO `blood_inventory` (`id`, `blood_group`, `quantity_ml`, `last_updated`) VALUES
(1, 'A+', 700, '2025-11-18 06:45:48'),
(2, 'A-', 0, '2025-11-18 03:51:52'),
(3, 'B+', 0, '2025-11-18 03:51:52'),
(4, 'B-', 0, '2025-11-18 03:51:52'),
(5, 'AB+', 0, '2025-11-18 03:51:52'),
(6, 'AB-', 0, '2025-11-18 03:51:52'),
(7, 'O+', 0, '2025-11-18 03:51:52'),
(8, 'O-', 0, '2025-11-18 03:51:52');

-- --------------------------------------------------------

--
-- Table structure for table `blood_requests`
--

CREATE TABLE `blood_requests` (
  `id` int(11) NOT NULL,
  `seeker_id` int(11) DEFAULT NULL,
  `blood_group` enum('A+','A-','B+','B-','AB+','AB-','O+','O-') NOT NULL,
  `quantity_ml` int(11) NOT NULL,
  `urgency_level` enum('low','medium','high','critical') NOT NULL,
  `hospital_name` varchar(255) DEFAULT NULL,
  `patient_name` varchar(100) DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('pending','approved','rejected','fulfilled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `blood_requests`
--

INSERT INTO `blood_requests` (`id`, `seeker_id`, `blood_group`, `quantity_ml`, `urgency_level`, `hospital_name`, `patient_name`, `reason`, `status`, `created_at`) VALUES
(2, 4, 'A-', 450, 'medium', 'Town Medical Center', 'Sarah Johnson', 'Regular transfusion', 'approved', '2025-11-18 03:51:52'),
(3, 5, 'O-', 500, 'medium', 'Civil', 'mukta', 'emergency', 'approved', '2025-11-18 16:21:30'),
(4, 5, 'AB-', 450, 'medium', 'Civil', 'mukta', 'emergency', 'pending', '2025-11-18 16:55:27'),
(5, 5, 'A+', 450, 'medium', 'Blakon', 'Amir', 'emergency', 'pending', '2025-11-18 17:13:18');

-- --------------------------------------------------------

--
-- Table structure for table `donations`
--

CREATE TABLE `donations` (
  `id` int(11) NOT NULL,
  `donor_id` int(11) DEFAULT NULL,
  `blood_group` enum('A+','A-','B+','B-','AB+','AB-','O+','O-') NOT NULL,
  `donation_date` date NOT NULL,
  `quantity_ml` int(11) NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `donation_type` varchar(50) NOT NULL DEFAULT 'whole_blood',
  `donation_center` varchar(255) DEFAULT NULL,
  `health_conditions` text DEFAULT NULL,
  `medications` text DEFAULT NULL,
  `travel_history` text DEFAULT NULL,
  `recent_sickness` enum('yes','no') DEFAULT 'no',
  `tattoo_piercing` enum('yes','no') DEFAULT 'no'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `donations`
--

INSERT INTO `donations` (`id`, `donor_id`, `blood_group`, `donation_date`, `quantity_ml`, `status`, `created_at`, `donation_type`, `donation_center`, `health_conditions`, `medications`, `travel_history`, `recent_sickness`, `tattoo_piercing`) VALUES
(1, 2, 'O+', '2024-01-15', 450, 'approved', '2025-11-18 03:51:52', 'whole_blood', NULL, NULL, NULL, NULL, 'no', 'no'),
(2, 2, 'O+', '2024-03-20', 450, 'approved', '2025-11-18 03:51:52', 'whole_blood', NULL, NULL, NULL, NULL, 'no', 'no'),
(3, 3, 'A-', '2024-02-10', 450, 'approved', '2025-11-18 03:51:52', 'whole_blood', NULL, NULL, NULL, NULL, 'no', 'no'),
(4, 6, 'A+', '2025-11-18', 450, 'approved', '2025-11-18 06:21:10', 'whole_blood', NULL, 'good', 'no', 'no', 'no', 'no');

-- --------------------------------------------------------

--
-- Table structure for table `donation_centers`
--

CREATE TABLE `donation_centers` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `operating_hours` text DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','donor','seeker') NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `blood_group` enum('A+','A-','B+','B-','AB+','AB-','O+','O-') DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `full_name`, `blood_group`, `date_of_birth`, `phone`, `address`, `status`, `created_at`) VALUES
(1, 'admin', 'admin@bloodbank.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'System Administrator', NULL, NULL, NULL, NULL, 'active', '2025-11-18 03:51:52'),
(2, 'donor1', 'donor1@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'donor', 'John Doe', 'O+', NULL, '1234567890', '123 Main St, City', 'active', '2025-11-18 03:51:52'),
(3, 'donor2', 'donor2@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'donor', 'Jane Smith', 'A-', NULL, '1234567891', '456 Oak St, Town', 'active', '2025-11-18 03:51:52'),
(4, 'seeker1', 'seeker1@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'seeker', 'Hospital Admin', 'AB+', NULL, '1234567892', '789 Pine St, Village', 'active', '2025-11-18 03:51:52'),
(5, 'sujanchand', 'sujan@gmail.com', '$2y$10$LwntEdRYouL29ktjyI8fQekuhY5xaM.26x8UsOGXJwY9V0REXszSa', 'seeker', 'Sujan Chand', 'A+', NULL, '9766270290', 'kathmandu', 'active', '2025-11-18 03:54:45'),
(6, 'muktapariyar', 'mukta@gmail.com', '$2y$10$zQ98VHhdzQQaKFtaZv2F7.0nwtsxHt.I11DssPgfbYvLiUO/BvIxC', 'donor', 'Mukta Pariyar', 'A+', '2001-06-12', '9862486394', 'Kathmandu', 'active', '2025-11-18 05:42:06');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_activity_log`
--
ALTER TABLE `admin_activity_log`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `admin_sessions`
--
ALTER TABLE `admin_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `session_id` (`session_id`);

--
-- Indexes for table `admin_users`
--
ALTER TABLE `admin_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `blood_inventory`
--
ALTER TABLE `blood_inventory`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `blood_group` (`blood_group`);

--
-- Indexes for table `blood_requests`
--
ALTER TABLE `blood_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `seeker_id` (`seeker_id`);

--
-- Indexes for table `donations`
--
ALTER TABLE `donations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `donor_id` (`donor_id`);

--
-- Indexes for table `donation_centers`
--
ALTER TABLE `donation_centers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_activity_log`
--
ALTER TABLE `admin_activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `admin_sessions`
--
ALTER TABLE `admin_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `blood_inventory`
--
ALTER TABLE `blood_inventory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `blood_requests`
--
ALTER TABLE `blood_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `donations`
--
ALTER TABLE `donations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `donation_centers`
--
ALTER TABLE `donation_centers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `blood_requests`
--
ALTER TABLE `blood_requests`
  ADD CONSTRAINT `blood_requests_ibfk_1` FOREIGN KEY (`seeker_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `donations`
--
ALTER TABLE `donations`
  ADD CONSTRAINT `donations_ibfk_1` FOREIGN KEY (`donor_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
