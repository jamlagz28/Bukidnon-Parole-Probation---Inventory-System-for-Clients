-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 30, 2026 at 07:25 AM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `inventory_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cases`
--

CREATE TABLE `cases` (
  `id` int(11) NOT NULL,
  `client_id` int(11) DEFAULT NULL,
  `case_number` varchar(50) DEFAULT NULL,
  `status` varchar(20) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `clients`
--

CREATE TABLE `clients` (
  `id` int(11) NOT NULL,
  `docket_number` varchar(50) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `cc_number` varchar(50) DEFAULT NULL,
  `court` varchar(100) DEFAULT NULL,
  `offense` varchar(100) DEFAULT NULL,
  `sentence` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `case_type` varchar(10) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `payment` decimal(10,2) DEFAULT 0.00,
  `pi_case_id` int(11) DEFAULT NULL,
  `ps_case_id` int(11) DEFAULT NULL,
  `case_status` varchar(50) DEFAULT 'Pending',
  `phone_number` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `clients`
--

INSERT INTO `clients` (`id`, `docket_number`, `name`, `cc_number`, `court`, `offense`, `sentence`, `address`, `contact_number`, `status`, `case_type`, `created_at`, `start_date`, `end_date`, `payment`, `pi_case_id`, `ps_case_id`, `case_status`, `phone_number`) VALUES
(1, 'D001', 'Juan dela Cruz', 'CC12345', 'Manolo Fortich Court', 'Theft', '6 months', 'Brgy. Alae', NULL, 'Pending', NULL, '2026-03-14 11:26:23', NULL, NULL, 0.00, NULL, NULL, 'Pending', NULL),
(2, 'D002', 'Maria Santos', 'CC12346', 'Manolo Fortich Court', 'Fraud', '1 year', 'Brgy. Alae', NULL, 'Terminated', NULL, '2026-03-14 11:26:23', NULL, NULL, 0.00, NULL, NULL, 'Pending', NULL),
(3, 'D003', 'Pedro Reyes', 'CC12347', 'Manolo Fortich Court', 'Assault', '2 years', 'Brgy. Poblacion', NULL, 'Revoked', NULL, '2026-03-14 11:26:23', NULL, NULL, 0.00, NULL, NULL, 'Pending', NULL),
(4, 'D004', 'Ana Lim', 'CC12348', 'Manolo Fortich Court', 'Drug Possession', '1 year', 'Brgy. Poblacion', NULL, 'Denied', NULL, '2026-03-14 11:26:23', NULL, NULL, 0.00, NULL, NULL, 'Pending', NULL),
(5, 'D005', 'Jose Ramos', 'CC12349', 'Manolo Fortich Court', 'Robbery', '3 years', 'Brgy. Alae', NULL, 'Pending', NULL, '2026-03-14 11:26:23', NULL, NULL, 0.00, NULL, NULL, 'Pending', NULL),
(6, 'D001', 'Juan dela Cruz', 'CC12345', 'Manolo Fortich Court', 'Theft', '6 months', 'Brgy. Alae', NULL, 'Pending', NULL, '2026-03-14 11:26:23', NULL, NULL, 0.00, NULL, NULL, 'Pending', NULL),
(7, 'D002', 'Maria Santos', 'CC12346', 'Manolo Fortich Court', 'Fraud', '1 year', 'Brgy. Alae', NULL, 'Terminated', NULL, '2026-03-14 11:26:23', NULL, NULL, 0.00, NULL, NULL, 'Pending', NULL),
(8, 'D003', 'Pedro Reyes', 'CC12347', 'Manolo Fortich Court', 'Assault', '2 years', 'Brgy. Poblacion', NULL, 'Revoked', NULL, '2026-03-14 11:26:23', NULL, NULL, 0.00, NULL, NULL, 'Pending', NULL),
(9, 'D004', 'Ana Lim', 'CC12348', 'Manolo Fortich Court', 'Drug Possession', '1 year', 'Brgy. Poblacion', NULL, 'Denied', NULL, '2026-03-14 11:26:23', NULL, NULL, 0.00, NULL, NULL, 'Pending', NULL),
(10, 'D005', 'Jose Ramos', 'CC12349', 'Manolo Fortich Court', 'Robbery', '3 years', 'Brgy. Alae', NULL, 'Terminated', NULL, '2026-03-14 11:26:23', NULL, NULL, 0.00, NULL, NULL, 'Pending', NULL),
(12, 'D006', 'Louella Jane Baslao', NULL, 'Manolo Fortich Court', 'Palaaway', NULL, 'agusan', NULL, 'Pending', NULL, '2026-03-14 11:26:23', NULL, NULL, 0.00, NULL, NULL, 'Pending', NULL),
(13, 'D006', 'zenn', 'wawd', 'Manolo Fortich Court', 'drugs', '10 years', 'agusan', NULL, 'Terminated', NULL, '2026-03-16 03:17:37', '2026-03-16', '2026-03-16', 0.00, NULL, NULL, 'Pending', NULL),
(14, 'D090', 'loue', '12', 'Manolo Fortich Court', 'drugs', '10 years', 'agusan', NULL, 'Pending', NULL, '2026-03-17 06:50:36', '2026-03-17', '2026-03-17', 0.00, NULL, NULL, 'Pending', NULL),
(15, 'dsfds', 'Hello', '5325', 'dgsd', 'gdsg', 'fdsfds', 'sdfsdf', NULL, 'Pending', NULL, '2026-03-17 07:52:40', '1111-11-11', '1111-11-11', 0.00, NULL, NULL, 'Pending', NULL),
(16, 'pi-2026-01-0002', 'sha', '1234-112', 'rtc 11', 'ra 9165', '0-4-0', 'manolo', NULL, 'Active', NULL, '2026-03-17 07:55:53', '0000-00-00', '2222-02-22', 0.00, NULL, 5, 'Pending', NULL),
(20, 'D017', 'stev', '1234-112', 'rtc 11', 'ggg', '0-4-0', 'manolo', NULL, 'Pending', NULL, '2026-03-18 03:32:22', '2026-03-18', '2027-03-18', 0.00, 7, NULL, 'Pending', NULL),
(21, 'DKT - 2026', 'Kristan Rey', '', 'RTC', 'Suyop', '5 years', 'Damilag Manolo Fortich Bukidnon', '09813526273', 'Revoked', NULL, '2026-03-23 23:18:26', NULL, NULL, 0.00, NULL, NULL, 'Pending', NULL),
(22, 'DKT - 2026', 'Kristan Rey', NULL, 'RTC', 'Suyop', NULL, 'Damilag Manolo Fortich Bukidnon', '09813526273', 'Revoked', NULL, '2026-03-23 23:18:55', NULL, NULL, 0.00, NULL, NULL, 'Pending', NULL),
(23, 'DKT - 2026', 'Kristan Rey', NULL, 'RTC', 'Suyop', NULL, 'Damilag Manolo Fortich Bukidnon', '09813526273', 'Revoked', NULL, '2026-03-23 23:20:05', NULL, NULL, 0.00, NULL, NULL, 'Pending', NULL),
(24, 'DKT - 2026', 'Kristan Rey', NULL, 'RTC', 'Suyop', NULL, 'Damilag Manolo Fortich Bukidnon', '09813526273', 'Revoked', NULL, '2026-03-23 23:30:57', NULL, NULL, 0.00, NULL, NULL, 'Pending', NULL),
(25, 'DKT - 2026', 'Kristan Rey', NULL, 'RTC', 'Suyop', NULL, 'Damilag Manolo Fortich Bukidnon', '09813526273', 'Revoked', NULL, '2026-03-23 23:31:17', NULL, NULL, 0.00, NULL, NULL, 'Pending', NULL),
(26, 'DKT - 2026', 'Kristan Rey', NULL, 'RTC', 'Suyop', NULL, 'Damilag Manolo Fortich Bukidnon', '09813526273', 'Revoked', NULL, '2026-03-23 23:36:55', NULL, NULL, 0.00, NULL, NULL, 'Pending', NULL),
(27, 'DKT - 2026', 'Kristan Rey', NULL, 'RTC', 'Suyop', NULL, 'Damilag Manolo Fortich Bukidnon', '09813526273', 'Revoked', NULL, '2026-03-23 23:38:19', NULL, NULL, 0.00, NULL, NULL, 'Pending', NULL),
(28, 'DKT - 2026', 'Kristan Rey', NULL, 'RTC', 'Suyop', NULL, 'Damilag Manolo Fortich Bukidnon', '09813526273', 'Revoked', NULL, '2026-03-23 23:38:38', NULL, NULL, 0.00, NULL, NULL, 'Pending', NULL),
(29, 'DKT - 2026', 'Kristan Rey', NULL, 'RTC', 'Suyop', NULL, 'Damilag Manolo Fortich Bukidnon', '09813526273', 'Revoked', NULL, '2026-03-24 00:03:49', NULL, NULL, 0.00, NULL, NULL, 'Pending', NULL),
(30, 'DKT - 2026', 'Kristan Rey', NULL, 'RTC', 'Suyop', NULL, 'Damilag Manolo Fortich Bukidnon', '09813526273', 'Revoked', NULL, '2026-03-24 00:04:02', NULL, NULL, 0.00, NULL, NULL, 'Pending', NULL),
(31, 'DKT - 2026', 'Kristan Rey', NULL, 'RTC', 'Suyop', NULL, 'Damilag Manolo Fortich Bukidnon', '09813526273', 'Revoked', NULL, '2026-03-24 00:04:14', NULL, NULL, 0.00, NULL, NULL, 'Pending', NULL),
(32, 'DKT - 2026', 'Kristan Rey', NULL, 'RTC', 'Suyop', NULL, 'Damilag Manolo Fortich Bukidnon', '09813526273', 'Revoked', NULL, '2026-03-24 00:04:23', NULL, NULL, 0.00, NULL, NULL, 'Pending', NULL),
(33, 'DKT - 2026', 'Kristan Rey', NULL, 'RTC', 'Suyop', NULL, 'Damilag Manolo Fortich Bukidnon', NULL, 'Revoked', NULL, '2026-03-24 00:05:14', NULL, NULL, 0.00, NULL, NULL, 'Pending', NULL),
(34, 'DKT - 2026', 'Kristan Rey', NULL, 'RTC', 'Suyop', NULL, 'Damilag Manolo Fortich Bukidnon', '09813526273', 'Revoked', NULL, '2026-03-24 00:19:27', NULL, NULL, 0.00, NULL, NULL, 'Pending', NULL),
(35, 'DKT - 2026', 'Kristan Rey', NULL, 'RTC', 'Suyop', NULL, 'Damilag Manolo Fortich Bukidnon', NULL, 'Revoked', NULL, '2026-03-24 00:19:50', NULL, NULL, 0.00, NULL, NULL, 'Pending', NULL),
(36, 'dkt - 2026', 'Jimboy', NULL, 'RTC - manolo', 'asd', NULL, 'Zone 1 Lunocan', NULL, 'Revoked', NULL, '2026-03-25 20:20:21', NULL, NULL, 0.00, NULL, NULL, 'Pending', '09813527364'),
(37, '21', 'nikki', NULL, 'RTC - manolo', 'dwa', NULL, 'San Miguel', NULL, 'Denied', NULL, '2026-03-25 20:22:01', NULL, NULL, 0.00, NULL, NULL, 'Pending', '09813527364'),
(38, '21', 'nikki', NULL, 'RTC - manolo', 'dwa', NULL, 'San Miguel', NULL, 'Denied', NULL, '2026-03-25 20:22:07', NULL, NULL, 0.00, NULL, NULL, 'Pending', '09813527364'),
(39, 'dkt - 2026', 'Ohahay', NULL, 'RTC - manolo', 'asd', NULL, 'Zone 1 Lunocan', NULL, 'Active', NULL, '2026-03-25 20:41:13', NULL, NULL, 0.00, NULL, NULL, 'Pending', '09813527364');

-- --------------------------------------------------------

--
-- Table structure for table `investigation_records`
--

CREATE TABLE `investigation_records` (
  `id` int(11) NOT NULL,
  `case_number` varchar(50) NOT NULL,
  `client_name` varchar(100) NOT NULL,
  `offense` varchar(255) NOT NULL,
  `date_received` date NOT NULL,
  `investigator` varchar(100) NOT NULL,
  `requirements_files` varchar(1000) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(50) DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `investigation_records`
--

INSERT INTO `investigation_records` (`id`, `case_number`, `client_name`, `offense`, `date_received`, `investigator`, `requirements_files`, `created_at`, `status`) VALUES
(5, 'dsdasdasdasdasdsa', 'dasdasdas', 'dasd', '1111-11-11', 'sdada', '1773201426_northern bukidnon state college.jpg', '2026-03-11 03:57:06', 'Pending'),
(6, '1232', 'sad', 'dsada', '1111-11-11', 'sadas', '1773201457_northern bukidnon state college.jpg', '2026-03-11 03:57:37', 'Pending'),
(7, 'asdas', 'dsada', 'adas', '1111-11-11', 'das', '1773201511_ss.JPG', '2026-03-11 03:58:31', 'Pending'),
(8, 'asdas', 'dsada', 'adas', '1111-11-11', 'das', '1773201626_ss.JPG', '2026-03-11 04:00:26', 'Pending'),
(9, 'w', 'dawd', 'daw', '0000-00-00', 'wd', '1773201661_IMG_1675.png', '2026-03-11 04:01:01', 'Pending'),
(10, 'w', 'dawd', 'daw', '0000-00-00', 'wd', '1773201865_IMG_1675.png', '2026-03-11 04:04:25', 'Pending'),
(11, 'w', 'dawd', 'daw', '0000-00-00', 'wd', '1773201866_IMG_1675.png', '2026-03-11 04:04:26', 'Pending'),
(12, 'D007', 'Jolina', 'drugs', '2026-03-16', 'Trisha', '1773628788_download (4).jpg', '2026-03-16 02:39:48', 'Approved');

-- --------------------------------------------------------

--
-- Table structure for table `monthly_reports`
--

CREATE TABLE `monthly_reports` (
  `id` int(11) NOT NULL,
  `probationer_id` int(11) NOT NULL,
  `report_month` int(11) NOT NULL,
  `report_year` int(11) NOT NULL,
  `photo` varchar(255) NOT NULL,
  `uploaded_by` int(11) DEFAULT NULL,
  `upload_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `monthly_reports`
--

INSERT INTO `monthly_reports` (`id`, `probationer_id`, `report_month`, `report_year`, `photo`, `uploaded_by`, `upload_date`) VALUES
(2, 13, 5, 2026, 'report_13_2026_5.jpg', 1, '2026-03-16 04:11:29'),
(3, 5, 11, 2026, 'report_5_2026_11.jpg', 1, '2026-03-16 05:06:42');

-- --------------------------------------------------------

--
-- Table structure for table `pre_investigation`
--

CREATE TABLE `pre_investigation` (
  `id` int(11) NOT NULL,
  `docket_number` varchar(50) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `cc_number` varchar(50) DEFAULT NULL,
  `court` varchar(255) DEFAULT NULL,
  `offense` text DEFAULT NULL,
  `sentence` text DEFAULT NULL,
  `address` text DEFAULT NULL,
  `investigator` varchar(255) DEFAULT NULL,
  `date_filed` date DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected','For Review') DEFAULT 'Pending',
  `remarks` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `client_id` int(11) DEFAULT NULL,
  `converted_to_ps` tinyint(4) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pre_investigation`
--

INSERT INTO `pre_investigation` (`id`, `docket_number`, `name`, `cc_number`, `court`, `offense`, `sentence`, `address`, `investigator`, `date_filed`, `status`, `remarks`, `created_by`, `created_at`, `updated_at`, `client_id`, `converted_to_ps`) VALUES
(7, 'PI-D017', 'stev', '1234-112', 'rtc 11', 'ggg', '0-4-0', 'manolo', 'Trisha', '2026-03-18', 'Pending', '', NULL, '2026-03-18 03:32:22', NULL, 20, 0);

-- --------------------------------------------------------

--
-- Table structure for table `probation_payments`
--

CREATE TABLE `probation_payments` (
  `id` int(11) NOT NULL,
  `probation_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `payment_date` date DEFAULT NULL,
  `receipt_number` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `collected_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `probation_supervision`
--

CREATE TABLE `probation_supervision` (
  `id` int(11) NOT NULL,
  `docket_number` varchar(50) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `offense` text DEFAULT NULL,
  `payment` decimal(10,2) DEFAULT 0.00,
  `address` text DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `supervising_officer` varchar(255) DEFAULT NULL,
  `status` enum('Active','Terminated','Revoked','Completed') DEFAULT 'Active',
  `monthly_fee` decimal(10,2) DEFAULT 500.00,
  `last_payment_date` date DEFAULT NULL,
  `next_payment_date` date DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `client_id` int(11) DEFAULT NULL,
  `source_pi_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `probation_supervision`
--

INSERT INTO `probation_supervision` (`id`, `docket_number`, `name`, `offense`, `payment`, `address`, `start_date`, `end_date`, `supervising_officer`, `status`, `monthly_fee`, `last_payment_date`, `next_payment_date`, `created_by`, `created_at`, `updated_at`, `client_id`, `source_pi_id`) VALUES
(6, 'D0015', 'sha', 'dwwa', 0.01, 'manolo', '2026-03-18', '2026-03-18', 'trisha', 'Active', 500.00, NULL, '2026-04-18', 1, '2026-03-18 03:48:50', NULL, NULL, NULL),
(7, 'D017', 'stev', 'daw', 0.01, 'manolo', '2026-03-18', '2026-03-18', 'trisha', 'Active', 500.00, NULL, '2026-04-18', 1, '2026-03-18 03:56:32', NULL, NULL, NULL),
(8, 'D006', 'stev', 'dwa', 0.01, 'agusan', '2026-03-18', '2026-03-18', 'trisha', 'Active', 500.00, NULL, '2026-04-18', 1, '2026-03-18 07:26:30', NULL, NULL, NULL),
(9, 'PS - 2026 -22', 'zenn', 'drugs', 0.03, 'agusan', '2026-03-26', '2027-03-26', 'Trezha', 'Terminated', 500.00, NULL, '2026-04-26', 1, '2026-03-26 05:58:19', '2026-03-26 05:58:28', 13, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `profiles`
--

CREATE TABLE `profiles` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` varchar(50) DEFAULT NULL,
  `trees_planted` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `id` int(11) NOT NULL,
  `fullname` varchar(100) DEFAULT NULL,
  `username` varchar(50) DEFAULT NULL,
  `password` varchar(50) DEFAULT NULL,
  `role` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff`
--

INSERT INTO `staff` (`id`, `fullname`, `username`, `password`, `role`) VALUES
(1, 'Trezsha Hazarmaveth G. Pablo', 'Admin', 'admin123', 'main'),
(4, 'Administrator', 'admin', 'admin123', 'staff'),
(5, 'stev', 'zenn', 'admin', 'staff');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `cases`
--
ALTER TABLE `cases`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `clients`
--
ALTER TABLE `clients`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `investigation_records`
--
ALTER TABLE `investigation_records`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `monthly_reports`
--
ALTER TABLE `monthly_reports`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_report` (`probationer_id`,`report_month`,`report_year`),
  ADD KEY `uploaded_by` (`uploaded_by`);

--
-- Indexes for table `pre_investigation`
--
ALTER TABLE `pre_investigation`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `docket_number` (`docket_number`);

--
-- Indexes for table `probation_payments`
--
ALTER TABLE `probation_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `probation_id` (`probation_id`);

--
-- Indexes for table `probation_supervision`
--
ALTER TABLE `probation_supervision`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `docket_number` (`docket_number`);

--
-- Indexes for table `profiles`
--
ALTER TABLE `profiles`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cases`
--
ALTER TABLE `cases`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `clients`
--
ALTER TABLE `clients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `investigation_records`
--
ALTER TABLE `investigation_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `monthly_reports`
--
ALTER TABLE `monthly_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `pre_investigation`
--
ALTER TABLE `pre_investigation`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `probation_payments`
--
ALTER TABLE `probation_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `probation_supervision`
--
ALTER TABLE `probation_supervision`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `profiles`
--
ALTER TABLE `profiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `staff` (`id`);

--
-- Constraints for table `monthly_reports`
--
ALTER TABLE `monthly_reports`
  ADD CONSTRAINT `monthly_reports_ibfk_1` FOREIGN KEY (`probationer_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `monthly_reports_ibfk_2` FOREIGN KEY (`uploaded_by`) REFERENCES `staff` (`id`);

--
-- Constraints for table `probation_payments`
--
ALTER TABLE `probation_payments`
  ADD CONSTRAINT `probation_payments_ibfk_1` FOREIGN KEY (`probation_id`) REFERENCES `probation_supervision` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
