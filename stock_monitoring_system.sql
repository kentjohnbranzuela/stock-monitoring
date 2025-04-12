-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 12, 2025 at 05:33 AM
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
-- Database: `stock_monitoring_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `drop_wire_consumption`
--

CREATE TABLE `drop_wire_consumption` (
  `id` int(11) NOT NULL,
  `account_number` varchar(255) NOT NULL,
  `serial_number` varchar(255) NOT NULL,
  `technician_name` varchar(255) NOT NULL,
  `drop_wire_consumed` float NOT NULL,
  `date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `drop_wire_consumption`
--

INSERT INTO `drop_wire_consumption` (`id`, `account_number`, `serial_number`, `technician_name`, `drop_wire_consumed`, `date`) VALUES
(2, '456456456', '64565457', 'SLI', 100, '2025-04-10'),
(3, '456456456', '64565457', 'JUBILAN', 0, '2025-04-10'),
(4, '45656734534636', '34567678345345', 'SLI', 0, '2025-04-10'),
(5, '3478563485534', '34545876', 'SLI', 0, '2025-04-10'),
(9, '456456456', '456575678678', 'SLI', 0, '2025-04-10'),
(10, '456456456', '64565457', 'JUBILAN', 0, '2025-04-10'),
(11, '45656734534636', '34567678345345', 'SLI', 0, '2025-04-10'),
(12, '3478563485534', '34545876', 'SLI', 0, '2025-04-10'),
(16, '456456456', '456575678678', 'SLI', 0, '2025-04-10'),
(17, '456456456', '64565457', 'JUBILAN', 100, '2025-04-10'),
(18, '45656734534636', '34567678345345', 'SLI', 0, '2025-04-10'),
(19, '3478563485534', '34545876', 'SLI', 0, '2025-04-10'),
(23, '456456456', '64565457', 'SLI', 1000, '2025-04-10'),
(24, '456456456', '64565457', 'JUBILAN', 0, '2025-04-10'),
(25, '45656734534636', '34567678345345', 'SLI', 0, '2025-04-10'),
(26, '3478563485534', '34545876', 'SLI', 1500, '2025-04-01'),
(33, '45656734534636', '34567678345345', 'SLI', 100, '2025-04-02'),
(34, '12333211233', '12', 'reymund', 123, '2025-04-12'),
(35, '891345732', 'CCBCE36B9107', 'SLI REYMUND', 190, '2025-04-12');

-- --------------------------------------------------------

--
-- Table structure for table `items`
--

