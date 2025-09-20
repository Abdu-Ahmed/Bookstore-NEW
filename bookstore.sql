-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 20, 2025 at 04:31 PM
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
-- Database: `bookstore`
--

-- --------------------------------------------------------

--
-- Table structure for table `books`
--

CREATE TABLE `books` (
  `book_id` int(11) UNSIGNED NOT NULL,
  `book_title` varchar(255) DEFAULT NULL,
  `book_description` text DEFAULT NULL,
  `book_author` varchar(255) DEFAULT NULL,
  `book_price` decimal(10,2) DEFAULT 0.00,
  `book_genre` varchar(255) DEFAULT NULL,
  `book_image` varchar(1024) DEFAULT NULL,
  `status` varchar(100) DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `books`
--

INSERT INTO `books` (`book_id`, `book_title`, `book_description`, `book_author`, `book_price`, `book_genre`, `book_image`, `status`, `created_at`, `updated_at`) VALUES
(5, 'Normal People', 'The author of conversations with friends comes out with another banger', 'Sally Rooney', 332.00, 'Action', 'https://cdn.vox-cdn.com/thumbor/p-gGrwlaU4rLikEAgYhupMUhIJc=/0x0:1650x2475/1200x0/filters:focal(0x0:1650x2475):no_upscale()/cdn.vox-cdn.com/uploads/chorus_asset/file/13757614/817BsplxI9L.jpg', '', '2024-01-13 06:36:57', NULL),
(6, 'Harry Potter and the deathly harrows', 'harry potter faces his greatest enemy so far, will he finally defeat him?', 'J.K. Rowling', 243.00, 'Fiction', 'https://hips.hearstapps.com/digitalspyuk.cdnds.net/15/50/1449878132-9781781100264.jpg?resize=980:*', '', '2024-07-04 11:39:18', NULL),
(7, 'The Right Swipe', 'a modern day romance story, with modern twists', 'Alisha Rai', 123.00, 'Romance', 'https://m.media-amazon.com/images/I/71LwgxyLFKL._AC_UF894,1000_QL80_.jpg', '', '2024-01-13 06:40:52', NULL),
(8, 'To Kill a Mockingbird', 'A novel about the serious issues of rape and racial inequality.', 'Harper Lee', 11.00, 'Fiction', 'https://m.media-amazon.com/images/I/81gepf1eMqL._AC_UF894,1000_QL80_.jpg', '', '2024-06-26 10:04:52', NULL),
(9, '1984', 'A dystopian social science fiction novel and cautionary tale about the dangers of totalitarianism.', 'George Orwell', 9.00, 'Science Fiction', 'https://m.media-amazon.com/images/I/61ZewDE3beL._AC_UF894,1000_QL80_.jpg', '', '2024-06-26 10:04:52', NULL),
(10, 'The Great Gatsby', 'A novel about the American dream and the roaring twenties.', 'F. Scott Fitzgerald', 10.00, 'Classic', 'https://m.media-amazon.com/images/I/81QuEGw8VPL._AC_UF894,1000_QL80_.jpg', '', '2024-06-26 10:04:52', NULL),
(11, 'The Catcher in the Rye', 'A story about teenage rebellion and angst.', 'J.D. Salinger', 13.00, 'Fiction', 'https://m.media-amazon.com/images/I/91fQEUwFMyL._AC_UF894,1000_QL80_.jpg', '', '2024-06-26 10:04:52', NULL),
(12, 'The Hobbit', 'A fantasy novel and children\'s book about the adventures of Bilbo Baggins.', 'J.R.R. Tolkien', 15.00, 'Fantasy', 'https://m.media-amazon.com/images/I/71k--OLmZKL._AC_UF894,1000_QL80_.jpg', '', '2024-06-26 10:04:52', NULL),
(13, 'Pride and Prejudice', 'A romantic novel that also critiques the British landed gentry at the end of the 18th century.', 'Jane Austen', 12.00, 'Romance', 'https://m.media-amazon.com/images/I/5176rSnUxfL.jpg', '', '2024-06-26 10:04:52', NULL),
(14, 'Harry Potter and the Sorcerer\'s Stone', 'The first book in the Harry Potter series about a young wizard and his adventures at Hogwarts.', 'J.K. Rowling', 14.00, 'Fantasy', 'https://m.media-amazon.com/images/I/91wKDODkgWL._AC_UF894,1000_QL80_.jpg', '', '2024-06-26 10:04:52', NULL),
(15, 'The Lord of the Rings', 'An epic high-fantasy novel about the quest to destroy the One Ring.', 'J.R.R. Tolkien', 20.00, 'Fantasy', 'https://m.media-amazon.com/images/I/81nV6x2ey4L._AC_UF894,1000_QL80_.jpg', '', '2024-06-26 10:04:52', NULL),
(16, 'Animal Farm', 'A satirical allegorical novella that reflects events leading up to the Russian Revolution of 1917.', 'George Orwell', 8.00, 'Political Satire', 'https://m.media-amazon.com/images/I/91Lbhwt5RzL._AC_UF894,1000_QL80_.jpg', '', '2024-06-26 10:04:52', NULL),
(17, 'The Chronicles of Narnia', 'A series of fantasy novels about the adventures in the magical land of Narnia.', 'C.S. Lewis', 16.00, 'Fantasy', 'https://m.media-amazon.com/images/I/81RuC2qCSmL._AC_UF894,1000_QL80_.jpg', '', '2024-06-26 10:04:52', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `cart_items`
--

CREATE TABLE `cart_items` (
  `cart_item_id` int(11) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cart_items`
--

INSERT INTO `cart_items` (`cart_item_id`, `user_id`, `book_id`, `quantity`, `created_at`) VALUES
(1, 1, 17, 1, '2025-09-07 15:45:53'),
(3, 1, 16, 1, '2025-09-10 15:14:47'),
(4, 11, 14, 2, '2025-09-13 14:26:34'),
(5, 13, 14, 2, '2025-09-13 14:29:34'),
(6, 15, 14, 2, '2025-09-13 14:35:47'),
(7, 17, 14, 2, '2025-09-13 14:38:02');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` int(11) UNSIGNED NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `session_id` varchar(128) NOT NULL,
  `amount` int(11) NOT NULL COMMENT 'total in cents',
  `currency` varchar(10) NOT NULL DEFAULT 'usd',
  `status` varchar(50) NOT NULL DEFAULT 'pending',
  `metadata` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `order_item_id` int(11) UNSIGNED NOT NULL,
  `order_id` int(11) NOT NULL,
  `book_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `unit_price` int(11) NOT NULL COMMENT 'price in cents',
  `quantity` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `phinxlog`
--

CREATE TABLE `phinxlog` (
  `version` bigint(20) NOT NULL,
  `migration_name` varchar(100) DEFAULT NULL,
  `start_time` timestamp NULL DEFAULT NULL,
  `end_time` timestamp NULL DEFAULT NULL,
  `breakpoint` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `phinxlog`
--

INSERT INTO `phinxlog` (`version`, `migration_name`, `start_time`, `end_time`, `breakpoint`) VALUES
(20250901120000, 'CreateUsersTable', '2025-09-02 11:36:43', '2025-09-02 11:36:44', 0),
(20250901120001, 'CreateRefreshTokensTable', '2025-09-02 11:36:44', '2025-09-02 11:36:44', 0),
(20250904103001, 'CreateRatingsTable', '2025-09-04 10:34:17', '2025-09-04 10:34:17', 0),
(20250904120500, 'CreateBooksTable', '2025-09-04 10:27:01', '2025-09-04 10:27:01', 0),
(20250904140000, 'SafeAddIndexesBooks', '2025-09-04 10:34:17', '2025-09-04 10:34:18', 0),
(20250905120000, 'CreateCartItems', '2025-09-06 10:22:51', '2025-09-06 10:22:52', 0),
(20250910120000, 'CreateOrders', '2025-09-09 11:17:53', '2025-09-09 11:17:54', 0);

-- --------------------------------------------------------

--
-- Table structure for table `ratings`
--

CREATE TABLE `ratings` (
  `id` int(11) UNSIGNED NOT NULL,
  `book_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `rating` int(1) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `refresh_tokens`
--

CREATE TABLE `refresh_tokens` (
  `id` int(11) UNSIGNED NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `token_hash` varchar(128) DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `revoked` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `refresh_tokens`
--

INSERT INTO `refresh_tokens` (`id`, `user_id`, `token_hash`, `expires_at`, `revoked`, `created_at`) VALUES
(1, 1, 'cfd48045dbc73acf4f471f355ad8dea739f9febc2d5928a3b40c43ebba6d7ecc', '2025-10-06 15:17:20', 1, '2025-09-06 13:17:20'),
(2, 1, 'a5ae3c5b085bf47168924838cd27d3195f136b8992b3e854a94e39da13915c2a', '2025-10-07 16:49:27', 0, '2025-09-07 14:49:27'),
(3, 1, '02c749116fb22083eac805eed6b930feec39f67e1d44dbab7c2f6cd3a31476da', '2025-10-07 16:59:21', 0, '2025-09-07 14:59:21'),
(4, 1, 'd15b1020c69bc7d9498ae21f74072f89afdd7624eef3e501bf5c25356c4ea17b', '2025-10-07 17:00:54', 0, '2025-09-07 15:00:54'),
(5, 1, 'af4d53f733b12aa8692028b5f1fd58776fae5e63e7605c2d505dfc7f7891250e', '2025-10-07 17:16:50', 0, '2025-09-07 15:16:50'),
(6, 1, 'd9586e22357e0f28dc38a3e18e71149477d1d877fe08744aba7e9518693f89a5', '2025-10-07 17:21:05', 0, '2025-09-07 15:21:05'),
(7, 1, 'ce935b46c32d9965e9a601d41ed21cd02f68d143c689c22c0d090e9620552eb8', '2025-10-09 16:38:23', 0, '2025-09-09 14:38:23'),
(8, 1, '1863a855843cbfbd6a51f971c15012bb050dd3094c845b091aa06aaffe1edf59', '2025-10-10 17:14:23', 1, '2025-09-10 15:14:23'),
(9, 2, 'c13c98031aeafe6de74b78248548026b52b435a5f10a63b22fd645176a17ccd7', '2025-10-11 13:52:11', 0, '2025-09-11 13:52:11'),
(10, 3, 'bc977b61a2c0283b6f17a84460ddf8bb06f893b33d725d14e501af6a5b803b90', '2025-10-11 13:54:04', 0, '2025-09-11 13:54:04'),
(11, 4, '858d1a1cd6e9fb732e0e82a19fe9c1a391f455f4e11c7a0f9e0cfaf8b69b44fb', '2025-10-11 14:01:59', 0, '2025-09-11 14:01:59'),
(12, 5, '63f52cf5b6c6e05617e680c05503ce96c5746fd4b0e2bc664b7d6069af34f30f', '2025-10-11 14:03:55', 0, '2025-09-11 14:03:55'),
(13, 6, '9e3aa522cb79547974b0c61f956c42dfe4193c3ad6cc39fa44c973cf1ab47d65', '2025-10-11 14:05:42', 0, '2025-09-11 14:05:42'),
(14, 7, '8f75ae714399ebad6fff55f99678296c6fb3960af50c7a087f6647fb162caad5', '2025-10-11 14:08:40', 0, '2025-09-11 14:08:40'),
(15, 8, '6e000d39604ca4f8329bd034831542997c487e03db12e2a21a1e954f977f53da', '2025-10-11 14:10:37', 1, '2025-09-11 14:10:37'),
(16, 9, '400aea155d899cfb1d36f7daa2cc00993fc7c63f9530a45f2a1d3f40d5b4b3b1', '2025-10-13 16:16:32', 0, '2025-09-13 14:16:32'),
(17, 10, '8ce1615ebba8df9bbe6a84af19aa08441bdca39724dfc80e1311d6dcc2cbfbea', '2025-10-13 16:16:33', 0, '2025-09-13 14:16:33'),
(18, 11, 'e65ff97d5a5f14bd35a6eaeea85e066617784b9e1a4079e68c6fc80c7f29d27a', '2025-10-13 16:26:33', 0, '2025-09-13 14:26:33'),
(19, 12, '60055ec1257558f6e183afd0b69095fcf58052c7263168d08fbc764c15664f4d', '2025-10-13 16:26:34', 0, '2025-09-13 14:26:34'),
(20, 13, '008b5840e35f943b7ee22ef76ff4eefd777516bbc47c7d8b7b75c4ae5b2de93f', '2025-10-13 16:29:34', 0, '2025-09-13 14:29:34'),
(21, 14, 'a4a446e183823dffc04d0fa6ef599f08078b32cdc874561ba1799ad383a8f714', '2025-10-13 16:29:34', 0, '2025-09-13 14:29:34'),
(22, 15, '66d167e3565079dfee0980ea11a1746e93e36df3cd1625fe7db56892fb41902e', '2025-10-13 16:35:47', 1, '2025-09-13 14:35:47'),
(23, 16, '17aeab629ab93c5604b9d9217e5e1208563b7b87f7c78834c4f76590ead4cb77', '2025-10-13 16:35:54', 0, '2025-09-13 14:35:54'),
(24, 17, 'd562bfe0139d7053f075c3e64c2843f1088b4e85d96b41511b3e41eeb5d148da', '2025-10-13 16:38:02', 1, '2025-09-13 14:38:02'),
(25, 18, '872f3c34630ea73ecd5ea175ff8543fa416cc953a7de5219053e3faf6921fa2e', '2025-10-13 16:38:03', 0, '2025-09-13 14:38:03'),
(26, 19, 'c7a4a751f19d63d7372d742d18834a8b26dc18311590d0e092a4cd1012399166', '2025-10-20 16:17:44', 0, '2025-09-20 14:17:44');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) UNSIGNED NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` varchar(50) DEFAULT 'user',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `role`, `created_at`, `updated_at`) VALUES
(1, 'trpni', 'trpni@example.com', '$2y$10$KqhSMX/Pqye7d/tidzO9ae5asuewivxSLqxxLTdnGIrQWDci5N2V6', 'user', '2025-09-06 13:17:09', '2025-09-06 13:17:09'),
(2, 'phpunit_ff03e452', 'phpunit_ff03e452@example.test', '$2y$12$nyuLzcEKqpo2nk.cOfBOiueWIbUoq5q74PrncdQwu/VT0Kb80VJT6', 'user', '2025-09-11 13:52:11', '2025-09-11 13:52:11'),
(3, 'phpunit_f25d6660', 'phpunit_f25d6660@example.test', '$2y$12$H7YAZ674cXLkMYw0pobQ2OkcVwOefDwB5nC1/aXn2iQsIK6ZTza6C', 'user', '2025-09-11 13:54:04', '2025-09-11 13:54:04'),
(4, 'phpunit_e902cea7', 'phpunit_e902cea7@example.test', '$2y$12$2.BceBGMkPAndsG/Q5p9cetRue7WQz8RTuiP626XAcoT1Oa61FDIe', 'user', '2025-09-11 14:01:58', '2025-09-11 14:01:58'),
(5, 'phpunit_4e6385ac', 'phpunit_4e6385ac@example.test', '$2y$12$5dRyowo9.crmzzBz1yLpLO61ML2ezTe0USo3yZ8jt.FyOcwrn/i7a', 'user', '2025-09-11 14:03:54', '2025-09-11 14:03:54'),
(6, 'phpunit_ec84c50d', 'phpunit_ec84c50d@example.test', '$2y$12$NNuUESr3f4LEgCTjY9q73ePsNqPUCK5mg6fO.nhPYr8RFKD.rwMKi', 'user', '2025-09-11 14:05:42', '2025-09-11 14:05:42'),
(7, 'phpunit_038454c6', 'phpunit_038454c6@example.test', '$2y$12$I8Ia/GUaWPhun.Csj5MtX.BenwwfY2rxfz6OuPVhrqm7WntollUTG', 'user', '2025-09-11 14:08:39', '2025-09-11 14:08:39'),
(8, 'phpunit_324a7c0f', 'phpunit_324a7c0f@example.test', '$2y$12$nDPn3t8UAkwlVmVl9IZ8YO4QGM2lWhgbT1YJdVYop3lqJ05pHxvaK', 'user', '2025-09-11 14:10:37', '2025-09-11 14:10:37'),
(9, 'phpunit_b7d6c8ae', 'phpunit_b7d6c8ae@example.test', '$2y$10$8L67MiYkC464fGavA92sFO9wwAP9fFPTHaqmqIjXio0.UtET66TGe', 'user', '2025-09-13 14:16:32', '2025-09-13 14:16:32'),
(10, 'phpunit_c02ef33e', 'phpunit_c02ef33e@example.test', '$2y$10$p1sZ6BOgLxLWbQl2xQkVzuJFpY5cvxsay/Gxvurk13WJHdTXq4eVe', 'user', '2025-09-13 14:16:32', '2025-09-13 14:16:32'),
(11, 'phpunit_a0e06b10', 'phpunit_a0e06b10@example.test', '$2y$10$AByoduWwS3yzN/bwittoN.xkqSeq2GganQS10jxhEDaN73dG6SKl2', 'user', '2025-09-13 14:26:33', '2025-09-13 14:26:33'),
(12, 'phpunit_77f8220e', 'phpunit_77f8220e@example.test', '$2y$10$NQd1j9YSorFiKJGIawMFRuN1Cw8g0dxOvyAcWKEPCDQS4I72kb4AK', 'user', '2025-09-13 14:26:34', '2025-09-13 14:26:34'),
(13, 'phpunit_7b45f051', 'phpunit_7b45f051@example.test', '$2y$10$JvG6KxhOlF3lYGPybsbzIeDrvoxhB9ATb2NxiVquFFA3pCzuXMqAu', 'user', '2025-09-13 14:29:34', '2025-09-13 14:29:34'),
(14, 'phpunit_6a8fbcd1', 'phpunit_6a8fbcd1@example.test', '$2y$10$XbTCZHsznjeYd8sko2J77u5N4slkkv6JVaNf6cKUB2..lX7dvUOcS', 'user', '2025-09-13 14:29:34', '2025-09-13 14:29:34'),
(15, 'phpunit_123c3c01', 'phpunit_123c3c01@example.test', '$2y$10$hj/H0Ow4AoMrDfip1XPuI.t3DXLDofB/ffaEcb/Kr5gw3CVxmur12', 'user', '2025-09-13 14:35:47', '2025-09-13 14:35:47'),
(16, 'phpunit_b68911d5', 'phpunit_b68911d5@example.test', '$2y$10$Ptrr/b/bm5P0FPcZxJXNp.IvvNq09CLoLBbmB5h1bab06yYfgv4Wy', 'user', '2025-09-13 14:35:54', '2025-09-13 14:35:54'),
(17, 'phpunit_4c25b0e2', 'phpunit_4c25b0e2@example.test', '$2y$10$U0e7BWUXaUCKkeakV1UAme/g9Yf2XVdpdpazebdyBvguOc5snhBai', 'user', '2025-09-13 14:38:02', '2025-09-13 14:38:02'),
(18, 'phpunit_e5d17091', 'phpunit_e5d17091@example.test', '$2y$10$Vglh753zOgp2pBjIr4VpEO78orNqHiFrYpodbjYWd.FS9V5CjDfE6', 'user', '2025-09-13 14:38:03', '2025-09-13 14:38:03'),
(19, 'abdu-admin', 'abdulrahman@testing.com', '$2y$10$vuoME8S0S/ZfJRPaE1ZZZOe0ktLm0XcdvkZeuE8ELy/Z5fI999ZTO', 'user', '2025-09-20 14:17:31', '2025-09-20 14:17:31');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `books`
--
ALTER TABLE `books`
  ADD PRIMARY KEY (`book_id`),
  ADD KEY `idx_book_genre` (`book_genre`),
  ADD KEY `idx_book_author` (`book_author`),
  ADD KEY `idx_book_price` (`book_price`),
  ADD KEY `idx_book_title` (`book_title`);

--
-- Indexes for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`cart_item_id`),
  ADD UNIQUE KEY `uq_user_book` (`user_id`,`book_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `book_id` (`book_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD UNIQUE KEY `idx_orders_session` (`session_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`order_item_id`),
  ADD KEY `idx_order_items_order` (`order_id`);

--
-- Indexes for table `phinxlog`
--
ALTER TABLE `phinxlog`
  ADD PRIMARY KEY (`version`);

--
-- Indexes for table `ratings`
--
ALTER TABLE `ratings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `book_id` (`book_id`);

--
-- Indexes for table `refresh_tokens`
--
ALTER TABLE `refresh_tokens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `token_hash` (`token_hash`),
  ADD KEY `user_id` (`user_id`);

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
-- AUTO_INCREMENT for table `books`
--
ALTER TABLE `books`
  MODIFY `book_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `cart_item_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `order_item_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ratings`
--
ALTER TABLE `ratings`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `refresh_tokens`
--
ALTER TABLE `refresh_tokens`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
