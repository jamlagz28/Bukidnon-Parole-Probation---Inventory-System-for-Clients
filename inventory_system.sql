-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 16, 2026 at 04:55 AM
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
  `status` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `payment` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `clients`
--

INSERT INTO `clients` (`id`, `docket_number`, `name`, `cc_number`, `court`, `offense`, `sentence`, `address`, `status`, `created_at`, `start_date`, `end_date`, `payment`) VALUES
(1, 'D001', 'Juan dela Cruz', 'CC12345', 'Manolo Fortich Court', 'Theft', '6 months', 'Brgy. Alae', 'Active', '2026-03-14 11:26:23', NULL, NULL, 0.00),
(2, 'D002', 'Maria Santos', 'CC12346', 'Manolo Fortich Court', 'Fraud', '1 year', 'Brgy. Alae', 'Terminated', '2026-03-14 11:26:23', NULL, NULL, 0.00),
(3, 'D003', 'Pedro Reyes', 'CC12347', 'Manolo Fortich Court', 'Assault', '2 years', 'Brgy. Poblacion', 'Revoked', '2026-03-14 11:26:23', NULL, NULL, 0.00),
(4, 'D004', 'Ana Lim', 'CC12348', 'Manolo Fortich Court', 'Drug Possession', '1 year', 'Brgy. Poblacion', 'Denied', '2026-03-14 11:26:23', NULL, NULL, 0.00),
(5, 'D005', 'Jose Ramos', 'CC12349', 'Manolo Fortich Court', 'Robbery', '3 years', 'Brgy. Alae', 'Active', '2026-03-14 11:26:23', NULL, NULL, 0.00),
(6, 'D001', 'Juan dela Cruz', 'CC12345', 'Manolo Fortich Court', 'Theft', '6 months', 'Brgy. Alae', 'Active', '2026-03-14 11:26:23', NULL, NULL, 0.00),
(7, 'D002', 'Maria Santos', 'CC12346', 'Manolo Fortich Court', 'Fraud', '1 year', 'Brgy. Alae', 'Terminated', '2026-03-14 11:26:23', NULL, NULL, 0.00),
(8, 'D003', 'Pedro Reyes', 'CC12347', 'Manolo Fortich Court', 'Assault', '2 years', 'Brgy. Poblacion', 'Revoked', '2026-03-14 11:26:23', NULL, NULL, 0.00),
(9, 'D004', 'Ana Lim', 'CC12348', 'Manolo Fortich Court', 'Drug Possession', '1 year', 'Brgy. Poblacion', 'Denied', '2026-03-14 11:26:23', NULL, NULL, 0.00),
(10, 'D005', 'Jose Ramos', 'CC12349', 'Manolo Fortich Court', 'Robbery', '3 years', 'Brgy. Alae', 'Terminated', '2026-03-14 11:26:23', NULL, NULL, 0.00),
(12, 'D006', 'Louella Jane Baslao', NULL, 'Manolo Fortich Court', 'Palaaway', NULL, 'agusan', 'Active', '2026-03-14 11:26:23', NULL, NULL, 0.00),
(13, 'D006', 'zenn', 'wawd', 'Manolo Fortich Court', 'drugs', '10 years', 'agusan', 'Active', '2026-03-16 03:17:37', '2026-03-16', '2026-03-16', 0.00);

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
(1, 5, 12, 2026, 'report_5_2026_12.jpg', 1, '2026-03-16 03:23:03');

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
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(2, 'Administrator', 'admin', 'admin123', NULL),
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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `investigation_records`
--
ALTER TABLE `investigation_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `monthly_reports`
--
ALTER TABLE `monthly_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `pre_investigation`
--
ALTER TABLE `pre_investigation`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `probation_payments`
--
ALTER TABLE `probation_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `probation_supervision`
--
ALTER TABLE `probation_supervision`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

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
