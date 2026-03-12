-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 12, 2026 at 04:59 AM
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
  `status` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `clients`
--

INSERT INTO `clients` (`id`, `docket_number`, `name`, `cc_number`, `court`, `offense`, `sentence`, `address`, `status`) VALUES
(1, 'D001', 'Juan dela Cruz', 'CC12345', 'Manolo Fortich Court', 'Theft', '6 months', 'Brgy. Alae', 'Active'),
(2, 'D002', 'Maria Santos', 'CC12346', 'Manolo Fortich Court', 'Fraud', '1 year', 'Brgy. Alae', 'Terminated'),
(3, 'D003', 'Pedro Reyes', 'CC12347', 'Manolo Fortich Court', 'Assault', '2 years', 'Brgy. Poblacion', 'Revoked'),
(4, 'D004', 'Ana Lim', 'CC12348', 'Manolo Fortich Court', 'Drug Possession', '1 year', 'Brgy. Poblacion', 'Denied'),
(5, 'D005', 'Jose Ramos', 'CC12349', 'Manolo Fortich Court', 'Robbery', '3 years', 'Brgy. Alae', 'Active'),
(6, 'D001', 'Juan dela Cruz', 'CC12345', 'Manolo Fortich Court', 'Theft', '6 months', 'Brgy. Alae', 'Active'),
(7, 'D002', 'Maria Santos', 'CC12346', 'Manolo Fortich Court', 'Fraud', '1 year', 'Brgy. Alae', 'Terminated'),
(8, 'D003', 'Pedro Reyes', 'CC12347', 'Manolo Fortich Court', 'Assault', '2 years', 'Brgy. Poblacion', 'Revoked'),
(9, 'D004', 'Ana Lim', 'CC12348', 'Manolo Fortich Court', 'Drug Possession', '1 year', 'Brgy. Poblacion', 'Denied'),
(10, 'D005', 'Jose Ramos', 'CC12349', 'Manolo Fortich Court', 'Robbery', '3 years', 'Brgy. Alae', 'Terminated'),
(11, 'D005', 'Louella Jane Baslao', NULL, 'Manolo Fortich Court', 'Palaaway', NULL, 'agusan', 'Terminated');

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `investigation_records`
--

INSERT INTO `investigation_records` (`id`, `case_number`, `client_name`, `offense`, `date_received`, `investigator`, `requirements_files`, `created_at`) VALUES
(5, 'dsdasdasdasdasdsa', 'dasdasdas', 'dasd', '1111-11-11', 'sdada', '1773201426_northern bukidnon state college.jpg', '2026-03-11 03:57:06'),
(6, '1232', 'sad', 'dsada', '1111-11-11', 'sadas', '1773201457_northern bukidnon state college.jpg', '2026-03-11 03:57:37'),
(7, 'asdas', 'dsada', 'adas', '1111-11-11', 'das', '1773201511_ss.JPG', '2026-03-11 03:58:31'),
(8, 'asdas', 'dsada', 'adas', '1111-11-11', 'das', '1773201626_ss.JPG', '2026-03-11 04:00:26'),
(9, 'w', 'dawd', 'daw', '0000-00-00', 'wd', '1773201661_IMG_1675.png', '2026-03-11 04:01:01'),
(10, 'w', 'dawd', 'daw', '0000-00-00', 'wd', '1773201865_IMG_1675.png', '2026-03-11 04:04:25'),
(11, 'w', 'dawd', 'daw', '0000-00-00', 'wd', '1773201866_IMG_1675.png', '2026-03-11 04:04:26');

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
(2, 'Administrator', 'admin', 'admin123', NULL);

--
-- Indexes for dumped tables
--

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
-- AUTO_INCREMENT for table `cases`
--
ALTER TABLE `cases`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `clients`
--
ALTER TABLE `clients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `investigation_records`
--
ALTER TABLE `investigation_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `profiles`
--
ALTER TABLE `profiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
