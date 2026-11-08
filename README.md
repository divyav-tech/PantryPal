# 🥑 PantryPal — Your Little Pantry Journal

**A scrapbook-inspired pantry and meal management web application that helps you organise groceries, track expiry dates, plan meals, and discover recipes — all in one place.**

## 🌿 About the Project

PantryPal is a full-stack web application designed to make everyday kitchen and grocery management easier.

From keeping track of pantry items and monitoring expiry dates to managing shopping lists and planning weekly meals, PantryPal brings essential kitchen organisation tools together in one place.

The project combines practical functionality with a warm, pastel-inspired scrapbook design to make managing your pantry feel a little more enjoyable.

## ✨ Features

### 🏡 Dashboard
- Overview of pantry items and their status.
- Quick access to important sections.
- Expiry-related summaries and useful reminders.

### 🥫 My Pantry
- Add, edit, and delete pantry items.
- Organise products by category.
- Search, filter, and sort pantry items.
- Keep track of product quantities and expiry dates.

### ⏳ Expiry Monitor
- Track products approaching their expiry dates.
- Identify expired and expiring-soon items.
- Prioritise products that should be used first.

### 🛒 Shopping List
- Create and manage grocery shopping lists.
- Add, update, and remove shopping items.
- Keep track of items you need to purchase.

### 📅 Meal Planner
- Plan meals across the week.
- Organise recipes and meal ideas using a weekly calendar.

### 📖 Recipe Book
- Discover recipes using TheMealDB API.
- Search for different recipes.
- Explore recipe details, ingredients, and instructions.
- Browse vegetarian and non-vegetarian options.
- Save favourite recipes for later.

### 👤 My Account
- User registration and login.
- Session-based authentication.
- Personal account management.

## 🧰 Tech Stack

| Technology | Purpose |
|---|---|
| HTML5 | Application structure |
| CSS3 | Styling and responsive layouts |
| JavaScript | Interactive functionality |
| jQuery | DOM manipulation and AJAX |
| PHP | Backend logic and API handling |
| MySQL | Database management |
| PDO | Secure database interactions |
| TheMealDB API | Recipe data and discovery |

## 🎨 Design Philosophy

PantryPal uses a warm, scrapbook-inspired visual identity.

- Soft pastel colour palette.
- Hand-painted illustrations and decorative elements.
- Friendly typography and paper-inspired details.
- A clean, organised interface designed around everyday kitchen activities.

The goal was to create something that feels personal and welcoming rather than like a conventional management dashboard.

## ⚙️ Getting Started

Follow these steps to run PantryPal locally.

### Prerequisites

Make sure you have installed:

- [XAMPP](https://www.apachefriends.org/)
- A modern web browser
- A code editor such as VS Code

### 1. Clone the Repository

```bash
git clone https://github.com/divyav-tech/PantryPal.git
```

### 2. Move the Project

Place the project folder inside your XAMPP `htdocs` directory.

```text
C:\xampp\htdocs\pantrypal
```

### 3. Start XAMPP

Open the XAMPP Control Panel and start:

- Apache
- MySQL

### 4. Set Up the Database

1. Open `http://localhost/phpmyadmin/`.
2. Create a database named `pantrypal_db`.
3. Import the project's database SQL file, if included, or apply the supplied database migrations.
4. Check the database configuration in `config/db.php` and update the credentials if required.

### 5. Run the Application

Open your browser and visit:

```text
http://localhost/pantrypal/
```

Register an account and start exploring PantryPal!

## 📁 Project Structure

```text
PantryPal/
│
├── api/
│   ├── account.php
│   ├── meal-plans.php
│   ├── products.php
│   ├── recipes.php
│   └── shopping.php
│
├── assets/
│   ├── css/
│   ├── js/
│   └── images/
│
├── config/
│   └── db.php
│
├── database/
│   ├── migration_001_meal_plans.sql
│   └── migration_002_recipes.sql
│
├── index.php
├── login.php
├── register.php
├── logout.php
└── recipes.php
```

## 🔐 Security

The application incorporates:
- PHP session-based authentication.
- Password hashing and verification.
- PDO prepared statements for database queries.
- Server-side handling of application data.

## 🌱 What I Learned

Building PantryPal helped me gain practical experience with:

- Connecting a frontend to a PHP backend.
- Designing and managing a relational MySQL database.
- Implementing CRUD operations.
- Working with AJAX and jQuery.
- Integrating a third-party public API.
- Managing user authentication and sessions.
- Organising a multi-page web application.
- Debugging and improving an application through testing.

## 🚀 Future Scope

Some ideas that could be explored in future versions:

- Smart grocery recommendations.
- More personalised meal suggestions.
- Advanced pantry analytics.
- Additional recipe discovery options.

## 💚 A Little Note

PantryPal was built as a hands-on web development project to explore how frontend design, backend development, databases, and APIs come together to create a functional application.

**Made with care, code, and a little love for organised kitchens. 🥑**

