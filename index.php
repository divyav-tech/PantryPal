<!DOCTYPE html>
<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}
?>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PantryPal — Your little pantry journal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Caveat:wght@500;600;700&family=DM+Sans:wght@400;500;600;700&family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&display=swap"
        rel="stylesheet">
    <script src="https://kit.fontawesome.com/78652ec636.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <div class="app-shell">
        <aside class="sidebar" id="sidebar">
            <a class="brand" href="#" data-page="dashboard"><span class="brand-mark"><i
                        class="fa-solid fa-leaf"></i></span><span>pantry<span class="brand-pal">pal</span><small>your
                        little pantry journal</small></span></a>
            <div class="side-label">YOUR SPACE</div>
            <nav class="nav-list">
                <button class="nav-link active" data-page="dashboard"><span><i class="fa-solid fa-house"></i></span> My
                    home</button>
                <button class="nav-link" data-page="pantry"><span><i class="fa-solid fa-box-open"></i></span> My
                    pantry</button>
                <button class="nav-link" data-page="expiry"><span><i class="fa-solid fa-clock"></i></span> Use it
                    soon</button>
                <button class="nav-link" data-page="shopping"><span><i class="fa-solid fa-cart-shopping"></i></span>
                    Shopping list</button>
                <button class="nav-link" data-page="recipes"><span><i class="fa-solid fa-book-open"></i></span> Recipe
                    book</button>
                <button class="nav-link" data-page="planner"><span><i class="fa-solid fa-calendar-days"></i></span> Meal
                    planner</button>
                <button class="nav-link" data-page="account"><span><i class="fa-solid fa-circle-user"></i></span> My
                    account</button>
            </nav>
            <div class="sidebar-note"><span class="note-doodle">🌼</span>
                <p class="hand">A little reminder</p>
                <p>Use what you have, love what you make, waste a little less.</p>
            </div>
        </aside>
        <main class="main-area">
            <header class="topbar"><button class="mobile-menu" id="mobileMenu">☰</button>
                <div class="breadcrumb">YOUR KITCHEN <span>/</span> <b id="crumb">MY HOME</b></div>
                <div class="top-actions"><span class="today" id="todayLabel"></span><button class="icon-btn"
                        title="Notifications">♧</button><a class="avatar small account-avatar-link" href="#page-account" data-page="account" aria-label="Open My Account" title="My Account"><?= htmlspecialchars(strtoupper(substr($_SESSION["user_name"] ?? "P", 0, 1)), ENT_QUOTES, "UTF-8") ?></a></div>
            </header>

            <section class="page active" id="page-dashboard">
                <div class="welcome-grid">
                    <div class="welcome-copy">
                        <h1>Your pantry,<br><em>your happy place.</em></h1>
                        <p class="lead">A little look at what's tucked away in your kitchen today.</p><button
                            class="primary-btn" data-go="pantry">Explore my pantry <span>→</span></button>
                    </div>
                    <div class="pantry-photo photo-frame"><img
                            src="https://images.unsplash.com/photo-1608686207856-001b95cf60ca?w=1200&h=900&fit=crop"
                            alt="A warm, organised kitchen pantry shelf">
                        <div class="photo-caption"><span>YOUR KITCHEN, YOUR WAY</span><b>A pantry full of
                                possibilities.</b></div>
                    </div>
                </div>
                <div class="section-heading">
                    <div>
                        <h2>Your kitchen snapshot</h2>
                    </div>
                </div>
                <div class="stats-grid">
                    <article class="stat-card stat-cream"><span class="stat-icon"><i
                                class="fa-solid fa-boxes-stacked"></i></span><span class="stat-label">In your
                            pantry</span><strong id="statTotal">32</strong><small>products tucked away</small></article>
                    <article class="stat-card stat-yellow"><span class="stat-icon"><i
                                class="fa-solid fa-hourglass-half"></i></span><span class="stat-label">Use these
                            soon</span><strong id="statSoon">05</strong><small>freshness needs a little love</small>
                    </article>
                    <article class="stat-card stat-pink"><span class="stat-icon"><i
                                class="fa-solid fa-triangle-exclamation"></i></span><span class="stat-label">Past their
                            date</span><strong id="statExpired">02</strong><small>check these items</small></article>
                    <article class="stat-card stat-blue"><span class="stat-icon"><i
                                class="fa-solid fa-cart-shopping"></i></span><span class="stat-label">Running
                            low</span><strong id="statLow">04</strong><small>add to your next shop</small></article>
                </div>
                <div class="visual-gallery" aria-label="Pantry inspiration">
                    <figure class="gallery-large"><img
                            src="https://images.unsplash.com/photo-1543168256-418811576931?w=1000&h=700&fit=crop"
                            alt="Fresh market groceries">
                        <figcaption>Fresh from the market</figcaption>
                    </figure>
                    <figure class="gallery-tall"><img
                            src="https://images.unsplash.com/photo-1490645935967-10de6ba17061?w=700&h=900&fit=crop"
                            alt="Colourful wholesome meal">
                        <figcaption>Good things to eat</figcaption>
                    </figure>
                    <figure class="gallery-small"><img
                            src="https://images.unsplash.com/photo-1547592180-85f173990554?w=700&h=500&fit=crop"
                            alt="Fresh ingredients">
                        <figcaption>Everyday ingredients</figcaption>
                    </figure>
                </div>
                <div class="content-grid">
                    <section class="paper-panel">
                        <div class="panel-title">
                            <div>
                                <h2>Use first <span class="hand">♡</span></h2>
                            </div><button class="text-btn" data-go="expiry">See all →</button>
                        </div>
                        <div id="dashboardExpiry"></div>
                    </section>
                    <section class="quote-panel">
                        <p class="quote">“Make room for good food and fewer forgotten ingredients.”</p><button
                            class="outline-btn" data-go="recipes">Find something to make ↗</button>
                    </section>
                </div>
                <div class="section-heading compact">
                    <div>
                        <h2>Recently added</h2>
                    </div><button class="text-btn" data-go="pantry">View pantry →</button>
                </div>
                <div class="mini-products" id="recentProducts"></div>
            </section>

            <section class="page" id="page-pantry">
                <div class="page-intro">
                    <div>
                        <h1>My <em>pantry.</em></h1>
                        <p>Everything you have, all in one little place.</p>
                    </div>
                    <div class="intro-photo pantry-intro-photo"><img
                            src="https://images.unsplash.com/photo-1608686207856-001b95cf60ca?w=700&h=450&fit=crop"
                            alt="Organised pantry shelves"></div><button class="primary-btn" id="addProductBtn">＋ Add a
                        product</button>
                </div>
                <div class="filter-bar"><label class="search-box">⌕ <input id="productSearch"
                            placeholder="Find something in your pantry..."></label><select id="categoryFilter">
                        <option value="all">All categories</option>
                    </select><select id="locationFilter">
                        <option value="all">All locations</option>
                    </select><select id="statusFilter">
                        <option value="all">All statuses</option>
                        <option value="fresh">Fresh</option>
                        <option value="soon">Expiring soon</option>
                        <option value="today">Expires today</option>
                        <option value="expired">Expired</option>
                        <option value="low">Low stock</option>
                    </select><select id="sortFilter">
                        <option value="name">Name A–Z</option>
                        <option value="expiry">Soonest expiry</option>
                        <option value="quantity">Quantity: low to high</option>
                        <option value="quantity-desc">Quantity: high to low</option>
                    </select></div>
                <div class="category-chips" id="categoryChips"><button class="chip active"
                        data-category="all">Everything</button></div>
                <div class="inventory-grid" id="inventoryGrid"></div>
            </section>

            <section class="page" id="page-expiry">
                <div class="page-intro">
                    <div>
                        <h1>Use it <em>soon.</em></h1>
                        <p>Give these ingredients a starring role before their time is up.</p>
                    </div>
                    <div class="intro-photo expiry-photo"><img
                            src="https://images.unsplash.com/photo-1540420773420-3366772f4999?w=600&h=420&fit=crop"
                            alt="Fresh vegetables ready to use"></div>
                </div>
                <div class="expiry-banner"><span><i class="fa-solid fa-leaf"></i></span>
                    <div><b>A little planning goes a long way.</b>
                        <p>Use First brings the soonest-to-expire items to the front.</p>
                    </div>
                </div>
                <div class="expiry-columns">
                    <div>
                        <h2>Use first <span class="hand">♡</span></h2>
                        <div id="expiryList" class="expiry-list"></div>
                    </div>
                    <aside class="tip-card"><span class="tip-label">PANTRY TIP</span><img class="tip-illustration"
                            src="https://images.unsplash.com/photo-1540420773420-3366772f4999?w=600&h=400&fit=crop"
                            alt="Fresh vegetables">
                        <h3>Store smart, waste less.</h3>
                        <p>Keep foods that need using soon at eye level in your fridge. A tiny reminder can make a big
                            difference!</p>
                    </aside>
                </div>
            </section>

            <section class="page" id="page-shopping">
                <div class="page-intro">
                    <div>
                        <h1>The shopping <em>list.</em></h1>
                        <p>Your little checklist for the next grocery run.</p>
                    </div>
                    <div class="shopping-hero-image"><img
                            src="https://images.unsplash.com/photo-1542838132-92c53300491e?w=900&h=600&fit=crop"
                            alt="Fresh groceries in a shopping basket"></div>
                </div>
                <div class="shopping-layout">
                    <section class="shopping-paper">
                        <div class="paper-top"><strong>Grocery checklist</strong><span><i
                                    class="fa-solid fa-basket-shopping"></i></span></div>
                        <form class="add-item shopping-form" id="shoppingForm">
                            <input id="shoppingInput" maxlength="120" placeholder="Add something to the list..."
                                required>
                            <input id="shoppingQty" type="number" min="0.01" step="0.01" value="1"
                                aria-label="Quantity">
                            <input id="shoppingUnit" maxlength="30" value="piece" aria-label="Unit" placeholder="Unit">
                            <select id="shoppingCategory" aria-label="Category">
                                <option value="">No category</option>
                            </select>
                            <button class="primary-btn" id="addShoppingBtn" type="submit">Add item ＋</button>
                        </form>
                        <div class="shopping-summary"><span>Pending <b
                                    id="shoppingPendingCount">0</b></span><span>Purchased <b
                                    id="shoppingBoughtCount">0</b></span><label>Show <select id="shoppingFilter">
                                    <option value="all">All items</option>
                                    <option value="pending">Pending</option>
                                    <option value="purchased">Purchased</option>
                                </select></label><label>Category <select id="shoppingCategoryFilter">
                                    <option value="all">All categories</option>
                                </select></label></div>
                        <div class="shopping-columns">
                            <section>
                                <h3>To buy <span class="hand">♡</span></h3>
                                <div id="shoppingPending" class="shopping-items"></div>
                            </section>
                            <section>
                                <h3>In the basket</h3>
                                <div id="shoppingBought" class="shopping-items"></div>
                            </section>
                        </div>
                        <p id="shoppingMessage" class="feature-message" role="status" aria-live="polite"></p>
                        <div class="shopping-footer"><span>YOU’VE GOT THIS</span><button class="text-btn"
                                id="clearBought" type="button">Clear purchased items</button></div>
                        <section class="low-stock-panel">
                            <div class="panel-title">
                                <div>
                                    <h2>Running low <span class="hand">✿</span></h2>
                                    <p>Pantry items at or below their minimum stock.</p>
                                </div>
                            </div>
                            <div id="lowStockSuggestions" class="low-stock-suggestions"></div>
                        </section>
                    </section>
                    <aside class="shopping-side">
                        <div class="side-illustration"><img
                                src="https://images.unsplash.com/photo-1543168256-418811576931?w=600&h=440&fit=crop"
                                alt="A basket of fresh groceries"></div>
                        <h3>A quick reminder</h3>
                        <p>Check your pantry before you shop. You might already have what you need!</p><button
                            class="outline-btn" data-go="pantry">Peek in my pantry →</button>
                    </aside>
                </div>
            </section>

            <section class="page" id="page-recipes">
                <div class="recipe-cover">
                    <div class="recipe-cover-copy"><span class="eyebrow">THE PANTRYPAL RECIPE BOOK</span>
                        <h1>What's cooking<br><em>today?</em></h1>
                        <p>Find something lovely to make with ingredients you already have.</p>
                        <div class="recipe-search-row"><label class="search-box recipe-search">⌕ <input
                                    id="recipeSearch" type="search"
                                    placeholder="Search a dish or ingredient..."></label><button class="primary-btn"
                                id="searchRecipesButton" type="button">Search recipes</button></div>
                        <div id="recipeSuggestions" class="recipe-suggestions" role="listbox" aria-label="Recipe suggestions" hidden></div>
                        <div class="recipe-actions"><button class="outline-btn" id="randomRecipeButton"
                                type="button">Surprise me</button><button class="text-btn" id="showSavedRecipes"
                                type="button">My saved recipes</button></div>
                    </div>
                    <div class="recipe-cover-art"><img
                            src="https://images.unsplash.com/photo-1547592180-85f173990554?w=900&h=700&fit=crop"
                            alt="A colourful bowl of fresh food"></div>
                </div>
                <p id="recipeMessage" class="feature-message recipe-message" role="status" aria-live="polite">A little inspiration for your next meal.</p>
                <div class="recipe-filter-row" role="group" aria-label="Filter recipes">
                    <span class="recipe-filter-label">Show me</span>
                    <button class="recipe-filter is-active" type="button" data-recipe-filter="vegetarian" aria-pressed="true">Vegetarian</button>
                    <button class="recipe-filter" type="button" data-recipe-filter="all" aria-pressed="false">Everything</button>
                    <button class="recipe-filter" type="button" data-recipe-filter="nonvegetarian" aria-pressed="false">Non-vegetarian</button>
                </div>
                <div class="recipe-book-heading">
                    <div><span class="eyebrow">COLLECT YOUR FAVOURITES</span>
                        <h2 id="recipeSectionTitle">A few ideas to get you started</h2>
                        <p id="recipeSectionSubtitle">Browse a little, find something lovely, and save the recipes you want to make.</p>
                    </div>
                </div>
                <div class="recipe-grid" id="recipeGrid">
                    <p class="recipe-loading">Opening your Recipe Book…</p>
                </div>
            </section>

            <section class="page" id="page-planner">
                <div class="page-intro">
                    <div>
                        <h1>Meal <em>planner.</em></h1>
                        <p>Plan a little ahead. Leave room for something delicious.</p>
                    </div>
                    <div class="intro-photo planner-photo"><img
                            src="https://images.unsplash.com/photo-1490645935967-10de6ba17061?w=600&h=420&fit=crop"
                            alt="A colourful balanced meal"></div>
                </div>
                <div class="planner-paper">
                    <div class="planner-heading">
                        <div>
                            <h2 id="plannerWeekLabel">This week's table</h2>
                            <p>Choose a recipe or add your own meal.</p>
                        </div>
                        <div class="planner-controls"><button class="outline-btn" id="prevWeek" type="button">←
                                Previous</button><button class="outline-btn" id="todayWeek" type="button">This
                                week</button><input class="planner-date-jump" id="plannerDateJump" type="date"
                                aria-label="Jump to a date"><button class="outline-btn" id="nextWeek" type="button">Next
                                →</button><button class="text-btn" id="clearPlan" type="button">Clear week</button>
                        </div>
                    </div>
                    <div class="meal-table-wrap">
                        <table class="meal-table">
                            <thead>
                                <tr>
                                    <th>DAY</th>
                                    <th>☀ Breakfast</th>
                                    <th>🥗 Lunch</th>
                                    <th>🍓 Snacks</th>
                                    <th>🌙 Dinner</th>
                                </tr>
                            </thead>
                            <tbody id="mealTable"></tbody>
                        </table>
                    </div>
                    <p class="planner-foot">A flexible plan for a delicious week.</p>
                    <p id="plannerMessage" class="feature-message" role="status" aria-live="polite"></p>
                </div>
                <dialog id="mealDialog" class="modal">
                    <form id="mealForm"><button type="button" class="modal-close" id="closeMeal">×</button>
                        <h2 id="mealDialogTitle">Plan a meal</h2>
                        <p id="mealSlotLabel" class="meal-slot-label"></p><label>Choose a recipe<select id="mealRecipe">
                                <option value="">Choose a saved recipe or type your own</option>
                            </select></label><label>Or enter a meal name<input id="mealCustomName" maxlength="160"
                                placeholder="e.g. Vegetable pulao"></label><label>Notes / ingredients
                            (optional)<textarea id="mealDetails" rows="3" maxlength="10000"
                                placeholder="A few ingredients or preparation notes..."></textarea></label>
                        <p id="mealFormMessage" class="feature-message" role="status"></p>
                        <div class="meal-dialog-actions"><button class="text-btn" id="removeMealButton" type="button"
                                hidden>Remove this meal</button><button class="primary-btn full-btn" type="submit">Save
                                this meal ✿</button></div>
                    </form>
                </dialog>
            </section>

            <section class="page" id="page-account">
                <div class="page-intro">
                    <div>
                        <h1>My <em>account.</em></h1>
                        <p>Keep your PantryPal details up to date.</p>
                    </div>
                    <div class="intro-photo account-photo"><img
                            src="https://images.unsplash.com/photo-1556911220-e15b29be8c8f?w=600&h=420&fit=crop"
                            alt="A bright, welcoming kitchen"></div>
                </div>
                <div class="account-card">
                    <div class="account-avatar">
                        <?= htmlspecialchars(strtoupper(substr($_SESSION["user_name"] ?? "P", 0, 1)), ENT_QUOTES, "UTF-8") ?>
                    </div>
                    <div>
                        <h2><?= htmlspecialchars($_SESSION["user_name"] ?? "My pantry", ENT_QUOTES, "UTF-8") ?></h2>
                        <p><?= htmlspecialchars($_SESSION["user_email"] ?? "", ENT_QUOTES, "UTF-8") ?></p>
                    </div><a class="sample-label" href="logout.php">LOG OUT ↗</a>
                </div>
                <section class="paper-panel account-edit-panel">
                    <div class="panel-title">
                        <div><span class="eyebrow">PERSONAL DETAILS</span>
                            <h2>Edit your profile</h2>
                            <p>Update the name and email connected to your pantry.</p>
                        </div>
                    </div>
                    <form id="accountForm" class="account-form">
                        <label>Full name<input id="accountName" name="name" type="text" maxlength="100"
                                value="<?= htmlspecialchars($_SESSION["user_name"] ?? "", ENT_QUOTES, "UTF-8") ?>"
                                required></label>
                        <label>Email address<input id="accountEmail" name="email" type="email" maxlength="190"
                                value="<?= htmlspecialchars($_SESSION["user_email"] ?? "", ENT_QUOTES, "UTF-8") ?>"
                                required></label>
                        <div class="account-form-actions">
                            <p id="accountMessage" class="feature-message" aria-live="polite"></p><button
                                class="primary-btn" id="saveAccountButton" type="submit">Save changes ✿</button>
                        </div>
                    </form>
                </section>
                <section class="paper-panel account-password-panel">
                    <div class="panel-title">
                        <div><span class="eyebrow">SECURITY</span>
                            <h2>Change password</h2>
                            <p>Choose a new password of at least 8 characters.</p>
                        </div>
                    </div>
                    <form id="passwordForm" class="account-form password-form">
                        <label>Current password<input id="currentPassword" type="password"
                                autocomplete="current-password" required></label>
                        <label>New password<input id="newPassword" type="password" autocomplete="new-password"
                                minlength="8" required></label>
                        <label>Confirm new password<input id="confirmPassword" type="password"
                                autocomplete="new-password" minlength="8" required></label>
                        <div class="account-form-actions">
                            <p id="passwordMessage" class="feature-message" aria-live="polite"></p><button
                                class="outline-btn" id="changePasswordButton" type="submit">Update password</button>
                        </div>
                    </form>
                </section>
            </section>

            <footer class="page-footer"><span>MADE FOR A HAPPIER KITCHEN</span><span>PantryPal · less waste, more
                    taste</span></footer>
        </main>
    </div>

    <dialog id="productDialog" class="modal">
        <form id="productForm"><button type="button" class="modal-close" id="closeProduct">×</button>
            <h2 id="productDialogTitle">Add a product</h2>
            <label>Product name<input id="newName" name="product_name" required maxlength="120"
                    placeholder="e.g. Cherry tomatoes"></label>
            <div class="form-row"><label>Brand <small>(optional)</small><input id="newBrand" name="brand"
                        maxlength="100" placeholder="e.g. Amul"></label><label>Category<select id="newCategory"
                        name="category_id" required>
                        <option value="">Loading categories...</option>
                    </select></label></div>
            <div class="form-row"><label>Quantity<input id="newQty" name="quantity" type="number" min="0.01" step="0.01"
                        value="1" required></label><label>Unit<input id="newUnit" name="unit" maxlength="30"
                        value="piece" required placeholder="piece, kg, litre"></label></div>
            <div class="form-row"><label>Purchase date<input id="newPurchase" name="purchase_date"
                        type="date"></label><label>Expiry date<input id="newExpiry" name="expiry_date"
                        type="date"></label></div>
            <div class="form-row"><label>Minimum stock<input id="newMinStock" name="min_stock" type="number" min="0"
                        step="0.01" value="0"></label><label>Storage location<input id="newLocation"
                        name="storage_location" maxlength="50" placeholder="Fridge, pantry..."></label></div>
            <label>Notes <small>(optional)</small><textarea id="newNotes" name="notes" rows="2"
                    placeholder="Anything useful to remember..."></textarea></label>
            <p id="productFormMessage" role="status" aria-live="polite"></p><button class="primary-btn full-btn"
                id="saveProductButton" type="submit">Add to my pantry ✿</button>
        </form>
    </dialog>
    <dialog id="shoppingEditDialog" class="modal">
        <form id="shoppingEditForm"><button type="button" class="modal-close" id="closeShoppingEdit">×</button>
            <h2>Edit shopping item</h2><input type="hidden" id="editShoppingId"><label>Item name<input
                    id="editShoppingName" maxlength="120" required></label>
            <div class="form-row"><label>Quantity<input id="editShoppingQty" type="number" min="0.01" step="0.01"
                        required></label><label>Unit<input id="editShoppingUnit" maxlength="30" required></label></div>
            <label>Category<select id="editShoppingCategory">
                    <option value="">No category</option>
                </select></label>
            <p id="shoppingEditMessage" class="feature-message" role="status"></p><button class="primary-btn full-btn"
                type="submit">Save changes ✿</button>
        </form>
    </dialog>
    <dialog id="recipeDialog" class="recipe-detail-dialog"><button type="button" class="recipe-dialog-close"
            id="closeRecipeDialog" aria-label="Close recipe">×</button>
        <div id="recipeDialogContent"></div>
    </dialog>
    <script src="assets/js/app.js"></script>
    <script src="assets/js/recipes.js"></script>
</body>

</html>