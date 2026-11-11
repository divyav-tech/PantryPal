# PantryPal

PantryPal is a first-year BTech Web Application Development project built with HTML5, CSS3, JavaScript, jQuery-free vanilla DOM/AJAX (the existing project did not load jQuery), Core PHP, MySQL and PDO. It runs locally using XAMPP. The existing Scrapbook UI v2 is preserved.

## Project structure

```text
PantryPal/
├── api/
│   ├── products.php          # Existing pantry CRUD API
│   ├── shopping.php          # MySQL shopping-list API
│   ├── account.php           # Profile and password updates
│   ├── meal-plans.php        # MySQL weekly meal-planner API
│   └── recipes.php           # TheMealDB search + saved recipe API
├── assets/
│   ├── css/
│   │   ├── style.css         # Main stylesheet; imports modules in cascade order
│   │   ├── variables.css
│   │   ├── core.css
│   │   ├── responsive-core.css
│   │   ├── scrapbook-theme.css
│   │   ├── responsive-theme.css
│   │   ├── scrapbook-components.css
│   │   ├── responsive.css
│   │   └── features.css
│   └── js/
│       ├── app.js
│       └── recipes.js
├── config/
│   └── db.php
├── database/
│   ├── migration_001_meal_plans.sql
│   └── migration_002_recipes.sql
├── index.php
├── login.php
├── register.php
└── logout.php
```

`index.php` is the active application. Use XAMPP/Apache to run it; do not open the PHP files by double-clicking them.

## XAMPP setup

1. Start **Apache** and **MySQL** in the XAMPP Control Panel.
2. Copy the `PantryPal` folder to `C:\xampp\htdocs\pantrypal`.
3. Open phpMyAdmin at `http://localhost/phpmyadmin`.
4. Select the existing database named `pantrypal_db`.
5. Back up the database before applying the migration (phpMyAdmin → database → Export).
6. Select `pantrypal_db`, open the **SQL** tab, and execute `database/migration_001_meal_plans.sql` and `database/migration_002_recipes.sql`.
   - Do **not** import the original database dump again over a populated database.
   - Do **not** add `shopping_items.category_id`: the supplied schema already contains that column and its index.
   - Migration 001 creates `meal_plans`; Migration 002 creates the saved `recipes` table. Both use `CREATE TABLE IF NOT EXISTS`.
7. Check `config/db.php`. The supplied local defaults are host `localhost`, database `pantrypal_db`, user `root`, and an empty password. Change these only if your local MySQL setup uses different credentials.
8. Visit `http://localhost/pantrypal/`. Log in with your existing account or register a new one.

## Implemented modules

### Existing authentication and pantry

- Existing registration, login, logout, password hashing and PHP session protection are retained.
- Existing `api/products.php` pantry CRUD is retained.
- Product API operations remain scoped to the session user.
- Pantry search, category/location/status filters and sorting remain in the existing interface.

### Shopping List

- Shopping items are stored in the existing `shopping_items` table.
- Add, edit, delete, purchased/unpurchased toggle, pending/purchased counts and clear-purchased operations use `api/shopping.php`.
- Items store user ID, optional pantry product ID, category ID, name, quantity, unit and purchased status.
- Low-stock suggestions use the user's own pantry products where quantity is at or below `min_stock`.
- A product already linked to any shopping-list row is not suggested again. Adding a suggestion checks ownership and prevents duplicate product links.
- The API uses prepared statements and checks session ownership.

### Recipe Book

- Search recipes by dish name using the public TheMealDB API.
- Open a recipe to view its image, cuisine, category, ingredients and instructions.
- Save discovered recipes to the logged-in user's Recipe Book and remove saved recipes.
- Saved recipes are available in the weekly Meal Planner dropdown.
- External API requests are proxied through `api/recipes.php`; the API requires a valid login session.

### Meal Planner

- The week view displays Monday to Sunday and Breakfast, Lunch, Snacks and Dinner.
- Use Previous/Next, This week or the date picker to navigate.
- Select a recipe from the existing in-project Recipe Book list or enter a custom meal.
- The `meal_plans` table stores the user, date, meal type, recipe source, recipe ID, recipe name and optional recipe details.
- Saved Recipe Book entries can be selected in the planner. Custom meal names can still be entered manually.
- Saving the same date/meal slot updates that slot instead of creating a duplicate.
- API reads, updates and deletes are scoped to the logged-in user.

