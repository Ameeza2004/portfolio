-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 24, 2026 at 02:24 AM
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
-- Database: `ipro_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `username`, `password`) VALUES
(1, 'admin', '1234');

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `product_name` varchar(255) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `address` text NOT NULL,
  `total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `payment_method` varchar(50) DEFAULT 'Cash on Delivery',
  `status` enum('Pending','Processing','Delivered','Cancelled') NOT NULL DEFAULT 'Pending',
  `order_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `name`, `phone`, `address`, `total`, `payment_method`, `status`, `order_date`) VALUES
(1, 2, 'john', '0741236784', '81, Mallandha, Nawalapitiya', 9000.00, 'Cash on Delivery', 'Pending', '2026-06-03 08:40:02'),
(2, 2, 'john', '0741236784', '81, Mallandha, Nawalapitiya', 9000.00, 'Cash on Delivery', 'Processing', '2026-06-03 08:47:51'),
(3, 2, 'john', '0741236784', '81, Mallandha, Nawalapitiya', 14300.00, 'Cash on Delivery', 'Delivered', '2026-06-03 09:22:47'),
(4, 2, 'john', '0741236784', '81, Mallandha, Nawalapitiya', 9000.00, 'Cash on Delivery', 'Processing', '2026-06-04 00:37:26'),
(5, 3, 'user', '077 123 4567', '41,Mallandha,Nawalpitiya', 5300.00, 'Cash on Delivery', 'Pending', '2026-07-01 10:55:51'),
(6, 3, 'user', '077 123 4567', '20,Ovita,Nawalapitiya', 10600.00, 'Cash on Delivery', 'Pending', '2026-07-01 11:04:31'),
(7, 4, 'maryam', '0740777019', '14,kahatapitiya, Gampola', 8880.00, 'Cash on Delivery', 'Processing', '2026-07-06 04:32:48'),
(8, NULL, 'maryam', '0740777019', '14,kahatapitiya, Gampola', 2290.00, 'Cash on Delivery', 'Pending', '2026-07-06 14:41:28'),
(9, NULL, 'Mala', '077 123 1874', '13,Jayasundhara,Nawalapitiya', 4290.00, 'Cash on Delivery', 'Pending', '2026-07-06 14:44:45'),
(10, 2, 'john', '0741236784', '81, Mallandha, Nawalapitiya', 4290.00, 'Cash on Delivery', 'Pending', '2026-07-07 04:55:03'),
(11, 2, 'john', '0741236784', '81, Mallandha, Nawalapitiya', 2290.00, 'Card Payment', 'Delivered', '2026-07-07 13:20:13'),
(12, 2, 'john', '0741236784', '81, Mallandha, Nawalapitiya', 5190.00, 'Cash on Delivery', 'Pending', '2026-07-15 15:21:02'),
(13, 5, 'Ameeza', '0783163873', '33,Penithudumulla,Nawalapitiya', 5300.00, 'Card Payment', 'Pending', '2026-07-23 12:54:22'),
(14, 6, 'Noah', '072 567 3456', '13,Mapakandha, Nawalapitiya', 2590.00, 'Bank Transfer', 'Processing', '2026-07-23 13:04:00'),
(15, 6, 'Noah', '072 567 3456', '45,Mallandha,Nawalapitiya', 5190.00, 'Card Payment', 'Pending', '2026-07-23 18:02:54'),
(16, 6, 'Noah', '072 567 3456', '45,Mallandha,Nawalapitiya', 5190.00, 'Card Payment', 'Pending', '2026-07-23 18:03:33'),
(17, 6, 'Noah', '072 567 3456', '86,Mallandha,Nawalapitiya', 5190.00, 'Card Payment', 'Pending', '2026-07-24 00:13:45'),
(18, 6, 'Noah', '072 567 3456', '86,Mallandha,Nawalapitiya', 5190.00, 'Card Payment', 'Processing', '2026-07-24 00:17:20');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `product_name` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `quantity` int(11) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `price`, `quantity`, `subtotal`) VALUES
(1, 1, 5, 'Headphone Pro Max', 5300.00, 1, 5300.00),
(2, 1, 4, 'Earphone Elite', 3700.00, 1, 3700.00),
(3, 2, 5, 'Headphone Pro Max', 5300.00, 1, 5300.00),
(4, 2, 4, 'Earphone Elite', 3700.00, 1, 3700.00),
(5, 3, 4, 'Earphone Elite', 3700.00, 1, 3700.00),
(6, 3, 5, 'Headphone Pro Max', 5300.00, 2, 10600.00),
(7, 4, 5, 'Headphone Pro Max', 5300.00, 1, 5300.00),
(8, 4, 4, 'Earphone Elite', 3700.00, 1, 3700.00),
(9, 5, 5, 'headphone', 5300.00, 1, 5300.00),
(10, 6, 5, 'headphone', 5300.00, 2, 10600.00),
(11, 7, 7, 'Earphone', 3990.00, 1, 3990.00),
(12, 7, 6, 'Headphone', 4890.00, 1, 4890.00),
(13, 7, 3, 'Earphone', 0.00, 3, 0.00),
(14, 8, 3, 'Earphone', 1990.00, 1, 1990.00),
(15, 9, 7, 'Earphone', 3990.00, 1, 3990.00),
(16, 10, 7, 'Earphone', 3990.00, 1, 3990.00),
(17, 11, 3, 'Earphone', 1990.00, 1, 1990.00),
(18, 12, 6, 'Headphone', 4890.00, 1, 4890.00),
(19, 13, 5, 'headphone', 5300.00, 1, 5300.00),
(20, 14, 3, 'Earphone', 2290.00, 1, 2290.00),
(21, 18, 6, 'Headphone', 4890.00, 1, 4890.00);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `category` varchar(100) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `quantity` int(11) NOT NULL,
  `image` varchar(255) NOT NULL,
  `product_status` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `product_name`, `category`, `price`, `quantity`, `image`, `product_status`, `description`, `created_at`) VALUES
(3, 'Earphone', 'Earphone', 2290.00, 6, 'earbud3.jpg.jpeg', 'Back In Stock', 'High-quality wired earphones with clear stereo sound, built-in microphone, 3.5mm audio jack and comfortable ergonomic design for everyday use', '2026-06-03 12:16:44'),
(4, 'Earphone', 'Earphone', 3700.00, 8, 'earbud1.jpg.jpg', 'Available', 'Compact wireless earphones with Bluetooth 5.3, crystal clear sound and a portable charging case', '2026-06-03 12:39:34'),
(5, 'headphone', 'headphone', 5300.00, 7, 'head phone2.jpg.jpeg', '', ' Comfortable gaming headphones with surround sound and built-in microphone ', '2026-06-03 12:50:12'),
(6, 'Headphone', 'headphone', 4890.00, 7, 'prod_6a47952c1d619.jpeg', 'Available', ' Premium wireless headphones with deep bass, noise cancellation and up to 30 hours of battery life', '2026-07-03 10:55:40'),
(7, 'Earphone', 'Earphone', 3990.00, 6, 'prod_6a47958c626f3.jpeg', 'Available', 'Sweat-resistant sports earphones with secure fit, powerful bass and long-lasting battery performance', '2026-07-03 10:57:16'),
(12, 'powerbank', 'Power Bank', 1990.00, 20, 'prod_6a4f71a3ccef1.jpeg', 'Available', 'Power Bank Slim 10000mAh\r\nUltra slim 10000mAh power bank with LED indicator and fast charge support.', '2026-07-09 10:02:11'),
(13, 'powerbank', 'Power Bank', 4850.00, 6, 'prod_6a4f7319165e8.jpeg', 'Available', 'High capacity 20000mAh power bank with fast charging and dual USB ports. Perfect for travel.', '2026-07-09 10:08:25'),
(14, 'SmartWatch', 'smartwatch', 15590.00, 6, 'prod_6a4f76e952bc1.jpeg', 'Available', 'Premium smart watch with heart rate monitor, GPS, sleep tracking and 7-day battery life.', '2026-07-09 10:24:41'),
(15, 'SmartWatch', 'smartwatch', 18990.00, 5, 'prod_6a4f77966f9bc.jpeg', 'Available', 'Fitness smart watch with step counter, calories tracker and waterproof design.', '2026-07-09 10:27:34');

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

CREATE TABLE `sales` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity_sold` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `sale_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `card_holder` varchar(100) DEFAULT NULL,
  `card_number_masked` varchar(20) DEFAULT NULL,
  `card_expiry` varchar(10) DEFAULT NULL,
  `card_type` varchar(20) DEFAULT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `bank_reference` varchar(100) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` varchar(20) DEFAULT 'Completed',
  `transaction_date` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`id`, `order_id`, `payment_method`, `card_holder`, `card_number_masked`, `card_expiry`, `card_type`, `bank_name`, `bank_reference`, `amount`, `status`, `transaction_date`) VALUES
