-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 28, 2026 at 01:22 PM
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
-- Database: `nyumbahub_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(255) DEFAULT NULL,
  `role` enum('super_admin','admin','moderator') DEFAULT 'admin',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `email`, `password`, `full_name`, `role`, `created_at`) VALUES
(1, 'admin', 'admin@nyumbahub.co.ke', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Super Admin', 'super_admin', '2026-05-27 17:37:29');

-- --------------------------------------------------------

--
-- Table structure for table `admin_settings`
--

CREATE TABLE `admin_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_settings`
--

INSERT INTO `admin_settings` (`id`, `setting_key`, `setting_value`, `created_at`, `updated_at`) VALUES
(1, 'site_name', 'NyumbaHub Kenya', '2026-05-27 20:19:34', '2026-05-27 20:19:34'),
(2, 'site_email', 'admin@nyumbahub.co.ke', '2026-05-27 20:19:34', '2026-05-27 20:19:34'),
(3, 'admin_email', 'admin@nyumbahub.co.ke', '2026-05-27 20:19:34', '2026-05-27 20:19:34'),
(4, 'currency', 'KES', '2026-05-27 20:19:34', '2026-05-27 20:19:34'),
(5, 'tax_rate', '0', '2026-05-27 20:19:34', '2026-05-27 20:19:34'),
(6, 'commission_rate', '5', '2026-05-27 20:19:34', '2026-05-27 20:19:34'),
(7, 'maintenance_mode', '0', '2026-05-27 20:19:34', '2026-05-27 20:19:34'),
(8, 'facebook_url', '', '2026-05-27 20:19:34', '2026-05-27 20:19:34'),
(9, 'twitter_url', '', '2026-05-27 20:19:34', '2026-05-27 20:19:34'),
(10, 'instagram_url', '', '2026-05-27 20:19:34', '2026-05-27 20:19:34'),
(11, 'site_logo', '', '2026-05-27 20:19:34', '2026-05-27 20:19:34'),
(12, 'site_favicon', '', '2026-05-27 20:19:34', '2026-05-27 20:19:34');

-- --------------------------------------------------------

--
-- Table structure for table `counties`
--

CREATE TABLE `counties` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `counties`
--

INSERT INTO `counties` (`id`, `name`) VALUES
(1, 'Nairobi'),
(2, 'Mombasa'),
(3, 'Kisumu'),
(4, 'Nakuru'),
(5, 'Kiambu'),
(6, 'Uasin Gishu'),
(7, 'Machakos'),
(8, 'Wajir'),
(9, 'Mandera'),
(10, 'Marsabit'),
(11, 'Isiolo'),
(12, 'Meru'),
(13, 'Tharaka Nithi'),
(14, 'Embu'),
(15, 'Kitui'),
(16, 'Machakos'),
(17, 'Makueni'),
(18, 'Nyandarua'),
(19, 'Nyeri'),
(20, 'Kirinyaga'),
(21, 'Murang\'a'),
(22, 'Kiambu'),
(23, 'Turkana'),
(24, 'West Pokot'),
(25, 'Samburu'),
(26, 'Trans Nzoia'),
(27, 'Uasin Gishu'),
(28, 'Elgeyo Marakwet'),
(29, 'Nandi'),
(30, 'Baringo'),
(31, 'Laikipia'),
(32, 'Nakuru'),
(33, 'Narok'),
(34, 'Kajiado'),
(35, 'Kericho'),
(36, 'Bomet'),
(37, 'Kakamega'),
(38, 'Vihiga'),
(39, 'Bungoma'),
(40, 'Busia'),
(41, 'Siaya'),
(42, 'Kisumu'),
(43, 'Homa Bay'),
(44, 'Migori'),
(45, 'Kisii'),
(46, 'Nyamira'),
(47, 'Nairobi');

-- --------------------------------------------------------

--
-- Table structure for table `favorites`
--

CREATE TABLE `favorites` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `property_id` int(11) DEFAULT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `type` enum('email','sms','system') DEFAULT 'system',
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `type`, `is_read`, `created_at`) VALUES
(1, 1, 'Payment Successful', 'Your property has been submitted for approval.', 'system', 0, '2026-05-27 19:58:34'),
(2, 1, 'Payment Successful', 'Your property has been submitted for approval.', 'system', 0, '2026-05-27 20:36:06');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `property_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` enum('mpesa','paypal','card') NOT NULL,
  `transaction_id` varchar(255) DEFAULT NULL,
  `package_type` enum('basic','premium','vip') NOT NULL,
  `status` enum('pending','completed','failed','refunded') DEFAULT 'pending',
  `mpesa_code` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `user_id`, `property_id`, `amount`, `payment_method`, `transaction_id`, `package_type`, `status`, `mpesa_code`, `created_at`) VALUES
(1, 1, NULL, 2000.00, 'mpesa', NULL, '', 'completed', NULL, '2026-05-27 18:11:02'),
(2, 1, NULL, 5000.00, 'mpesa', NULL, '', 'completed', NULL, '2026-05-27 18:11:07'),
(3, 1, NULL, 1000.00, 'mpesa', '', 'basic', 'completed', 'q23ewedrfwfw', '2026-05-27 19:58:34'),
(6, 1, 2, 1000.00, 'mpesa', '', 'basic', 'completed', 'q23ewedrfwfw', '2026-05-27 20:36:06');

-- --------------------------------------------------------

--
-- Table structure for table `phone_views`
--

CREATE TABLE `phone_views` (
  `id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `viewed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `phone_views`
--

INSERT INTO `phone_views` (`id`, `property_id`, `user_id`, `ip_address`, `viewed_at`) VALUES
(1, 2, 2, '::1', '2026-05-27 21:15:40'),
(2, 2, 2, '::1', '2026-05-27 21:15:41'),
(3, 2, 2, '::1', '2026-05-27 21:15:43'),
(4, 2, 2, '::1', '2026-05-27 21:15:44'),
(5, 2, 2, '::1', '2026-05-27 21:15:45'),
(6, 2, 2, '::1', '2026-05-27 21:21:12'),
(7, 2, 2, '::1', '2026-05-27 21:22:53'),
(8, 2, 2, '::1', '2026-05-27 21:22:54'),
(9, 2, 2, '::1', '2026-05-27 21:22:54'),
(10, 2, 2, '::1', '2026-05-27 21:22:54'),
(11, 2, 2, '::1', '2026-05-27 21:22:54'),
(12, 2, 2, '::1', '2026-05-27 21:22:56'),
(13, 2, 2, '::1', '2026-05-28 11:00:13'),
(14, 2, 1, '::1', '2026-05-28 11:02:18');

-- --------------------------------------------------------

--
-- Table structure for table `properties`
--

CREATE TABLE `properties` (
  `id` int(11) NOT NULL,
  `owner_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `property_type` enum('house','apartment','bedsitter','single_room','hostel','commercial','land') NOT NULL,
  `category` enum('rent','sale','airbnb') NOT NULL,
  `price_rent` decimal(10,2) DEFAULT NULL,
  `price_sale` decimal(10,2) DEFAULT NULL,
  `service_charge` decimal(10,2) DEFAULT NULL,
  `county_id` int(11) DEFAULT NULL,
  `town_id` int(11) DEFAULT NULL,
  `estate` varchar(255) DEFAULT NULL,
  `street` varchar(255) DEFAULT NULL,
  `bedrooms` int(11) DEFAULT 0,
  `bathrooms` int(11) DEFAULT 0,
  `parking_slots` int(11) DEFAULT 0,
  `square_feet` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `water_available` tinyint(1) DEFAULT 0,
  `electricity_available` tinyint(1) DEFAULT 0,
  `wifi_available` tinyint(1) DEFAULT 0,
  `security_available` tinyint(1) DEFAULT 0,
  `cctv` tinyint(1) DEFAULT 0,
  `borehole` tinyint(1) DEFAULT 0,
  `swimming_pool` tinyint(1) DEFAULT 0,
  `gym` tinyint(1) DEFAULT 0,
  `backup_generator` tinyint(1) DEFAULT 0,
  `balcony` tinyint(1) DEFAULT 0,
  `furnished` tinyint(1) DEFAULT 0,
  `pets_allowed` tinyint(1) DEFAULT 0,
  `listing_package` enum('basic','premium','vip') DEFAULT 'basic',
  `listing_expiry` date DEFAULT NULL,
  `is_featured` tinyint(1) DEFAULT 0,
  `is_verified` tinyint(1) DEFAULT 0,
  `status` enum('pending_payment','pending_approval','approved','rejected') DEFAULT 'pending_payment',
  `views_count` int(11) DEFAULT 0,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `properties`