CREATE TABLE `items` (
  `item_code` varchar(20) NOT NULL,
  `description` varchar(255) NOT NULL,
  `category` varchar(50) NOT NULL,
  `current_stock` int(11) NOT NULL DEFAULT 0,
  `min_stock` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `items`
--

INSERT INTO `items` (`item_code`, `description`, `category`, `current_stock`, `min_stock`) VALUES
('10000039', 'BOX, SET TOP; B866 V2; ZTE CABLE', 'Set Top Box', 29, 5),
('10000082', 'ROUTER; GT-AX6000; DUAL BAND GIGABIT', 'Router', 8, 2),
('10000084', 'MODEM; ONT; GPON; F670 & F670L; ZTE', 'Modem', 20, 5),
('10001006', 'MODEM; ONT; GN630V; WIFI 6; SKYWORTH', 'Modem', 17, 4),
('10001011', 'MODEM; ONT; GPON; M2-4050; YOTC - BIDA', 'Modem', 11, 3),
('10001081', 'MODEM; ONT; GPON; GN256V; 2-PORT S2S SKYWORTH', 'Modem', 25, 5),
('10001101', 'MODEM; GPON; EG8041X6; WIFI 6 HUAWEI', 'Modem', 3, 2),
('10001113', 'BOX, SET TOP; B866 V2F; ZTE/NETFLIX', 'Set Top Box', 50, 10),
('30001154', 'ASUS RT-AX82U AX5400', 'Router', 12, 3),
('40000009', 'CLIP, CABLE; 5MM', 'Accessory', 100, 20),
('40000011', 'TIE, CABLE; WRAP; 100MM X 2.5MM (4\')', 'Accessory', 80, 15),
('40000048', 'CLAMP, ANCHOR; S-SHAPE; F17', 'Accessory', 60, 10),
('40000049', 'HOUSE BRACKET; F19 C TYPE HOOK', 'Accessory', 50, 10),
('40000050', 'CLAMP, MID-SPAN; F20 TWO SLOTS HOOK', 'Accessory', 70, 10),
('40000051', 'FOC; DROP CABLE; 2 CORE; FIG8; ORANGE ST', 'Accessory', 39, 5),
('40000052', 'TERMINAL BOX; FTTH (NIU)', 'Accessory', 30, 5),
('40000057', 'CONNECTOR, MECHANICAL; FAST SC; SC/APC', 'Accessory', 200, 50),
('40000059', 'PATCHCORD; OJC; SM SX SC/APC-SC/APC 1.5M', 'Accessory', 150, 30),
('DBL_TAPE', 'DOUBLE SIDED TAPE', 'Tape', 20, 3),
('ELEC_TAPE', 'ELECTRICAL TAPE', 'Tape', 30, 5);

-- --------------------------------------------------------

--
-- Table structure for table `processors`
--

CREATE TABLE `processors` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `date_created` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `processors`
--

INSERT INTO `processors` (`id`, `name`, `is_active`, `date_created`) VALUES
(1, 'SLI LIBETH', 1, '2025-04-05 06:26:19'),
(2, 'SLI SARRAN', 1, '2025-04-05 06:26:19'),
(3, 'SLI TAN', 1, '2025-04-05 06:26:19'),
(4, 'SLI BJORN ALEG', 1, '2025-04-05 06:26:19'),
(5, 'SLI RYAN', 1, '2025-04-05 06:26:19'),
(6, 'SLI JUBILAN', 1, '2025-04-05 06:26:19'),
(7, 'SLR ARSENAS JO', 1, '2025-04-05 06:26:19'),
(8, 'SLR SIMBRANO VALLAR', 1, '2025-04-05 06:26:19'),
(9, 'SLR MACUSI', 1, '2025-04-05 06:26:19'),
(10, 'SLR IPON', 1, '2025-04-05 06:26:19'),
(12, 'SLI REYMUND', 1, '2025-04-12 02:28:39');

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `transaction_id` int(11) NOT NULL,
  `transaction_date` datetime NOT NULL,
  `item_code` varchar(20) NOT NULL,
  `transaction_type` enum('in','out','adjust') NOT NULL,
  `quantity` int(11) NOT NULL,
  `processed_by` varchar(50) NOT NULL,
  `notes` text DEFAULT NULL,
  `serial_number` varchar(50) DEFAULT NULL,
  `account_number` varchar(100) DEFAULT NULL,
  `status` varchar(100) DEFAULT NULL,
  `last_realesby` varchar(100) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`transaction_id`, `transaction_date`, `item_code`, `transaction_type`, `quantity`, `processed_by`, `notes`, `serial_number`, `account_number`, `status`, `last_realesby`, `updated_at`) VALUES
