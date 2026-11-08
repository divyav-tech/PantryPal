<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PantryPal - My Recipe Scrapbook</title>
    <link rel="stylesheet" href="assets/css/core.css">
    <link rel="stylesheet" href="assets/css/scrapbook-recipes.css">
</head>
<body>
    <div class="scrapbook-container">
        <header class="scrapbook-header">
            <h1 class="scrapbook-title">📖 My Recipe Scrapbook</h1>
            <p style="font-family: var(--font-handwriting); font-size: 1.4rem; color: #5d4037; margin-top: 10px;">
                Discover, pin, and collect your favorite meals!
            </p>

            <div style="margin-top: 1.5rem; display: flex; justify-content: center; gap: 10px;">
                <input type="text" id="recipe-search-input" placeholder="Search meals (e.g. Pasta, Chicken, Tacos)..." style="padding: 10px 15px; width: 320px; border: 1px solid #ccc; border-radius: 20px; font-size: 1rem;">
                <button id="search-recipe-btn" style="padding: 10px 20px; background: #8d6e63; color: white; border: none; border-radius: 20px; cursor: pointer;">🔍 Search</button>
                <button id="random-recipe-btn" style="padding: 10px 20px; background: #ffb74d; color: #3e2723; border: none; border-radius: 20px; cursor: pointer; font-weight: bold;">🎲 Surprise Me!</button>
            </div>
        </header>

        <main>
            <div id="recipe-grid" class="scrapbook-grid">
                <!-- Polaroid Recipe Cards loaded dynamically -->
            </div>
        </main>
    </div>

    <!-- Recipe Detail Journal Modal -->
    <div id="recipe-modal" class="recipe-modal">
        <div class="recipe-journal-page">
            <button class="close-modal-btn">&times;</button>
            <div id="modal-recipe-content"></div>
        </div>
    </div>

    <script src="assets/js/recipes.js"></script>
</body>
</html>