--

INSERT INTO `properties` (`id`, `owner_id`, `title`, `slug`, `property_type`, `category`, `price_rent`, `price_sale`, `service_charge`, `county_id`, `town_id`, `estate`, `street`, `bedrooms`, `bathrooms`, `parking_slots`, `square_feet`, `description`, `water_available`, `electricity_available`, `wifi_available`, `security_available`, `cctv`, `borehole`, `swimming_pool`, `gym`, `backup_generator`, `balcony`, `furnished`, `pets_allowed`, `listing_package`, `listing_expiry`, `is_featured`, `is_verified`, `status`, `views_count`, `latitude`, `longitude`, `created_at`, `updated_at`) VALUES
(2, 1, 'Spacious 2 Bedroom Apartment', 'spacious-2-bedroom-apartment-1779914090', 'apartment', 'rent', 26000.00, NULL, 500.00, 1, 551, 'Highrise', 'Mbagathi way', 2, 1, 2, NULL, 'A very good two bedroom apartment at highrise estate, and also affordable📞', 1, 1, 1, 1, 0, 1, 0, 0, 0, 0, 1, 1, 'basic', '2026-06-26', 0, 0, 'approved', 24, NULL, NULL, '2026-05-27 20:34:50', '2026-05-28 11:09:31'),
(3, 1, 'Luxury 5-Bedroom Villa with Pool in Karen', 'luxury-5-bedroom-villa-karen', 'house', 'sale', NULL, 45000000.00, 15000.00, 1, 3, 'Karen', 'Karen Road', 5, 5, 4, 450, 'Stunning luxury villa located in the prestigious Karen area. Features include a large swimming pool, beautifully landscaped garden, modern kitchen with granite countertops, master ensuite with walk-in closet, servant quarters, and solar water heating. The property offers 24/7 security and is close to international schools and shopping malls.', 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 'vip', '2026-08-26', 1, 1, 'approved', 45, NULL, NULL, '2026-05-13 11:17:53', '2026-05-28 11:17:53'),
(4, 1, 'Spacious 3-Bedroom Apartment in Kilimani', 'spacious-3-bedroom-apartment-kilimani', 'apartment', 'rent', 120000.00, NULL, 5000.00, 1, 4, 'Kilimani', 'Elgeyo Marakwet Road', 3, 3, 2, 180, 'Beautifully furnished 3-bedroom apartment in the heart of Kilimani. Features include spacious living room, modern kitchen with fitted cabinets, balcony with city views, and 24/7 security. Close to Yaya Centre, restaurants, and entertainment spots.', 1, 1, 1, 1, 1, 0, 1, 1, 1, 1, 1, 1, 'premium', '2026-07-27', 1, 1, 'approved', 78, NULL, NULL, '2026-05-08 11:17:53', '2026-05-28 11:17:53'),
(5, 1, 'Beachfront Villa in Nyali, Mombasa', 'beachfront-villa-nyali-mombasa', 'house', 'sale', NULL, 65000000.00, 20000.00, 2, 7, 'Nyali', 'Beach Road', 4, 4, 3, 350, 'Exclusive beachfront villa with direct access to the Indian Ocean. Features include ocean view terrace, private swimming pool, modern finishes, and 24/7 security. Perfect for vacation home or luxury rental investment.', 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 'vip', '2026-08-26', 1, 1, 'approved', 32, NULL, NULL, '2026-05-18 11:17:53', '2026-05-28 11:17:53'),
(6, 1, 'Clean Bedsitter in Milimani, Kisumu', 'clean-bedsitter-milimani-kisumu', 'bedsitter', 'rent', 15000.00, NULL, 500.00, 3, 11, 'Milimani', 'Kisumu Road', 1, 1, 1, 250, 'Well-maintained bedsitter in a secure compound in Milimani. Features include tiled floors, secure parking, water heater, and CCTV cameras. Close to the CBD and shopping centers.', 1, 1, 1, 1, 1, 0, 0, 0, 0, 0, 1, 1, 'basic', '2026-06-27', 0, 1, 'approved', 56, NULL, NULL, '2026-05-03 11:17:53', '2026-05-28 11:17:53'),
(7, 1, 'Commercial Office Space in Nakuru CBD', 'commercial-office-space-nakuru-cbd', 'commercial', 'rent', 80000.00, NULL, 3000.00, 4, 13, 'Nakuru CBD', 'Kenyatta Avenue', 0, 2, 5, 500, 'Prime commercial office space located in Nakuru CBD. Features include reception area, 2 private offices, open plan workspace, kitchenette, and secure parking. Perfect for corporate offices.', 1, 1, 1, 1, 1, 0, 0, 0, 1, 0, 0, 0, 'premium', '2026-07-27', 1, 1, 'approved', 24, NULL, NULL, '2026-05-23 11:17:53', '2026-05-28 11:18:13'),
(8, 1, 'Prime Land for Sale in Ruaka, Kiambu', 'prime-land-for-sale-ruaka-kiambu', 'land', 'sale', NULL, 8500000.00, NULL, 5, 18, 'Ruaka', 'Ruaka Road', 0, 0, 0, 10000, '1/4 acre plot of prime land located in the fast-growing Ruaka area. Close to major roads, shopping centers, and schools. Title deed available, ready for development. Perfect for residential or commercial development.', 1, 1, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 'basic', '2026-06-27', 1, 1, 'approved', 41, NULL, NULL, '2026-05-16 11:17:53', '2026-05-28 11:17:53'),
(9, 1, 'Modern Studio Apartment in Westlands', 'modern-studio-apartment-westlands', 'apartment', 'rent', 35000.00, NULL, 2000.00, 1, 2, 'Westlands', 'Waiyaki Way', 1, 1, 1, 400, 'Fully furnished studio apartment in the heart of Westlands. Walking distance to Sarit Centre, restaurants, and entertainment spots. Includes gym access and rooftop terrace.', 1, 1, 1, 1, 1, 0, 1, 1, 1, 1, 1, 0, 'premium', '2026-07-27', 1, 1, 'approved', 89, NULL, NULL, '2026-05-10 11:17:53', '2026-05-28 11:17:53'),
(10, 1, 'Luxury Beach Cottage in Diani', 'luxury-beach-cottage-diani', 'house', 'airbnb', 12000.00, NULL, 1000.00, 2, 9, 'Diani', 'Diani Beach Road', 2, 2, 2, 1200, 'Beautiful beach cottage located just 200 meters from Diani Beach. Perfect for vacation rentals, fully furnished with modern amenities, private garden, and outdoor shower.', 1, 1, 1, 1, 1, 1, 0, 0, 0, 1, 1, 1, 'vip', '2026-08-26', 1, 1, 'approved', 67, NULL, NULL, '2026-05-20 11:17:53', '2026-05-28 11:17:53'),
(11, 1, 'Elegant 4-Bedroom Townhouse in Runda', 'elegant-4-bedroom-townhouse-runda', 'house', 'sale', NULL, 35000000.00, 10000.00, 1, 5, 'Runda', 'Runda Drive', 4, 4, 3, 300, 'Elegant townhouse in the prestigious Runda estate. Features include spacious bedrooms, modern kitchen, private garden, and community amenities. Close to international schools and shopping centers.', 1, 1, 1, 1, 1, 0, 1, 1, 1, 1, 1, 0, 'premium', '2026-07-27', 1, 1, 'approved', 34, NULL, NULL, '2026-05-06 11:17:53', '2026-05-28 11:17:53'),
(12, 1, 'Affordable Single Room in Hostel - CBD', 'affordable-single-room-hostel-cbd', 'single_room', 'rent', 8000.00, NULL, 0.00, 1, 1, 'CBD', 'Moi Avenue', 1, 1, 0, 100, 'Budget-friendly single room in a secure hostel located in Nairobi CBD. Shared bathroom facilities, 24/7 security, and close to all amenities. Perfect for students and young professionals.', 1, 1, 1, 1, 1, 0, 0, 0, 0, 0, 0, 0, 'basic', '2026-06-27', 0, 0, 'approved', 112, NULL, NULL, '2026-05-23 11:17:53', '2026-05-28 11:17:53'),
(13, 1, 'Spacious 3-Bedroom House in Lang\'ata', 'spacious-3-bedroom-house-langata', 'house', 'rent', 85000.00, NULL, 3000.00, 1, 521, 'Lang\'ata', 'Lang\'ata Road', 3, 2, 2, 200, 'Beautiful family home in a quiet Lang\'ata neighborhood. Large garden, ample parking, and close to schools and shopping centers. Features include tiled floors, modern kitchen, and security system.', 1, 1, 1, 1, 1, 0, 0, 0, 1, 1, 0, 1, 'premium', '2026-07-27', 0, 1, 'approved', 67, NULL, NULL, '2026-04-28 11:17:53', '2026-05-28 11:17:53'),
(14, 1, 'Luxury Penthouse with City Views', 'luxury-penthouse-city-views', 'apartment', 'rent', 250000.00, NULL, 10000.00, 1, 2, 'Westlands', 'Woodvale Grove', 3, 3, 2, 250, 'Exclusive penthouse with panoramic city views. Features include floor-to-ceiling windows, private rooftop terrace, modern finishes, and high-end appliances. Includes dedicated parking and 24/7 concierge.', 1, 1, 1, 1, 1, 0, 1, 1, 1, 1, 1, 0, 'vip', '2026-08-26', 1, 1, 'approved', 45, NULL, NULL, '2026-05-14 11:17:53', '2026-05-28 11:17:53');

