-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 10, 2026 at 07:05 PM
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
-- Database: `pantrypal_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(60) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`category_id`, `category_name`) VALUES
(28, 'Bakery'),
(29, 'Beverages'),
(27, 'Dairy'),
(32, 'Frozen Foods'),
(1, 'Fruits'),
(33, 'Grain and Pulses'),
(31, 'Meat and Seafood'),
(34, 'Others'),
(30, 'Snacks '),
(26, 'Vegetables');

-- --------------------------------------------------------

--
-- Table structure for table `meal_plans`
--

CREATE TABLE `meal_plans` (
  `plan_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `meal_date` date NOT NULL,
  `meal_type` enum('breakfast','lunch','snacks','dinner') NOT NULL,
  `recipe_source` varchar(20) NOT NULL DEFAULT 'manual',
  `recipe_id` varchar(100) DEFAULT NULL,
  `recipe_name` varchar(160) NOT NULL,
  `recipe_details` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `meal_plans`
--

INSERT INTO `meal_plans` (`plan_id`, `user_id`, `meal_date`, `meal_type`, `recipe_source`, `recipe_id`, `recipe_name`, `recipe_details`, `created_at`, `updated_at`) VALUES
(1, 1, '2026-11-10', 'breakfast', 'pantrypal', '2', 'Creamy Tomato Soup', NULL, '2026-11-09 18:08:14', '2026-11-09 18:08:14'),
(2, 1, '2026-11-10', 'snacks', 'manual', NULL, 'Cookies', NULL, '2026-11-09 18:08:25', '2026-11-09 18:08:25'),
(3, 1, '2026-11-10', 'lunch', 'manual', NULL, 'Vegetable Pulao', NULL, '2026-11-09 18:08:36', '2026-11-09 18:08:36'),
(4, 1, '2026-11-10', 'dinner', 'manual', NULL, 'Chickpeas salad', NULL, '2026-11-09 18:08:47', '2026-11-09 18:08:47');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `product_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `product_name` varchar(120) NOT NULL,
  `brand` varchar(100) DEFAULT NULL,
  `quantity` decimal(10,2) NOT NULL DEFAULT 1.00,
  `unit` varchar(30) NOT NULL DEFAULT 'piece',
  `purchase_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `min_stock` decimal(10,2) DEFAULT 0.00,
  `storage_location` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`product_id`, `user_id`, `category_id`, `product_name`, `brand`, `quantity`, `unit`, `purchase_date`, `expiry_date`, `min_stock`, `storage_location`, `notes`, `created_at`, `updated_at`) VALUES
(3, 1, 26, 'cherry tomatoes', NULL, 1.00, 'kg', NULL, NULL, 0.00, 'fridge', NULL, '2026-11-08 08:55:44', '2026-11-08 08:55:59'),
(4, 1, 27, 'Butter', NULL, 0.50, 'piece', NULL, NULL, 1.00, NULL, NULL, '2026-11-08 08:57:02', '2026-11-08 08:57:59'),
(5, 1, 28, 'Bread', NULL, 1.00, 'piece', '2026-11-09', NULL, 0.00, NULL, NULL, '2026-11-09 18:03:04', '2026-11-09 18:03:04'),
(6, 1, 26, 'Cilantro', NULL, 1.00, 'piece', '2026-11-08', '2026-11-10', 0.00, NULL, NULL, '2026-11-09 18:04:14', '2026-11-09 18:04:14');

-- --------------------------------------------------------

--
-- Table structure for table `recipes`
--

CREATE TABLE `recipes` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `mealdb_id` varchar(50) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `category` varchar(100) DEFAULT 'General',
  `area` varchar(100) DEFAULT 'International',
  `instructions` text NOT NULL,
  `ingredients` text NOT NULL,
  `image_url` varchar(500) DEFAULT NULL,
  `youtube_url` varchar(500) DEFAULT NULL,
  `is_favorite` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `recipes`
--

