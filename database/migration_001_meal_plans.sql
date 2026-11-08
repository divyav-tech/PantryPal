-- PantryPal migration 001
-- Existing shopping_items.category_id and idx_shopping_category were verified
-- in the supplied database export, so no shopping table alteration is needed.
-- This migration only adds persistent weekly meal planning.

CREATE TABLE IF NOT EXISTS `meal_plans` (
  `plan_id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `meal_date` DATE NOT NULL,
  `meal_type` ENUM('breakfast','lunch','snacks','dinner') NOT NULL,
  `recipe_source` VARCHAR(20) NOT NULL DEFAULT 'manual',
  `recipe_id` VARCHAR(100) DEFAULT NULL,
  `recipe_name` VARCHAR(160) NOT NULL,
  `recipe_details` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`plan_id`),
  UNIQUE KEY `uq_meal_user_date_type` (`user_id`,`meal_date`,`meal_type`),
  KEY `idx_meal_date` (`meal_date`),
  CONSTRAINT `fk_meal_plans_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
