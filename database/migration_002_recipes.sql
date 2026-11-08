-- Migration 002: persistent Recipe Book favourites from TheMealDB
-- Run once in the existing pantrypal_db database.
CREATE TABLE IF NOT EXISTS recipes (
    id INT AUTO_INCREMENT,
    user_id INT NOT NULL,
    mealdb_id VARCHAR(50) NULL,
    title VARCHAR(255) NOT NULL,
    category VARCHAR(100) DEFAULT 'General',
    area VARCHAR(100) DEFAULT 'International',
    instructions MEDIUMTEXT NOT NULL,
    ingredients LONGTEXT NOT NULL,
    image_url VARCHAR(500) NULL,
    youtube_url VARCHAR(500) NULL,
    is_favorite TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_recipe_user_mealdb (user_id, mealdb_id),
    KEY idx_recipe_user (user_id),
    CONSTRAINT fk_recipes_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