-- --------------------------------------------------------

--
-- Table structure for table `property_documents`
--

CREATE TABLE `property_documents` (
  `id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `document_path` varchar(255) DEFAULT NULL,
  `document_name` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `property_images`
--

CREATE TABLE `property_images` (
  `id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `is_primary` tinyint(1) DEFAULT 0,
  `image_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `property_images`
--

INSERT INTO `property_images` (`id`, `property_id`, `image_path`, `is_primary`, `image_order`, `created_at`) VALUES
(12, 2, 'uploads/properties/1779914090_0_22fb7858f90a79c917a4a2efae786ce1.jpg', 0, 0, '2026-05-27 20:34:50'),
(13, 2, 'uploads/properties/1779914254_0_6af4a817968ab68792f36d220c33a450.jpg', 0, 1, '2026-05-27 20:37:34'),
(14, 2, 'uploads/properties/1779914254_1_36bbf18e6bea53ee9ba551934ae2a204.jpg', 0, 2, '2026-05-27 20:37:34'),
(15, 2, 'uploads/properties/1779914254_2_6e8a0b9efda59788ae751160a00288b4.jpg', 0, 3, '2026-05-27 20:37:34'),
(16, 2, 'uploads/properties/1779914254_3_2f2b4e360166b532a26dac0e89bb2918.jpg', 1, 4, '2026-05-27 20:37:34'),
(17, 2, 'uploads/properties/1779914254_4_ab99caf93b52c1568af1e3051b44bb72.jpg', 0, 5, '2026-05-27 20:37:34'),
(18, 2, 'uploads/properties/1779914254_5_4385abea2288b8fffa669fe1471bf334.jpg', 0, 6, '2026-05-27 20:37:34'),
(19, 2, 'uploads/properties/1779914254_6_542f99bc697b94c95e3d634361e8eac6.jpg', 0, 7, '2026-05-27 20:37:34'),
(20, 2, 'uploads/properties/1779914254_7_fa489d35aa36be80dded689815733654.jpg', 0, 8, '2026-05-27 20:37:34');

-- --------------------------------------------------------

--
-- Table structure for table `property_inquiries`
--

CREATE TABLE `property_inquiries` (
  `id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `property_videos`
--

CREATE TABLE `property_videos` (
  `id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `video_path` varchar(255) DEFAULT NULL,
  `video_url` varchar(255) DEFAULT NULL,
  `video_type` enum('youtube','vimeo','upload') DEFAULT 'youtube',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `property_views`
--

CREATE TABLE `property_views` (
  `id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `viewed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `rating` int(11) DEFAULT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(255) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `created_at`, `updated_at`) VALUES
(1, 'site_name', 'NyumbaHub Kenya', '2026-05-27 17:37:29', '2026-05-27 17:37:29'),
(2, 'site_email', 'info@nyumbahub.co.ke', '2026-05-27 17:37:29', '2026-05-27 17:37:29'),
(3, 'site_phone', '+254700000000', '2026-05-27 17:37:29', '2026-05-27 17:37:29'),
(4, 'mpesa_consumer_key', '', '2026-05-27 17:37:29', '2026-05-27 17:37:29'),
(5, 'mpesa_consumer_secret', '', '2026-05-27 17:37:29', '2026-05-27 17:37:29'),
(6, 'paypal_client_id', '', '2026-05-27 17:37:29', '2026-05-27 17:37:29'),
(7, 'paypal_secret', '', '2026-05-27 17:37:29', '2026-05-27 17:37:29'),
(8, 'google_maps_api', '', '2026-05-27 17:37:29', '2026-05-27 17:37:29');

-- --------------------------------------------------------

--
-- Table structure for table `subscriptions`
--

CREATE TABLE `subscriptions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `plan` enum('bronze','silver','gold') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` enum('active','expired','cancelled') DEFAULT 'active',
  `payment_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subscriptions`
--

INSERT INTO `subscriptions` (`id`, `user_id`, `plan`, `amount`, `start_date`, `end_date`, `status`, `payment_id`, `created_at`) VALUES
(1, 1, 'bronze', 2000.00, '2026-05-27', '2026-06-26', 'active', 1, '2026-05-27 18:11:02'),
(2, 1, 'silver', 5000.00, '2026-05-27', '2026-06-26', 'active', 2, '2026-05-27 18:11:07');

-- --------------------------------------------------------

--
-- Table structure for table `towns`
--

CREATE TABLE `towns` (
  `id` int(11) NOT NULL,
  `county_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `towns`
--

INSERT INTO `towns` (`id`, `county_id`, `name`) VALUES
(1, 1, 'Nairobi CBD'),
(2, 1, 'Westlands'),
(3, 1, 'Karen'),
(4, 1, 'Kilimani'),
(5, 1, 'Runda'),
(6, 2, 'Mombasa CBD'),
(7, 2, 'Nyali'),
(8, 2, 'Bamburi'),
(9, 2, 'Diani'),
(10, 3, 'Kisumu CBD'),
(11, 3, 'Milimani'),
(12, 3, 'Kondele'),
(13, 4, 'Nakuru CBD'),
(14, 4, 'Milimani'),
(15, 4, 'Lanet'),
(16, 5, 'Kiambu'),
(17, 5, 'Thika'),
(18, 5, 'Ruaka'),
(19, 5, 'Tigoni'),
(20, 1, 'Mombasa CBD'),
(21, 1, 'Nyali'),
(22, 1, 'Bamburi'),
(23, 1, 'Likoni'),
(24, 1, 'Changamwe'),
(25, 1, 'Shanzu'),
(26, 1, 'Mtwapa'),
(27, 1, 'Kizingo'),
(28, 1, 'Kiembeni'),
(29, 1, 'Frere Town'),
(30, 1, 'Mkomani'),
(31, 1, 'Port Reitz'),
(32, 1, 'Jomvu'),
(33, 1, 'Magongo'),
(34, 2, 'Ukunda'),
(35, 2, 'Diani'),
(36, 2, 'Msambweni'),
(37, 2, 'Lunga Lunga'),
(38, 2, 'Kwale Town'),
(39, 2, 'Kinango'),
(40, 2, 'Mackinnon Road'),
(41, 2, 'Ramisi'),
(42, 2, 'Gazi'),
(43, 3, 'Kilifi Town'),
(44, 3, 'Malindi'),
(45, 3, 'Watamu'),
(46, 3, 'Mariakani'),
(47, 3, 'Vipingo'),
(48, 3, 'Kikambala'),
(49, 3, 'Mambrui'),
(50, 3, 'Gongoni'),
(51, 3, 'Tezo'),
(52, 3, 'Takaungu'),
(53, 4, 'Hola'),
(54, 4, 'Garsen'),
(55, 4, 'Bura'),
(56, 4, 'Madogo'),
(57, 4, 'Wayu'),
(58, 4, 'Kipini'),
(59, 4, 'Ngao'),
(60, 4, 'Minjila'),
(61, 4, 'Kipao'),
(62, 5, 'Lamu'),
(63, 5, 'Mpeketoni'),
(64, 5, 'Mokowe'),
(65, 5, 'Shela'),
(66, 5, 'Kipungani'),
(67, 5, 'Faza'),
(68, 5, 'Kizingitini'),
(69, 5, 'Mkunumbi'),
(70, 5, 'Hindi'),
(71, 6, 'Voi'),
(72, 6, 'Taveta'),
(73, 6, 'Wundanyi'),
(74, 6, 'Mwatate'),
(75, 6, 'Bura'),
(76, 6, 'Mbale'),
(77, 6, 'Maktau'),
(78, 6, 'Chala'),
(79, 7, 'Garissa'),
(80, 7, 'Dadaab'),
(81, 7, 'Fafi'),
(82, 7, 'Masalani'),
(83, 7, 'Hulugho'),
(84, 7, 'Sangailu'),
(85, 7, 'Ijara'),
(86, 7, 'Liboi'),
(87, 8, 'Wajir'),
(88, 8, 'Habaswein'),
(89, 8, 'Eldas'),
(90, 8, 'Tarbaj'),
(91, 8, 'Griffiths'),
(92, 8, 'Buna'),
(93, 8, 'Khorof Harar'),
(94, 9, 'Mandera'),
(95, 9, 'Rhamu'),
(96, 9, 'Elwak'),
(97, 9, 'Takaba'),
(98, 9, 'Fino'),
(99, 9, 'Kutulo'),
(100, 9, 'Arabia'),
(101, 9, 'Lafey'),
(102, 9, 'Banisa'),
(103, 10, 'Marsabit'),
(104, 10, 'Moyale'),
(105, 10, 'North Horr'),
(106, 10, 'Laisamis'),
(107, 10, 'Maikona'),
(108, 10, 'Kalacha'),
(109, 10, 'Sololo'),
(110, 10, 'Dukana'),
(111, 10, 'Turbi'),
(112, 11, 'Isiolo'),
(113, 11, 'Merti'),
(114, 11, 'Garbatulla'),
(115, 11, 'Kinna'),
(116, 11, 'Sericho'),
(117, 11, 'Oldonyiro'),
(118, 11, 'Cherab'),
(119, 12, 'Meru Town'),
(120, 12, 'Maua'),
(121, 12, 'Nkubu'),
(122, 12, 'Timau'),
(123, 12, 'Chogoria'),
(124, 12, 'Laare'),
(125, 12, 'Muthara'),
(126, 12, 'Kianjai'),
(127, 12, 'Kangeta'),
(128, 12, 'Ruiri'),
(129, 12, 'Mikinduri'),
(130, 12, 'Kiegoi'),
(131, 12, 'Kiirua'),
(132, 13, 'Chuka'),
(133, 13, 'Marimanti'),
(134, 13, 'Kathwana'),
(135, 13, 'Mitheru'),
(136, 13, 'Kiang\'ombe'),
(137, 13, 'Gatunga'),
(138, 13, 'Tharaka'),
(139, 13, 'Nkondi'),
(140, 14, 'Embu'),
(141, 14, 'Runyenjes'),
(142, 14, 'Siakago'),
(143, 14, 'Manyatta'),
(144, 14, 'Kiritiri'),
(145, 14, 'Kianjokoma'),
(146, 14, 'Kagaari'),
(147, 14, 'Ishiara'),
(148, 14, 'Mavuria'),
(149, 15, 'Kitui'),
(150, 15, 'Mwingi'),
(151, 15, 'Mutomo'),
(152, 15, 'Kyuso'),
(153, 15, 'Kisasi'),
(154, 15, 'Migwani'),
(155, 15, 'Nzambani'),
(156, 15, 'Kwa Vonza'),
(157, 15, 'Zombe'),
(158, 16, 'Machakos'),
(159, 16, 'Athi River'),
(160, 16, 'Mlolongo'),
(161, 16, 'Kangundo'),
(162, 16, 'Tala'),
(163, 16, 'Kinanie'),
(164, 16, 'Syokimau'),
(165, 16, 'Matuu'),
(166, 16, 'Masii'),
(167, 16, 'Mavoko'),
(168, 16, 'Katangi'),
(169, 16, 'Kithimani'),
(170, 16, 'Yatta'),
(171, 16, 'Kalamba'),
(172, 17, 'Wote'),
(173, 17, 'Emali'),
(174, 17, 'Makindu'),
(175, 17, 'Kibwezi'),
(176, 17, 'Sultan Hamud'),
(177, 17, 'Kathonzweni'),
(178, 17, 'Mukaa'),
(179, 17, 'Mtito Andei'),
(180, 18, 'Ol Kalou'),
(181, 18, 'Engineer'),
(182, 18, 'Njabini'),
(183, 18, 'Kinungi'),
(184, 18, 'North Kinangop'),
(185, 18, 'South Kinangop'),
(186, 18, 'Kipipiri'),
(187, 18, 'Shamata'),
(188, 18, 'Miharati'),
(189, 19, 'Nyeri Town'),
(190, 19, 'Karatina'),
(191, 19, 'Othaya'),
(192, 19, 'Naro Moru'),
(193, 19, 'Mweiga'),
(194, 19, 'Mukurwe-ini'),
(195, 19, 'Tetu'),
(196, 19, 'Giakanja'),
(197, 19, 'Mathari'),
(198, 19, 'Chaka'),
(199, 19, 'Endarasha'),
(200, 20, 'Kerugoya'),
(201, 20, 'Kutus'),
(202, 20, 'Wang\'uru'),
(203, 20, 'Kagio'),
(204, 20, 'Baricho'),
(205, 20, 'Ngurubani'),
(206, 20, 'Kianyaga'),
(207, 20, 'Kimbimbi'),
(208, 20, 'Gathigiriri'),
(209, 21, 'Murang\'a'),
(210, 21, 'Kenol'),
(211, 21, 'Kandara'),
(212, 21, 'Makuyu'),
(213, 21, 'Maragua'),
(214, 21, 'Kahuro'),
(215, 21, 'Kigumo'),
(216, 21, 'Gatanga'),
(217, 21, 'Kangari'),
(218, 21, 'Ithanga'),
(219, 21, 'Kiriaini'),
(220, 21, 'Kiharu'),
(221, 21, 'Mbiri'),
(222, 22, 'Thika'),
(223, 22, 'Kiambu Town'),
(224, 22, 'Ruiru'),
(225, 22, 'Juja'),
(226, 22, 'Limuru'),
(227, 22, 'Kikuyu'),
(228, 22, 'Ruaka'),
(229, 22, 'Karuri'),
(230, 22, 'Tigoni'),
(231, 22, 'Githunguri'),
(232, 22, 'Kabete'),
(233, 22, 'Kinoo'),
(234, 22, 'Kiamumbi'),
(235, 22, 'Githurai'),
(236, 22, 'Kahawa'),
(237, 22, 'Kasarani'),
(238, 22, 'Mwiki'),
(239, 22, 'Runda'),
(240, 22, 'Rosslyn'),
(241, 22, 'Gachie'),
(242, 23, 'Lodwar'),
(243, 23, 'Kakuma'),
(244, 23, 'Lokichoggio'),
(245, 23, 'Lokitaung'),
(246, 23, 'Lokori'),
(247, 23, 'Katilu'),
(248, 23, 'Kalokol'),
(249, 23, 'Eliye Springs'),
(250, 24, 'Kapenguria'),
(251, 24, 'Makutano'),
(252, 24, 'Ortum'),
(253, 24, 'Chepareria'),
(254, 24, 'Kacheliba'),
(255, 24, 'Sigor'),
(256, 24, 'Alale'),
(257, 25, 'Maralal'),
(258, 25, 'Baragoi'),
(259, 25, 'Wamba'),
(260, 25, 'Archers Post'),
(261, 25, 'Lodokejek'),
(262, 25, 'Kisima'),
(263, 25, 'Suguta Marmar'),
(264, 26, 'Kitale'),
(265, 26, 'Endebess'),
(266, 26, 'Kiminini'),
(267, 26, 'Saboti'),
(268, 26, 'Kachibora'),
(269, 26, 'Sikhendu'),
(270, 26, 'Matisi'),
(271, 26, 'Makutano'),
(272, 26, 'Kipsaina'),
(273, 27, 'Eldoret'),
(274, 27, 'Burnt Forest'),
(275, 27, 'Turbo'),
(276, 27, 'Moi\'s Bridge'),
(277, 27, 'Kapsaret'),
(278, 27, 'Kapsoya'),
(279, 27, 'Langas'),
(280, 27, 'Kimumu'),
(281, 27, 'Chepkoilel'),
(282, 27, 'Soy'),
(283, 27, 'Moiben'),
(284, 27, 'Kesses'),
(285, 27, 'Lessos'),
(286, 27, 'Tarakwo'),
(287, 28, 'Iten'),
(288, 28, 'Kapsowar'),
(289, 28, 'Chebara'),
(290, 28, 'Tambach'),
(291, 28, 'Kapyego'),
(292, 28, 'Kipkabus'),
(293, 28, 'Kokwong'),
(294, 29, 'Kapsabet'),
(295, 29, 'Nandi Hills'),
(296, 29, 'Mosoriot'),
(297, 29, 'Kabiyet'),
(298, 29, 'Chemase'),
(299, 29, 'Chepterwai'),
(300, 29, 'Kilibwoni'),
(301, 29, 'Kipkaren'),
(302, 29, 'Tinderet'),
(303, 30, 'Kabarnet'),
(304, 30, 'Marigat'),
(305, 30, 'Eldama Ravine'),
(306, 30, 'Mogotio'),
(307, 30, 'Kibingo'),
(308, 30, 'Baringo'),
(309, 30, 'Tangulbei'),
(310, 30, 'Nginyang'),
(311, 30, 'Loruk'),
(312, 31, 'Nanyuki'),
(313, 31, 'Rumuruti'),
(314, 31, 'Nyahururu'),
(315, 31, 'Kinamba'),
(316, 31, 'Doldol'),
(317, 31, 'Marmanet'),
(318, 31, 'Sosian'),
(319, 31, 'Ol Maisor'),
(320, 31, 'Kirima'),
(321, 32, 'Nakuru CBD'),
(322, 32, 'Naivasha'),
(323, 32, 'Molo'),
(324, 32, 'Gilgil'),
(325, 32, 'Njoro'),
(326, 32, 'Lanet'),
(327, 32, 'Rongai'),
(328, 32, 'Subukia'),
(329, 32, 'Salgaa'),
(330, 32, 'Elementaita'),
(331, 32, 'Mai Mahiu'),
(332, 32, 'Kuresoi'),
(333, 32, 'Elburgon'),
(334, 32, 'London'),
(335, 32, 'Bahati'),
(336, 32, 'Kabazi'),
(337, 32, 'Mbaruk'),
(338, 32, 'Mau Narok'),
(339, 33, 'Narok'),
(340, 33, 'Kilgoris'),
(341, 33, 'Suswa'),
(342, 33, 'Ololulung\'a'),
(343, 33, 'Ntulele'),
(344, 33, 'Lemek'),
(345, 33, 'Mara'),
(346, 33, 'Sekenani'),
(347, 33, 'Talek'),
(348, 33, 'Aitong'),
(349, 33, 'Lolgorien'),
(350, 33, 'Transmara'),
(351, 33, 'Enoosaen'),
(352, 34, 'Kajiado'),
(353, 34, 'Kitengela'),
(354, 34, 'Ngong'),
(355, 34, 'Ongata Rongai'),
(356, 34, 'Isinya'),
(357, 34, 'Namanga'),
(358, 34, 'Loitokitok'),
(359, 34, 'Kiserian'),
(360, 34, 'Kimana'),
(361, 34, 'Rombo'),
(362, 34, 'Oloitoktok'),
(363, 34, 'Mashuuru'),
(364, 34, 'Magadi'),
(365, 35, 'Kericho'),
(366, 35, 'Litein'),
(367, 35, 'Londiani'),
(368, 35, 'Kipkelion'),
(369, 35, 'Sotik'),
(370, 35, 'Kapsuser'),
(371, 35, 'Sigowet'),
(372, 35, 'Kapkatet'),
(373, 35, 'Fort Ternan'),
(374, 36, 'Bomet'),
(375, 36, 'Sotik'),
(376, 36, 'Longisa'),
(377, 36, 'Konoin'),
(378, 36, 'Chepalungu'),
(379, 36, 'Merigi'),
(380, 36, 'Mulot'),
(381, 36, 'Kapkoros'),
(382, 36, 'Kimulot'),
(383, 37, 'Kakamega'),
(384, 37, 'Mumias'),
(385, 37, 'Malava'),
(386, 37, 'Lugari'),
(387, 37, 'Butere'),
(388, 37, 'Shinyalu'),
(389, 37, 'Khayega'),
(390, 37, 'Ikolomani'),
(391, 37, 'Navakholo'),
(392, 37, 'Matungu'),
(393, 37, 'Lwandeti'),
(394, 37, 'Lubao'),
(395, 37, 'Kakamega Forest'),
(396, 38, 'Mbale'),
(397, 38, 'Luanda'),
(398, 38, 'Chavakali'),
(399, 38, 'Sabatia'),
(400, 38, 'Emuhaya'),
(401, 38, 'Vihiga'),
(402, 38, 'Wodanga'),
(403, 38, 'Kilingili'),
(404, 38, 'Mudete'),
(405, 39, 'Bungoma'),
(406, 39, 'Webuye'),
(407, 39, 'Kimilili'),
(408, 39, 'Chwele'),
(409, 39, 'Sirisia'),
(410, 39, 'Kanduyi'),
(411, 39, 'Tongaren'),
(412, 39, 'Bumula'),
(413, 39, 'Kibingei'),
(414, 39, 'Malakisi'),
(415, 39, 'Mukuyuni'),
(416, 39, 'Kimaeti'),
(417, 39, 'Nalondo'),
(418, 39, 'Kamukuywa'),
(419, 40, 'Busia'),
(420, 40, 'Malaba'),
(421, 40, 'Funyula'),
(422, 40, 'Nambale'),
(423, 40, 'Butula'),
(424, 40, 'Bunyala'),
(425, 40, 'Matayos'),
(426, 40, 'Budalangi'),
(427, 40, 'Teso'),
(428, 40, 'Amagoro'),
(429, 40, 'Nangina'),
(430, 40, 'Bujumba'),
(431, 41, 'Siaya'),
(432, 41, 'Bondo'),
(433, 41, 'Ugunja'),
(434, 41, 'Rarieda'),
(435, 41, 'Gem'),
(436, 41, 'Alego'),
(437, 41, 'Uranga'),
(438, 41, 'Sega'),
(439, 41, 'Yala'),
(440, 41, 'Lwak'),
(441, 41, 'Ndori'),
(442, 41, 'Usonga'),
(443, 41, 'Madiany'),
(444, 41, 'Boro'),
(445, 42, 'Kisumu CBD'),
(446, 42, 'Maseno'),
(447, 42, 'Ahero'),
(448, 42, 'Muhoroni'),
(449, 42, 'Kisian'),
(450, 42, 'Kombewa'),
(451, 42, 'Kibos'),
(452, 42, 'Miwani'),
(453, 42, 'Kogony'),
(454, 42, 'Manyatta'),
(455, 42, 'Migosi'),
(456, 42, 'Milimani'),
(457, 42, 'Kondele'),
(458, 42, 'Nyalenda'),
(459, 43, 'Homa Bay'),
(460, 43, 'Mbita'),
(461, 43, 'Oyugis'),
(462, 43, 'Rangwe'),
(463, 43, 'Kendu Bay'),
(464, 43, 'Sindo'),
(465, 43, 'Rod Kopany'),
(466, 43, 'Kosele'),
(467, 43, 'Magunga'),
(468, 43, 'Mbita Point'),
(469, 43, 'Mfangano'),
(470, 43, 'Rusinga'),
(471, 43, 'Sori'),
(472, 44, 'Migori'),
(473, 44, 'Rongo'),
(474, 44, 'Awendo'),
(475, 44, 'Kuria'),
(476, 44, 'Isebania'),
(477, 44, 'Kehancha'),
(478, 44, 'Masaba'),
(479, 44, 'Suna'),
(480, 44, 'Nyatike'),
(481, 44, 'Uriri'),
(482, 44, 'Taranta'),
(483, 44, 'Ranen'),
(484, 44, 'Kakrao'),
(485, 45, 'Kisii'),
(486, 45, 'Ogembo'),
(487, 45, 'Suneka'),
(488, 45, 'Nyamache'),
(489, 45, 'Keumbu'),
(490, 45, 'Marani'),
(491, 45, 'Daraja Mbili'),
(492, 45, 'Getembe'),
(493, 45, 'Mogonga'),
(494, 45, 'Nyansiongo'),
(495, 45, 'Kiogoro'),
(496, 45, 'Nyamarambe'),
(497, 45, 'Riana'),
(498, 46, 'Nyamira'),
(499, 46, 'Keroka'),
(500, 46, 'Ekerenyo'),
(501, 46, 'Manga'),
(502, 46, 'Magwagwa'),
(503, 46, 'Nyansiongo'),
(504, 46, 'Borabu'),
(505, 46, 'Kebirigo'),
(506, 46, 'Rigoma'),
(507, 46, 'Mochenwa'),
(508, 46, 'Kiangoso'),
(509, 46, 'Magombo'),
(510, 46, 'Senende'),
(511, 46, 'Mokwerero'),
(512, 47, 'Nairobi CBD'),
(513, 47, 'Westlands'),
(514, 47, 'Kilimani'),
(515, 47, 'Kileleshwa'),
(516, 47, 'South B'),
(517, 47, 'South C'),
(518, 47, 'Embakasi'),
(519, 47, 'Kasarani'),
(520, 47, 'Ruaka'),
(521, 47, 'Karen'),
(522, 47, 'Lang\'ata'),
(523, 47, 'Ongata Rongai'),
(524, 47, 'Syokimau'),
(525, 47, 'Donholm'),
(526, 47, 'Buruburu'),
(527, 47, 'Umoja'),
(528, 47, 'Komarock'),
(529, 47, 'Tena'),
(530, 47, 'Kawangware'),
(531, 47, 'Dagoretti'),
(532, 47, 'Kangemi'),
(533, 47, 'Lavington'),
(534, 47, 'Runda'),
(535, 47, 'Muthaiga'),
(536, 47, 'Gigiri'),
(537, 47, 'Ridgeways'),
(538, 47, 'Parklands'),
(539, 47, 'Spring Valley'),
(540, 47, 'Hurlingham'),
(541, 47, 'Milimani'),
(542, 47, 'Industrial Area'),
(543, 47, 'Pipeline'),
(544, 47, 'Fedha'),
(545, 47, 'Kariobangi'),
(546, 47, 'Dandora'),
(547, 47, 'Kayole'),
(548, 47, 'Mowlem'),
(549, 47, 'Utawala'),
(550, 47, 'Joska'),
(551, 1, 'nairobi');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `facebook_link` varchar(255) DEFAULT NULL,
  `twitter_link` varchar(255) DEFAULT NULL,
  `instagram_link` varchar(255) DEFAULT NULL,
  `last_login` timestamp NULL DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT 'default-avatar.png',
  `user_type` enum('user','admin','super_admin') DEFAULT 'user',
  `role` enum('user','admin','super_admin') DEFAULT 'user',
  `is_verified` tinyint(1) DEFAULT 0,
  `verification_badge` tinyint(1) DEFAULT 0,
  `subscription_plan` enum('bronze','silver','gold') DEFAULT 'bronze',
  `subscription_expiry` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` enum('active','suspended','deleted') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `password`, `phone`, `bio`, `facebook_link`, `twitter_link`, `instagram_link`, `last_login`, `profile_image`, `user_type`, `role`, `is_verified`, `verification_badge`, `subscription_plan`, `subscription_expiry`, `created_at`, `updated_at`, `status`) VALUES
(1, 'Jonathan Bosimwenda', 'josbosimwenda@gmail.com', '$2y$10$otK2H6dTCuhgFqviRybLie8dKqyrjqOBTsIN/IOFi4Kj2rCxjN0AC', '0851600109', 'HEY I AM JONATHA N BOSIMWENDA', '', '', '', '2026-05-28 11:21:04', '1779906934_1.png', 'super_admin', 'admin', 0, 0, 'silver', '2026-06-26', '2026-05-27 17:53:46', '2026-05-28 11:21:04', 'active'),
(2, 'John Bosi', 'Asantedivine55@gmail.com', '$2y$10$zdYME0WSYeBt6mAliMm0G.pl4FrPFXD6WxYXC2fw/2zuvqKyTNu5G', '0851600109', NULL, NULL, NULL, NULL, '2026-05-28 11:19:57', '1779915663_2.jpeg', 'admin', 'super_admin', 0, 0, 'bronze', NULL, '2026-05-27 20:23:44', '2026-05-28 11:19:57', 'active'),
(3, 'Admin User', 'admin@nyumbahub.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+254711223344', 'System Administrator', NULL, NULL, NULL, NULL, 'default-avatar.png', '', 'super_admin', 1, 1, 'gold', '2027-05-28', '2026-05-28 11:12:39', '2026-05-28 11:12:39', 'active');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `admin_settings`
--
ALTER TABLE `admin_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `counties`
--
ALTER TABLE `counties`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `favorites`
--
ALTER TABLE `favorites`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_favorite` (`user_id`,`property_id`),
  ADD KEY `property_id` (`property_id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `receiver_id` (`receiver_id`),
  ADD KEY `property_id` (`property_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `property_id` (`property_id`);

--
-- Indexes for table `phone_views`
--
ALTER TABLE `phone_views`
  ADD PRIMARY KEY (`id`),
  ADD KEY `property_id` (`property_id`);

--
-- Indexes for table `properties`
--
ALTER TABLE `properties`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `owner_id` (`owner_id`),
  ADD KEY `county_id` (`county_id`),
  ADD KEY `town_id` (`town_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_category` (`category`);
ALTER TABLE `properties` ADD FULLTEXT KEY `idx_search` (`title`,`description`);

--
-- Indexes for table `property_documents`
--
ALTER TABLE `property_documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `property_id` (`property_id`);

--
-- Indexes for table `property_images`
--
ALTER TABLE `property_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `property_id` (`property_id`);

--
-- Indexes for table `property_inquiries`
--
ALTER TABLE `property_inquiries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `property_id` (`property_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `property_videos`
--
ALTER TABLE `property_videos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `property_id` (`property_id`);

--
-- Indexes for table `property_views`
--
ALTER TABLE `property_views`
  ADD PRIMARY KEY (`id`),
  ADD KEY `property_id` (`property_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_review` (`user_id`,`property_id`),
  ADD KEY `property_id` (`property_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `subscriptions`
--
ALTER TABLE `subscriptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `payment_id` (`payment_id`);

--
-- Indexes for table `towns`
--
ALTER TABLE `towns`
  ADD PRIMARY KEY (`id`),
  ADD KEY `county_id` (`county_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `admin_settings`
--
ALTER TABLE `admin_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `counties`
--
ALTER TABLE `counties`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT for table `favorites`
--
ALTER TABLE `favorites`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `phone_views`
--
ALTER TABLE `phone_views`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `properties`
--
ALTER TABLE `properties`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `property_documents`
--
ALTER TABLE `property_documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `property_images`
--
ALTER TABLE `property_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `property_inquiries`
--
ALTER TABLE `property_inquiries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `property_videos`
--
ALTER TABLE `property_videos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `property_views`
--
ALTER TABLE `property_views`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `subscriptions`
--
ALTER TABLE `subscriptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `towns`
--
ALTER TABLE `towns`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=552;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `favorites`
--
ALTER TABLE `favorites`
  ADD CONSTRAINT `favorites_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `favorites_ibfk_2` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ibfk_3` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `phone_views`
--
ALTER TABLE `phone_views`
  ADD CONSTRAINT `phone_views_ibfk_1` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `properties`
--
ALTER TABLE `properties`
  ADD CONSTRAINT `properties_ibfk_1` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `properties_ibfk_2` FOREIGN KEY (`county_id`) REFERENCES `counties` (`id`),
  ADD CONSTRAINT `properties_ibfk_3` FOREIGN KEY (`town_id`) REFERENCES `towns` (`id`);

--
-- Constraints for table `property_documents`
--
ALTER TABLE `property_documents`
  ADD CONSTRAINT `property_documents_ibfk_1` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `property_images`
--
ALTER TABLE `property_images`
  ADD CONSTRAINT `property_images_ibfk_1` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `property_inquiries`
--
ALTER TABLE `property_inquiries`
  ADD CONSTRAINT `property_inquiries_ibfk_1` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `property_inquiries_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `property_videos`
--
ALTER TABLE `property_videos`
  ADD CONSTRAINT `property_videos_ibfk_1` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `property_views`
--
ALTER TABLE `property_views`
  ADD CONSTRAINT `property_views_ibfk_1` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `property_views_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `subscriptions`
--
ALTER TABLE `subscriptions`
  ADD CONSTRAINT `subscriptions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `subscriptions_ibfk_2` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`);

--
-- Constraints for table `towns`
--
ALTER TABLE `towns`
  ADD CONSTRAINT `towns_ibfk_1` FOREIGN KEY (`county_id`) REFERENCES `counties` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