(3, '2025-04-05 09:30:00', '30001154', 'out', 10, 'SLI JUBILAN', 'IKA DUJA NA SIYA NAG KUHA ANI', '456575678678', '456456456', 'PENDING FOR ACTIVATION', 'JEN', '2025-04-08 06:42:23'),
(4, '2025-04-05 09:31:00', '30001154', 'out', 6, 'JUBILAN/SARRAN', 'IKA 3 NANI', '64565457', '456456456', 'DEFECTIVE', 'JEN', '2025-04-08 06:59:52'),
(5, '2025-04-05 09:31:00', '30001154', 'out', 1, 'SLI RYAN', '1', 'SampleSerial', 'SampleAccount', 'ACTIVATED', 'JEN', '2025-04-08 06:59:45'),
(6, '2025-04-05 09:38:00', '10001101', 'out', 5, 'SLI JUBILAN', 'nag kuha gahapon and nakuha pud siya ganina', '34567678345345', '45656734534636', 'DEFECTIVE', 'POLDS', '2025-04-10 01:01:53'),
(7, '2025-04-05 16:08:00', '30001154', 'in', 15, 'SLI LIBETH', '', '54564564354435', '1236567567', 'DEFECTIVE', 'JEN', '2025-04-08 06:37:27'),
(8, '2025-04-05 16:42:00', '30001154', 'out', 1, 'SLR MACUSI', '', '48575443666BC4B1', '312313123', 'INSTALLED / ICS', 'JEN', '2025-04-08 06:34:51'),
(9, '2025-04-05 16:54:00', '10001011', 'out', 1, 'SLI LIBETH', '', '48575443666BC4B1', '12315345646', 'INSTALLED / ICS', 'JEN', '2025-04-08 06:34:25'),
(10, '2025-04-08 14:43:00', '10000039', 'out', 1, 'SLR IPON', '', '48575443666BC4B1', 'y56456456451', 'ACTIVATED', 'JEN', '2025-04-08 07:27:00'),
(11, '2025-04-08 15:01:00', '10000039', 'out', 100, 'SLR IPON', '', '234234234', '34234234', 'ACTIVATED', 'JEN', '2025-04-08 07:42:44'),
(12, '2025-04-08 15:12:00', '40000051', 'out', 1, 'SLI JUBILAN', '', '34545876', '3478563485534', 'DEFECTIVE', 'JEN', '2025-04-10 01:01:42'),
(13, '2025-04-10 15:13:00', '10000039', 'in', 100, 'SLI BJORN ALEG', '', NULL, NULL, NULL, NULL, '2025-04-10 07:13:58'),
(15, '2025-04-12 10:32:00', '10001006', 'out', 1, 'reymund', '', '12', '12333211233', 'ACTIVATED', 'POLDS', '2025-04-12 03:20:17'),
(16, '2025-04-12 11:23:00', '10001101', 'out', 1, 'reymund', '', '4857544377968CB2', '', 'ACTIVATED', '', '2025-04-12 03:27:17'),
(17, '2025-04-12 11:26:00', '10001101', 'out', 1, 'SLI REYMUND', '', 'CCBCE36B9107', '891345732', 'ACTIVATED', 'JEN', '2025-04-12 03:27:40');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` enum('admin','user') DEFAULT 'user'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `full_name`, `role`) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin'),
(2, 'user1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Regular User', 'user');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `drop_wire_consumption`
--
ALTER TABLE `drop_wire_consumption`
  ADD PRIMARY KEY (`id`),
  ADD KEY `account_number` (`account_number`),
  ADD KEY `serial_number` (`serial_number`),
  ADD KEY `technician_name` (`technician_name`);

--
-- Indexes for table `items`
--
ALTER TABLE `items`
  ADD PRIMARY KEY (`item_code`);

--
-- Indexes for table `processors`
--
ALTER TABLE `processors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`transaction_id`),
  ADD KEY `item_code` (`item_code`),
  ADD KEY `idx_account_number` (`account_number`),
  ADD KEY `idx_serial_number` (`serial_number`),
  ADD KEY `idx_processed_by` (`processed_by`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `drop_wire_consumption`
--
ALTER TABLE `drop_wire_consumption`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `processors`
--
ALTER TABLE `processors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `transaction_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `drop_wire_consumption`
--
ALTER TABLE `drop_wire_consumption`
  ADD CONSTRAINT `drop_wire_consumption_ibfk_1` FOREIGN KEY (`account_number`) REFERENCES `transactions` (`account_number`),
  ADD CONSTRAINT `drop_wire_consumption_ibfk_2` FOREIGN KEY (`serial_number`) REFERENCES `transactions` (`serial_number`);

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`item_code`) REFERENCES `items` (`item_code`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