### Expiry Monitor

- No expiry date, fresh (>7 days), expiring soon (1–7 days), expires today and expired are distinguished in the frontend.
- Use First sorts expired and soonest-expiring products to the top.
- Dashboard counts are calculated from the products returned by the database-backed pantry API.

### My Account

- Edit the account name and email address.
- Change the password after verifying the current password.
- Profile and password updates use `api/account.php`, prepared PDO statements and the active session user.
- Email addresses are checked for duplicates before saving.

### Removed module

- The Usage Tracker navigation, page, localStorage logic and event handlers have been removed from the application interface. The old `usage_logs` database table is left untouched so existing data is not deleted.

## Database migration details

The supplied database export already contains:
- `users`
- `categories`
- `products`
- `shopping_items` (including `category_id` and `idx_shopping_category`)
- `usage_logs`

The migrations add `meal_plans` and `recipes`. The recipes table stores the TheMealDB ID, title, category, area, instructions, ingredients, image and video link, and belongs to the logged-in user.
- `plan_id` primary key
- `user_id` foreign key to `users` with `ON DELETE CASCADE`
- `meal_date`
- `meal_type` (`breakfast`, `lunch`, `snacks`, `dinner`)
- `recipe_source`, `recipe_id`, `recipe_name`, `recipe_details`
- A unique key on (`user_id`, `meal_date`, `meal_type`)

## Quick test checklist

After importing the migration and opening the site:

1. Log in and confirm the dashboard loads.
2. Add, edit, search, filter, sort and delete a pantry product.
3. Confirm today's expiry, within-seven-days, expired and no-expiry products display as expected.
4. Add a shopping item with quantity, unit and category.
5. Edit it, mark it purchased, unmark it, delete it and clear purchased items.
6. Set a pantry product's `min_stock` above its current quantity; confirm it appears under Running low.
7. Add the suggestion to the shopping list; confirm it disappears from suggestions.
8. Add meals to different dates and all four meal types.
9. Navigate to another week and back; confirm the meal remains saved.
10. Edit and remove a meal.
11. Log out and confirm the dashboard and APIs require login.
12. Register a second test account and confirm its pantry, shopping list and meal plans are separate.

## Testing status and limitations

The source files have been checked for PHP syntax errors and the JavaScript file for syntax errors. The stylesheet import targets and duplicate HTML IDs were checked. The shopping and meal APIs were also requested without a login session and returned HTTP 401 as expected.

A full MySQL/XAMPP integration test could not be run in the build environment because it does not have the `pdo_mysql` PHP extension or a running MySQL/MariaDB service. Please run the checklist above on your local XAMPP installation before submitting the project. Do not describe the database CRUD as locally verified until these tests pass.

## Short WAD viva notes

- **PHP session:** `session_start()` resumes the user's session. The `user_id` stored after login identifies the current user on protected pages and APIs.
- **Password security:** `password_hash()` stores a one-way password hash; `password_verify()` checks a submitted password against that hash.
- **PDO:** PHP Data Objects provides a consistent database interface. `prepare()` creates a parameterised SQL statement and `execute()` supplies values, reducing SQL injection risk.
- **AJAX/fetch:** JavaScript sends HTTP requests to PHP APIs without reloading the whole page. The APIs return JSON; JavaScript updates the relevant section.
- **HTTP methods:** GET reads records, POST adds or saves, PUT edits, PATCH changes purchased status, and DELETE removes records.
- **Foreign key:** `meal_plans.user_id` references `users.user_id`; deleting a user cascades to that user's meal plans.
- **Unique composite key:** (`user_id`, `meal_date`, `meal_type`) allows only one meal in each meal slot per user and date.
- **Ownership check:** API SQL includes the session user's ID in SELECT/UPDATE/DELETE conditions so one account cannot modify another account's records.
- **localStorage vs MySQL:** localStorage belongs to one browser; MySQL stores records on the server and associates them with a user account.
