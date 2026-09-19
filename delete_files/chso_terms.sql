-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 19, 2026 at 08:58 PM
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
-- Database: `lufly_new`
--

-- --------------------------------------------------------

--
-- Table structure for table `chso_terms`
--

CREATE TABLE `chso_terms` (
  `term_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(200) NOT NULL DEFAULT '',
  `slug` varchar(200) NOT NULL DEFAULT '',
  `term_group` bigint(10) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

--
-- Dumping data for table `chso_terms`
--

INSERT INTO `chso_terms` (`term_id`, `name`, `slug`, `term_group`) VALUES
(1, 'Uncategorized', 'uncategorized', 0),
(2, 'simple', 'simple', 0),
(3, 'grouped', 'grouped', 0),
(4, 'variable', 'variable', 0),
(5, 'external', 'external', 0),
(6, 'exclude-from-search', 'exclude-from-search', 0),
(7, 'exclude-from-catalog', 'exclude-from-catalog', 0),
(8, 'featured', 'featured', 0),
(9, 'outofstock', 'outofstock', 0),
(10, 'rated-1', 'rated-1', 0),
(11, 'rated-2', 'rated-2', 0),
(12, 'rated-3', 'rated-3', 0),
(13, 'rated-4', 'rated-4', 0),
(14, 'rated-5', 'rated-5', 0),
(15, 'Uncategorized', 'uncategorized', 0),
(16, 'Business', 'business', 0),
(17, 'Information', 'information', 0),
(18, 'Marketing', 'marketing', 0),
(19, 'Promotions', 'promotions', 0),
(20, 'Search Engine', 'search-engine', 0),
(21, 'Social Media', 'social-media', 0),
(22, 'Statistics', 'statistics', 0),
(23, 'Writing', 'writing', 0),
(24, 'Blogging', 'blogging', 0),
(25, 'Community', 'community', 0),
(26, 'Copywriting', 'copywriting', 0),
(27, 'Educational', 'educational', 0),
(28, 'Experiences', 'experiences', 0),
(29, 'Knowledge', 'knowledge', 0),
(30, 'Learning', 'learning', 0),
(31, 'Management', 'management', 0),
(32, 'Networking', 'networking', 0),
(33, 'Photography', 'photography', 0),
(34, 'Success Story', 'success-story', 0),
(35, 'Bathroom', 'bathroom', 0),
(36, 'Black', 'black', 0),
(37, 'Blue', 'blue', 0),
(38, 'Brown', 'brown', 0),
(40, 'Clay', 'clay', 0),
(41, 'Earthenware', 'earthenware', 0),
(42, 'Glass', 'glass', 0),
(44, 'Gray', 'gray', 0),
(45, 'Green', 'green', 0),
(46, 'Kitchen', 'kitchen', 0),
(47, 'Large', 'large', 0),
(48, 'Medium', 'medium', 0),
(49, 'Non Oxide', 'non-oxide', 0),
(51, 'Orange', 'orange', 0),
(52, 'Our Store', 'our-store', 0),
(53, 'Pink', 'pink', 0),
(54, 'Porcelain', 'porcelain', 0),
(55, 'Red', 'red', 0),
(57, 'Silicates', 'silicates', 0),
(59, 'Small', 'small', 0),
(60, 'Soft Porcelain', 'soft-porcelain', 0),
(61, 'Stoneware', 'stoneware', 0),
(64, 'White', 'white', 0),
(65, 'Yellow', 'yellow', 0),
(84, 'Footer Navigation', 'footer-navigation', 0),
(85, 'Primary Navigation', 'primary-navigation', 0),
(86, 'Secondary Navigation', 'secondary-navigation', 0),
(87, 'Shop By Categories', 'shop-by-categories', 0),
(88, 'avanam-stoneware', 'avanam-stoneware', 0),
(89, 'Kids', 'kids', 0),
(90, 'Washbasin Mixer', 'washbasin-mixer', 0),
(91, 'Sink Mixer', 'sink-mixer', 0),
(92, 'Shower Set', 'shower-set', 0),
(93, 'Sensors', 'sensors', 0),
(95, 'Disabled', 'disabled', 0),
(96, 'Code Snippet', 'code-snippet', 0),
(97, 'Elementor Widget', 'elementor-widget', 0),
(98, 'Gutenberg Block', 'gutenberg-block', 0),
(99, 'Popup', 'popup', 0),
(100, 'Form', 'form', 0),
(101, 'Visual App', 'visual-app', 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `chso_terms`
--
ALTER TABLE `chso_terms`
  ADD PRIMARY KEY (`term_id`),
  ADD KEY `slug` (`slug`(191)),
  ADD KEY `name` (`name`(191));

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `chso_terms`
--
ALTER TABLE `chso_terms`
  MODIFY `term_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=102;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