(1, 18, 'Card Payment', 'visa card', '**** **** **** 3221', '02 / 27', 'Amex', NULL, NULL, 5190.00, 'Completed', '2026-07-24 05:47:20');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `role` enum('admin','customer') NOT NULL DEFAULT 'customer',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `phone`, `address`, `role`, `created_at`) VALUES
(1, 'admin', 'admin@techstore.lk', '1234', '0771234567', 'Colombo', 'admin', '2026-06-30 08:26:22'),
(2, 'john', 'john@gmail.com', '1234', '0741236784', '81, Mallandha, Nawalapitiya', 'customer', '2026-06-30 08:26:22'),
(3, 'user', 'user@gmail.com', '1234', '077 123 4567', NULL, 'customer', '2026-06-30 08:27:06'),
(4, 'maryam', 'maryam@gmail.com', 'fama@123', '0740777019', NULL, 'customer', '2026-07-06 04:22:37'),
(5, 'Ameeza', 'ameezaramzen@gmail.com', '123456', '0783163873', NULL, 'customer', '2026-07-09 13:47:41'),
(6, 'Noah', 'noah@gmail.com', 'noah', '072 567 3456', NULL, 'customer', '2026-07-23 13:02:03');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sales`
--
ALTER TABLE `sales`
  ADD CONSTRAINT `sales_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