INSERT INTO `recipes` (`id`, `user_id`, `mealdb_id`, `title`, `category`, `area`, `instructions`, `ingredients`, `image_url`, `youtube_url`, `is_favorite`, `created_at`) VALUES
(1, 1, '52777', 'Mediterranean Pasta Salad', 'Seafood', 'Italian', 'Bring a large saucepan of salted water to the boil\r\nAdd the pasta, stir once and cook for about 10 minutes or as directed on the packet.\r\nMeanwhile, wash the tomatoes and cut into quarters. Slice the olives. Wash the basil.\r\nPut the tomatoes into a salad bowl and tear the basil leaves over them. Add a tablespoon of olive oil and mix.\r\nWhen the pasta is ready, drain into a colander and run cold water over it to cool it quickly.\r\nToss the pasta into the salad bowl with the tomatoes and basil.\r\nAdd the sliced olives, drained mozzarella balls, and chunks of tuna. Mix well and let the salad rest for at least half an hour to allow the flavours to mingle.\r\nSprinkle the pasta with a generous grind of black pepper and drizzle with the remaining olive oil just before serving.', '[\"200 g mozzarella balls\",\"250 g baby plum tomatoes\",\"1  bunch fresh basil\",\"350 g farfalle\",\"3  tablespoons extra virgin olive oil\",\"40 g Green Olives\",\"200 g tuna\",\"to taste salt\",\"to taste pepper\"]', 'https://www.themealdb.com/images/media/meals/wvqpwt1468339226.jpg', 'https://www.youtube.com/watch?v=e52IL8zYmaE', 0, '2026-11-08 08:53:59'),
(2, 1, '52841', 'Creamy Tomato Soup', 'Starter', 'British', 'Put the oil, onions, celery, carrots, potatoes and bay leaves in a big casserole dish, or two saucepans. Fry gently until the onions are softened – about 10-15 mins. Fill the kettle and boil it.\r\nStir in the tomato purée, sugar, vinegar, chopped tomatoes and passata, then crumble in the stock cubes. Add 1 litre boiling water and bring to a simmer. Cover and simmer for 15 mins until the potato is tender, then remove the bay leaves. Purée with a stick blender (or ladle into a blender in batches) until very smooth. Season to taste and add a pinch more sugar if it needs it. The soup can now be cooled and chilled for up to 2 days, or frozen for up to 3 months.\r\nTo serve, reheat the soup, stirring in the milk – try not to let it boil. Serve in small bowls with cheesy sausage rolls.', '[\"3 tbsp Olive Oil\",\"2 chopped Onions\",\"2 sticks Celery\",\"300g Carrots\",\"500g Potatoes\",\"4 Bay Leaf\",\"5 tblsp Tomato Puree\",\"2 tblsp Sugar\",\"2 tblsp White Vinegar\",\"1½ kg Chopped Tomatoes\",\"500g Passata\",\"3 Vegetable Stock Cube\",\"400ml Whole Milk\"]', 'https://www.themealdb.com/images/media/meals/stpuws1511191310.jpg', 'https://www.youtube.com/watch?v=lBhwjjUiFk4', 0, '2026-11-08 08:54:31');

-- --------------------------------------------------------

--
-- Table structure for table `shopping_items`
--

CREATE TABLE `shopping_items` (
  `item_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `item_name` varchar(120) NOT NULL,
  `quantity` decimal(10,2) DEFAULT 1.00,
  `unit` varchar(30) DEFAULT 'piece',
  `is_purchased` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `shopping_items`
--

INSERT INTO `shopping_items` (`item_id`, `user_id`, `product_id`, `category_id`, `item_name`, `quantity`, `unit`, `is_purchased`, `created_at`) VALUES
(1, 1, NULL, 26, 'Tomoatoes', 1.00, 'kg', 1, '2026-11-07 15:50:48'),
(2, 1, NULL, NULL, 'Onion', 1.00, 'piece', 1, '2026-11-09 18:05:01'),
(3, 1, NULL, NULL, 'Banana', 1.00, 'piece', 0, '2026-11-09 18:05:12'),
(4, 1, NULL, NULL, 'Tomato Ketchup', 1.00, 'piece', 0, '2026-11-09 18:05:19'),
(5, 1, NULL, NULL, 'Olive oil', 1.00, 'piece', 0, '2026-11-09 18:05:27');

-- --------------------------------------------------------

--
-- Table structure for table `usage_logs`
--

CREATE TABLE `usage_logs` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `product_name` varchar(120) NOT NULL,
  `action_type` varchar(20) NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `unit` varchar(30) NOT NULL,
  `notes` text DEFAULT NULL,
  `logged_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `name`, `email`, `password_hash`, `created_at`) VALUES
(1, 'Test', 'test@gmail.com', '$2y$10$D2fqWoGtRf1vhphnngfHoeUauxbiygLVukQ8IdSlJT7Tnse8U4oXS', '2026-11-07 06:15:32');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`category_id`),
  ADD UNIQUE KEY `category_name` (`category_name`);

--
-- Indexes for table `meal_plans`
--
ALTER TABLE `meal_plans`
  ADD PRIMARY KEY (`plan_id`),
  ADD UNIQUE KEY `uq_meal_user_date_type` (`user_id`,`meal_date`,`meal_type`),
  ADD KEY `idx_meal_date` (`meal_date`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_category_id` (`category_id`);

--
-- Indexes for table `recipes`
--
ALTER TABLE `recipes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_recipes_user` (`user_id`);

--
-- Indexes for table `shopping_items`
--
ALTER TABLE `shopping_items`
  ADD PRIMARY KEY (`item_id`),
  ADD KEY `idx_shopping_user` (`user_id`),
  ADD KEY `fk_shopping_product` (`product_id`),
  ADD KEY `idx_shopping_category` (`category_id`);

--
-- Indexes for table `usage_logs`
--
ALTER TABLE `usage_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `idx_usage_user` (`user_id`),
  ADD KEY `idx_usage_product` (`product_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT for table `meal_plans`
--
ALTER TABLE `meal_plans`
  MODIFY `plan_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `recipes`
--
ALTER TABLE `recipes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `shopping_items`
--
ALTER TABLE `shopping_items`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `usage_logs`
--
ALTER TABLE `usage_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `meal_plans`
--
ALTER TABLE `meal_plans`
  ADD CONSTRAINT `fk_meal_plans_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`),
  ADD CONSTRAINT `fk_products_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `recipes`
--
ALTER TABLE `recipes`
  ADD CONSTRAINT `fk_recipes_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `shopping_items`
--
ALTER TABLE `shopping_items`
  ADD CONSTRAINT `fk_shopping_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_shopping_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `usage_logs`
--
ALTER TABLE `usage_logs`
  ADD CONSTRAINT `fk_usage_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_usage_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
