/* PantryPal frontend – PHP/MySQL APIs are the source of truth for pantry, shopping and meals. */
"use strict";

const API_PRODUCTS = "api/products.php";
const API_SHOPPING = "api/shopping.php";
const API_MEALS = "api/meal-plans.php";
const API_ACCOUNT = "api/account.php";
const $ = (selector) => document.querySelector(selector);

let products = [];
let categoryRows = [];
let shoppingItems = [];
let lowStockItems = [];
let mealPlans = [];
let editingProductId = null;
let editingShoppingId = null;
let activeMealSlot = null;
let plannerWeekStart = getMonday(new Date());

let recipes = [];

function escapeHTML(value) {
  return String(value ?? "").replace(/[&<>"']/g, (char) => ({
    "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;"
  })[char]);
}
async function apiRequest(url, options = {}) {
  const response = await fetch(url, {
    ...options,
    headers: { "Accept": "application/json", ...(options.body ? { "Content-Type": "application/json" } : {}), ...(options.headers || {}) }
  });
  let data;
  try { data = await response.json(); } catch { throw new Error("The server returned an invalid response. Check PHP errors."); }
  if (!response.ok || !data.success) throw new Error(data.message || "Something went wrong.");
  return data;
}
function dateStatus(date) {
  if (!date) return { label: "No expiry date", status: "fresh", days: null };
  const today = new Date();
  today.setHours(0, 0, 0, 0);
  const expiry = new Date(`${date}T00:00:00`);
  const days = Math.round((expiry - today) / 86400000);
  if (days < 0) return { label: "Expired", status: "expired", days };
  if (days === 0) return { label: "Expires today", status: "today", days };
  if (days <= 7) return { label: days === 1 ? "Tomorrow" : `In ${days} days`, status: "soon", days };
  return { label: expiry.toLocaleDateString("en-IN", { day: "numeric", month: "short" }), status: "fresh", days };
}
function mapProduct(row) {
  const expiry = dateStatus(row.expiry_date);
  return { ...row, id: Number(row.product_id), name: row.product_name, category: row.category_name || "Others",
    qty: `${Number(row.quantity)} ${row.unit}`, status: expiry.status, expiry: expiry.label };
}
function statusLabel(product) {
  return product.status === "expired" ? "Expired" : product.status === "today" ? "Expires today" :
    product.status === "soon" ? "Expiring soon" : "Fresh";
}
function getMonday(date) {
  const result = new Date(date);
  result.setHours(0, 0, 0, 0);
  const day = (result.getDay() + 6) % 7;
  result.setDate(result.getDate() - day);
  return result;
}
function dateString(date) {
  const y = date.getFullYear(), m = String(date.getMonth() + 1).padStart(2, "0"), d = String(date.getDate()).padStart(2, "0");
  return `${y}-${m}-${d}`;
}
function addDays(date, count) {
  const result = new Date(date);
  result.setDate(result.getDate() + count);
  return result;
}
function formatDate(date) {
  return date.toLocaleDateString("en-IN", { day: "numeric", month: "short" });
}
function setMessage(selector, message, isError = false) {
  const element = $(selector);
  if (!element) return;
  element.textContent = message;
  element.classList.toggle("is-error", isError);
}

async function loadPantryData() {
  try {
    const data = await apiRequest(API_PRODUCTS);
    categoryRows = data.categories || [];
    products = (data.products || []).map(mapProduct);
    fillCategorySelect($("#newCategory"), "Choose a category");
    fillCategorySelect($("#shoppingCategory"), "No category");
    fillCategorySelect($("#shoppingCategoryFilter"), "All categories", true);
    fillCategorySelect($("#editShoppingCategory"), "No category");
    const filter = $("#categoryFilter");
    const oldCategory = filter.value || "all";
    filter.innerHTML = '<option value="all">All categories</option>' + categoryRows.map(c =>
      `<option value="${Number(c.category_id)}">${escapeHTML(c.category_name)}</option>`).join("");
    filter.value = [...filter.options].some(o => o.value === oldCategory) ? oldCategory : "all";
    renderCategoryChips();
    renderLocationOptions();
    renderInventory();
    renderExpiry();
    renderDashboard();
  } catch (error) {
    console.error(error);
    const grid = $("#inventoryGrid");
    if (grid) grid.innerHTML = `<div class="empty-state">Could not load your pantry. ${escapeHTML(error.message)}</div>`;
  }
}
function fillCategorySelect(element, firstLabel, allOption = false) {
  if (!element) return;
  const selected = element.value;
  element.innerHTML = `<option value="${allOption ? "all" : ""}">${escapeHTML(firstLabel)}</option>` +
    categoryRows.map(c => `<option value="${Number(c.category_id)}">${escapeHTML(c.category_name)}</option>`).join("");
  if ([...element.options].some(option => option.value === selected)) element.value = selected;
}
function renderCategoryChips() {
  const wrap = $("#categoryChips");
  if (!wrap) return;
  const icons = { Fruits: "🍎", Vegetables: "🥬", Dairy: "🥛", Bakery: "🍞", Snacks: "🍪" };
  wrap.innerHTML = '<button class="chip active" data-category="all">Everything</button>' +
    categoryRows.map(c => `<button class="chip" data-category="${Number(c.category_id)}">${icons[c.category_name] || "✿"} ${escapeHTML(c.category_name)}</button>`).join("");
}
function renderLocationOptions() {
  const element = $("#locationFilter");
  if (!element) return;
  const current = element.value || "all";
  const locations = [...new Set(products.map(p => p.storage_location).filter(Boolean))].sort((a, b) => a.localeCompare(b));
  element.innerHTML = '<option value="all">All locations</option>' + locations.map(x =>
    `<option value="${escapeHTML(x)}">${escapeHTML(x)}</option>`).join("");
  element.value = locations.includes(current) ? current : "all";
}
function renderInventory() {
  const term = ($("#productSearch")?.value || "").trim().toLowerCase();
  const category = $("#categoryFilter")?.value || "all";
  const sort = $("#sortFilter")?.value || "name";
  const location = $("#locationFilter")?.value || "all";
  const status = $("#statusFilter")?.value || "all";
  let list = products.filter(p => {
    const haystack = [p.name, p.brand, p.category, p.storage_location, p.notes].join(" ").toLowerCase();
    const low = Number(p.quantity) <= Number(p.min_stock || 0);
    return (category === "all" || String(p.category_id) === category) &&
      (location === "all" || (p.storage_location || "") === location) &&
      (status === "all" || (status === "low" ? low : p.status === status)) && haystack.includes(term);
  });
  list.sort((a, b) => {
    if (sort === "expiry") return (a.expiry_date || "9999-12-31").localeCompare(b.expiry_date || "9999-12-31");
    if (sort === "quantity") return Number(a.quantity) - Number(b.quantity);
    if (sort === "quantity-desc") return Number(b.quantity) - Number(a.quantity);
    return a.name.localeCompare(b.name);
  });
  $("#inventoryGrid").innerHTML = list.length ? list.map(p => `
    <article class="inventory-card"><div class="inventory-category-mark" aria-hidden="true">${escapeHTML(p.category.slice(0, 1).toUpperCase())}</div><h3>${escapeHTML(p.name)}</h3>
    <p>${escapeHTML(p.category)} · ${escapeHTML(p.qty)}</p><span class="date-pill">${escapeHTML(p.expiry)}</span>
    <div class="inventory-meta"><small>${escapeHTML(p.brand || "No brand")}</small><small>${escapeHTML(p.storage_location || "Location not set")}</small>
    <small>${Number(p.quantity) <= Number(p.min_stock || 0) ? "Low stock" : statusLabel(p)}</small></div>
    <div class="inventory-actions"><button type="button" data-edit-product="${p.id}">Edit</button><button type="button" class="delete-product" data-delete-product="${p.id}">Delete</button></div></article>`).join("") :
    `<div class="empty-state inventory-empty">🌼<h3>${products.length ? "No matching products found." : "Your pantry is waiting for its first ingredients."}</h3>${products.length ? "Try changing your search or filters." : "Add a product to see it here."}</div>`;
}
function productRow(product) {
  return `<div class="product-row"><span class="product-category-mark" aria-hidden="true">${escapeHTML(product.category.slice(0, 1).toUpperCase())}</span><div class="product-info"><b>${escapeHTML(product.name)}</b><small>${escapeHTML(product.category)} · ${escapeHTML(product.qty)}</small></div><span class="date-pill">${escapeHTML(product.expiry)}</span></div>`;
}
function renderExpiry() {
  const urgent = products.filter(p => ["today", "soon", "expired"].includes(p.status))
    .sort((a, b) => {
      const order = { expired: 0, today: 1, soon: 2 };
      return order[a.status] - order[b.status] || (a.expiry_date || "").localeCompare(b.expiry_date || "");
    });
  $("#expiryList").innerHTML = urgent.length ? urgent.map(p => `
    <div class="expiry-item"><span class="product-category-mark" aria-hidden="true">${escapeHTML(p.category.slice(0, 1).toUpperCase())}</span><div><b>${escapeHTML(p.name)}</b><small>${escapeHTML(p.category)} · ${escapeHTML(p.qty)}</small></div><span class="date-pill">${escapeHTML(p.expiry)}</span></div>`).join("") :
    '<p class="empty-state">Nothing urgent today. Lovely! ✿</p>';
  $("#dashboardExpiry").innerHTML = urgent.slice(0, 3).map(productRow).join("") || '<p class="empty-state">Your pantry is looking fresh! ✿</p>';
}
function renderDashboard() {
  const low = products.filter(p => Number(p.quantity) <= Number(p.min_stock || 0) && Number(p.min_stock || 0) > 0);
  $("#statTotal").textContent = products.length;
  $("#statSoon").textContent = products.filter(p => p.status === "soon" || p.status === "today").length;
  $("#statExpired").textContent = products.filter(p => p.status === "expired").length;
  $("#statLow").textContent = low.length;
  $("#recentProducts").innerHTML = products.slice(0, 4).map(p =>
    `<div class="mini-product"><div class="mini-product-mark" aria-hidden="true">${escapeHTML(p.category.slice(0, 1).toUpperCase())}</div><b>${escapeHTML(p.name)}</b><small>${escapeHTML(p.category)}</small></div>`).join("") ||
    '<p class="empty-state">Your recently added products will appear here.</p>';
}
function renderRecipes(term = "") {
  const grid = $("#recipeGrid");
  if (!grid) return;
  const filtered = recipes.filter(recipe => (recipe.name + " " + recipe.tags).toLowerCase().includes(term.toLowerCase()));
  grid.innerHTML = filtered.length ? filtered.map(recipe =>
    `<article class="recipe-card"><img src="${escapeHTML(recipe.img || "assets/images/pantry.jpg")}" alt="${escapeHTML(recipe.name)}"><h3>${escapeHTML(recipe.name)}</h3><p>${escapeHTML(recipe.time || "Saved recipe")}</p><span class="match-tag">${escapeHTML(recipe.match || "Recipe Book")}</span></article>`).join("") :
    '<p class="empty-state">Search TheMealDB to discover recipes, then save your favourites here.</p>';
}

/* Shopping list – every change is sent to the authenticated PHP API. */
async function loadShopping() {
  try {
    const [data, suggestionData] = await Promise.all([
      apiRequest(API_SHOPPING),
      apiRequest(`${API_SHOPPING}?action=suggestions`)
    ]);
    shoppingItems = data.items || [];
    lowStockItems = suggestionData.suggestions || [];
    categoryRows = data.categories || categoryRows;
    fillCategorySelect($("#shoppingCategory"), "No category");
    fillCategorySelect($("#shoppingCategoryFilter"), "All categories", true);
    fillCategorySelect($("#editShoppingCategory"), "No category");
    renderShopping();
    renderLowStock();
  } catch (error) {
    console.error(error);
    $("#shoppingPending").innerHTML = `<p class="empty-state">${escapeHTML(error.message)}</p>`;
  }
}
function renderShopping() {
  const statusFilter = $("#shoppingFilter")?.value || "all";
  const categoryFilter = $("#shoppingCategoryFilter")?.value || "all";
  const filtered = shoppingItems.filter(item =>
    (statusFilter === "all" || (statusFilter === "pending" ? Number(item.is_purchased) === 0 : Number(item.is_purchased) === 1)) &&
    (categoryFilter === "all" || String(item.category_id || "") === categoryFilter));
  const pending = filtered.filter(item => Number(item.is_purchased) === 0);
  const bought = filtered.filter(item => Number(item.is_purchased) === 1);
  const itemMarkup = item => `<article class="shopping-item ${Number(item.is_purchased) ? "done" : ""}">
    <label class="shopping-check"><input type="checkbox" data-shopping-check="${Number(item.item_id)}" ${Number(item.is_purchased) ? "checked" : ""}><span class="checkmark"></span></label>
    <div class="shopping-item-info"><b>${escapeHTML(item.item_name)}</b><small>${Number(item.quantity)} ${escapeHTML(item.unit)}${item.category_name ? " · " + escapeHTML(item.category_name) : ""}</small></div>
    <div class="shopping-item-actions"><button type="button" data-edit-shopping="${Number(item.item_id)}" aria-label="Edit ${escapeHTML(item.item_name)}">Edit</button><button type="button" data-delete-shopping="${Number(item.item_id)}" aria-label="Delete ${escapeHTML(item.item_name)}">×</button></div></article>`;
  $("#shoppingPending").innerHTML = pending.length ? pending.map(itemMarkup).join("") : '<p class="empty-state">Nothing waiting on your list. Enjoy the calm! ✿</p>';
  $("#shoppingBought").innerHTML = bought.length ? bought.map(itemMarkup).join("") : '<p class="empty-state">Your basket is waiting for its first tick.</p>';
  $("#shoppingPendingCount").textContent = shoppingItems.filter(i => !Number(i.is_purchased)).length;
  $("#shoppingBoughtCount").textContent = shoppingItems.filter(i => Number(i.is_purchased)).length;
}
function renderLowStock() {
  $("#lowStockSuggestions").innerHTML = lowStockItems.length ? lowStockItems.map(item =>
    `<div class="low-stock-item"><span><b>${escapeHTML(item.product_name)}</b><small>${Number(item.quantity)} ${escapeHTML(item.unit)} left · minimum ${Number(item.min_stock)}</small></span>
    <button class="outline-btn" type="button" data-add-suggestion="${Number(item.product_id)}">Add to list ＋</button></div>`).join("") :
    '<p class="empty-state">No low-stock suggestions right now. Your pantry is doing well! ✿</p>';
}
async function addShoppingItem(event) {
  event.preventDefault();
  const button = $("#addShoppingBtn");
  const payload = { item_name: $("#shoppingInput").value.trim(), quantity: $("#shoppingQty").value,
    unit: $("#shoppingUnit").value.trim(), category_id: $("#shoppingCategory").value };
  if (!payload.item_name) return;
  button.disabled = true;
  try {
    await apiRequest(API_SHOPPING, { method: "POST", body: JSON.stringify(payload) });
    $("#shoppingForm").reset();
    $("#shoppingQty").value = "1";
    $("#shoppingUnit").value = "piece";
    await loadShopping();
    setMessage("#shoppingMessage", "Added to your grocery checklist ✿");
  } catch (error) { setMessage("#shoppingMessage", error.message, true); }
  finally { button.disabled = false; }
}
function openShoppingEdit(id) {
  const item = shoppingItems.find(row => Number(row.item_id) === Number(id));
  if (!item) return;
  editingShoppingId = Number(item.item_id);
  $("#editShoppingId").value = item.item_id;
  $("#editShoppingName").value = item.item_name;
  $("#editShoppingQty").value = item.quantity;
  $("#editShoppingUnit").value = item.unit;
  $("#editShoppingCategory").value = item.category_id || "";
  $("#shoppingEditMessage").textContent = "";
  $("#shoppingEditDialog").showModal();
}
async function saveShoppingEdit(event) {
  event.preventDefault();
  const payload = { item_id: editingShoppingId, item_name: $("#editShoppingName").value.trim(),
    quantity: $("#editShoppingQty").value, unit: $("#editShoppingUnit").value.trim(),
    category_id: $("#editShoppingCategory").value };
  try {
    await apiRequest(API_SHOPPING, { method: "PUT", body: JSON.stringify(payload) });
    $("#shoppingEditDialog").close();
    await loadShopping();
    setMessage("#shoppingMessage", "Shopping item updated ✿");
  } catch (error) { setMessage("#shoppingEditMessage", error.message, true); }
}
async function toggleShopping(id, checked) {
  try {
    await apiRequest(API_SHOPPING, { method: "PATCH", body: JSON.stringify({ item_id: id, is_purchased: checked ? 1 : 0 }) });
    await loadShopping();
  } catch (error) { alert(error.message); await loadShopping(); }
}
async function deleteShopping(id) {
  if (!confirm("Remove this item from your shopping list?")) return;
  try { await apiRequest(API_SHOPPING, { method: "DELETE", body: JSON.stringify({ item_id: id }) }); await loadShopping(); }
  catch (error) { alert(error.message); }
}
async function addSuggestion(productId) {
  try {
    await apiRequest(API_SHOPPING, { method: "POST", body: JSON.stringify({ product_id: productId }) });
    await loadShopping();
    setMessage("#shoppingMessage", "Low-stock item added to your list ✿");
  } catch (error) { alert(error.message); }
}
async function clearPurchased() {
  if (!shoppingItems.some(item => Number(item.is_purchased))) return;
  if (!confirm("Clear all purchased items?")) return;
  try { await apiRequest(`${API_SHOPPING}?action=clear-purchased`, { method: "DELETE", body: JSON.stringify({}) }); await loadShopping(); }
  catch (error) { alert(error.message); }
}

/* Meal planner – one persistent meal per user, date and meal type. */
function renderMealRecipeOptions() {
  $("#mealRecipe").innerHTML = '<option value="">Choose a saved recipe or type your own</option>' +
    recipes.map(recipe => `<option value="${escapeHTML(recipe.id)}">${escapeHTML(recipe.name)}</option>`).join("");
}
async function loadMealPlans() {
  const start = dateString(plannerWeekStart);
  const end = dateString(addDays(plannerWeekStart, 6));
  $("#plannerWeekLabel").textContent = `${formatDate(plannerWeekStart)} – ${formatDate(addDays(plannerWeekStart, 6))}`;
  if ($("#plannerDateJump")) $("#plannerDateJump").value = dateString(plannerWeekStart);
  try {
    const data = await apiRequest(`${API_MEALS}?start=${encodeURIComponent(start)}&end=${encodeURIComponent(end)}`);
    mealPlans = data.plans || [];
    renderPlanner();
  } catch (error) {
    console.error(error);
    $("#mealTable").innerHTML = `<tr><td colspan="5" class="empty-state">${escapeHTML(error.message)}</td></tr>`;
  }
}
function renderPlanner() {
  const days = ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"];
  const meals = ["breakfast", "lunch", "snacks", "dinner"];
  $("#mealTable").innerHTML = days.map((day, index) => {
    const date = addDays(plannerWeekStart, index);
    const key = dateString(date);
    const slots = meals.map(meal => {
      const plan = mealPlans.find(p => p.meal_date === key && p.meal_type === meal);
      return `<td><button type="button" class="meal-cell ${plan ? "has-meal" : ""}" data-meal-date="${key}" data-meal-type="${meal}">
        ${plan ? `<b>${escapeHTML(plan.recipe_name)}</b><small>${escapeHTML(plan.recipe_source === "themealdb" ? "TheMealDB recipe" : plan.recipe_source === "pantrypal" ? "Recipe Book" : "Your meal")}</small><span class="meal-cell-actions">Edit / remove</span>` : '<span class="meal-placeholder">＋ Add a meal</span>'}
      </button></td>`;
    }).join("");
    return `<tr><th scope="row"><b>${day.slice(0,3).toUpperCase()}</b><small>${formatDate(date)}</small></th>${slots}</tr>`;
  }).join("");
}
function openMealDialog(date, type) {
  activeMealSlot = { date, type };
  const plan = mealPlans.find(p => p.meal_date === date && p.meal_type === type);
  $("#mealDialogTitle").textContent = plan ? "Edit your meal" : "Plan a meal";
  $("#mealSlotLabel").textContent = `${new Date(`${date}T00:00:00`).toLocaleDateString("en-IN", { weekday: "long", day: "numeric", month: "long" })} · ${type[0].toUpperCase() + type.slice(1)}`;
  $("#mealRecipe").value = plan?.recipe_source === "pantrypal" ? (plan.recipe_id || "") : "";
  $("#mealCustomName").value = plan && plan.recipe_source !== "pantrypal" ? plan.recipe_name : "";
  $("#mealDetails").value = plan?.recipe_details || "";
  $("#mealFormMessage").textContent = "";
  $("#mealForm").dataset.planId = plan?.plan_id || "";
  $("#removeMealButton").hidden = !plan;
  $("#mealDialog").showModal();
}
async function saveMeal(event) {
  event.preventDefault();
  const recipeId = $("#mealRecipe").value;
  const selectedRecipe = recipes.find(recipe => recipe.id === recipeId);
  const customName = $("#mealCustomName").value.trim();
  const name = selectedRecipe?.name || customName;
  if (!name) { setMessage("#mealFormMessage", "Choose a Recipe Book recipe or enter a meal name.", true); return; }
  const payload = {
    meal_date: activeMealSlot.date, meal_type: activeMealSlot.type,
    recipe_source: selectedRecipe ? "pantrypal" : "manual",
    recipe_id: selectedRecipe?.id || "",
    recipe_name: name,
    recipe_details: $("#mealDetails").value.trim()
  };
  try {
    await apiRequest(API_MEALS, { method: "POST", body: JSON.stringify(payload) });
    $("#mealDialog").close();
    await loadMealPlans();
    setMessage("#plannerMessage", "Your meal has been saved ✿");
  } catch (error) { setMessage("#mealFormMessage", error.message, true); }
}
async function removeMeal() {
  const plan = mealPlans.find(p => p.meal_date === activeMealSlot.date && p.meal_type === activeMealSlot.type);
  if (!plan) return;
  if (!confirm("Remove this meal from your plan?")) return;
  try {
    await apiRequest(API_MEALS, { method: "DELETE", body: JSON.stringify({ plan_id: Number(plan.plan_id) }) });
    $("#mealDialog").close();
    await loadMealPlans();
    setMessage("#plannerMessage", "Meal removed from your week.");
  } catch (error) { setMessage("#mealFormMessage", error.message, true); }
}
async function clearMealWeek() {
  if (!mealPlans.length) return;
  if (!confirm("Clear all meals in the week currently displayed?")) return;
  try {
    await Promise.all(mealPlans.map(plan => apiRequest(API_MEALS, { method: "DELETE", body: JSON.stringify({ plan_id: Number(plan.plan_id) }) })));
    await loadMealPlans();
    setMessage("#plannerMessage", "This week's plan has been cleared.");
  } catch (error) { alert(error.message); await loadMealPlans(); }
}


async function saveAccountProfile(event) {
  event.preventDefault();
  const button = $("#saveAccountButton");
  button.disabled = true;
  setMessage("#accountMessage", "");
  try {
    const data = await apiRequest(API_ACCOUNT, { method: "POST", body: JSON.stringify({
      action: "profile", name: $("#accountName").value.trim(), email: $("#accountEmail").value.trim()
    })});
    const heading = document.querySelector("#page-account .account-card h2");
    const email = document.querySelector("#page-account .account-card p");
    const avatar = document.querySelector("#page-account .account-avatar");
    if (heading) heading.textContent = data.name;
    if (email) email.textContent = data.email;
    if (avatar) avatar.textContent = data.name.charAt(0).toUpperCase();
    setMessage("#accountMessage", data.message);
  } catch (error) { setMessage("#accountMessage", error.message, true); }
  finally { button.disabled = false; }
}
async function changeAccountPassword(event) {
  event.preventDefault();
  const current = $("#currentPassword").value;
  const next = $("#newPassword").value;
  const confirmNext = $("#confirmPassword").value;
  if (next !== confirmNext) { setMessage("#passwordMessage", "The new passwords do not match.", true); return; }
  const button = $("#changePasswordButton");
  button.disabled = true;
  setMessage("#passwordMessage", "");
  try {
    const data = await apiRequest(API_ACCOUNT, { method: "POST", body: JSON.stringify({
      action: "password", current_password: current, new_password: next
    })});
    $("#passwordForm").reset();
    setMessage("#passwordMessage", data.message);
  } catch (error) { setMessage("#passwordMessage", error.message, true); }
  finally { button.disabled = false; }
}

/* Existing pantry product CRUD. */
function resetProductForm() {
  editingProductId = null;
  $("#productForm").reset();
  $("#newQty").value = "1";
  $("#newUnit").value = "piece";
  $("#newMinStock").value = "0";
  $("#productDialogTitle").textContent = "Add a product";
  $("#saveProductButton").textContent = "Add to my pantry ✿";
  $("#productFormMessage").textContent = "";
}
function openEditProduct(id) {
  const product = products.find(p => p.id === Number(id));
  if (!product) return;
  editingProductId = product.id;
  $("#newName").value = product.product_name;
  $("#newBrand").value = product.brand || "";
  $("#newCategory").value = product.category_id;
  $("#newQty").value = product.quantity;
  $("#newUnit").value = product.unit;
  $("#newPurchase").value = product.purchase_date || "";
  $("#newExpiry").value = product.expiry_date || "";
  $("#newMinStock").value = product.min_stock ?? 0;
  $("#newLocation").value = product.storage_location || "";
  $("#newNotes").value = product.notes || "";
  $("#productDialogTitle").textContent = "Edit product";
  $("#saveProductButton").textContent = "Save changes ✿";
  $("#productFormMessage").textContent = "";
  $("#productDialog").showModal();
}
async function deleteProduct(id) {
  const product = products.find(p => p.id === Number(id));
  if (!product || !confirm(`Delete “${product.product_name}” from your pantry? This cannot be undone.`)) return;
  try {
    await apiRequest(API_PRODUCTS, { method: "DELETE", body: JSON.stringify({ product_id: product.id }) });
    await loadPantryData();
  } catch (error) { alert(error.message); }
}
async function saveProduct(event) {
  event.preventDefault();
  const button = $("#saveProductButton"), message = $("#productFormMessage");
  const payload = {
    product_name: $("#newName").value.trim(), brand: $("#newBrand").value.trim(),
    category_id: $("#newCategory").value, quantity: $("#newQty").value, unit: $("#newUnit").value.trim(),
    purchase_date: $("#newPurchase").value, expiry_date: $("#newExpiry").value,
    min_stock: $("#newMinStock").value, storage_location: $("#newLocation").value.trim(), notes: $("#newNotes").value.trim()
  };
  if (editingProductId) payload.product_id = editingProductId;
  button.disabled = true;
  button.textContent = editingProductId ? "Saving..." : "Adding...";
  message.textContent = "";
  try {
    await apiRequest(API_PRODUCTS, { method: editingProductId ? "PUT" : "POST", body: JSON.stringify(payload) });
    $("#productDialog").close();
    await loadPantryData();
    showPage("pantry");
  } catch (error) { message.textContent = error.message; message.style.color = "#a33"; }
  finally { button.disabled = false; button.textContent = editingProductId ? "Save changes ✿" : "Add to my pantry ✿"; }
}

function showPage(name) {
  document.querySelectorAll(".page").forEach(page => page.classList.remove("active"));
  const page = $(`#page-${name}`);
  if (page) page.classList.add("active");
  document.querySelectorAll(".nav-link").forEach(link => link.classList.toggle("active", link.dataset.page === name));
  const labels = { dashboard: "MY HOME", pantry: "MY PANTRY", expiry: "USE IT SOON", shopping: "SHOPPING LIST", recipes: "RECIPE BOOK", planner: "MEAL PLANNER", account: "MY ACCOUNT" };
  $("#crumb").textContent = labels[name] || "MY KITCHEN";
  $("#sidebar").classList.remove("open");
  if (name === "shopping") loadShopping();
  if (name === "planner") loadMealPlans();
  window.scrollTo({ top: 0, behavior: "smooth" });
}

/* Event wiring – listeners are attached only after the DOM has loaded. */
document.addEventListener("click", event => {
  const nav = event.target.closest("[data-page]");
  if (nav) { event.preventDefault(); showPage(nav.dataset.page); }
  const go = event.target.closest("[data-go]");
  if (go) showPage(go.dataset.go);
  const chip = event.target.closest("[data-category]");
  if (chip) {
    document.querySelectorAll(".chip").forEach(c => c.classList.remove("active"));
    chip.classList.add("active");
    $("#categoryFilter").value = chip.dataset.category;
    renderInventory();
  }
  const editProduct = event.target.closest("[data-edit-product]");
  if (editProduct) openEditProduct(editProduct.dataset.editProduct);
  const deleteProductButton = event.target.closest("[data-delete-product]");
  if (deleteProductButton) deleteProduct(deleteProductButton.dataset.deleteProduct);
  const editShopping = event.target.closest("[data-edit-shopping]");
  if (editShopping) openShoppingEdit(editShopping.dataset.editShopping);
  const deleteShoppingButton = event.target.closest("[data-delete-shopping]");
  if (deleteShoppingButton) deleteShopping(deleteShoppingButton.dataset.deleteShopping);
  const suggestion = event.target.closest("[data-add-suggestion]");
  if (suggestion) addSuggestion(suggestion.dataset.addSuggestion);
  const mealCell = event.target.closest("[data-meal-date]");
  if (mealCell) openMealDialog(mealCell.dataset.mealDate, mealCell.dataset.mealType);
});
document.addEventListener("change", event => {
  const checkbox = event.target.closest("[data-shopping-check]");
  if (checkbox) toggleShopping(Number(checkbox.dataset.shoppingCheck), checkbox.checked);
});
["#productSearch", "#categoryFilter", "#sortFilter", "#locationFilter", "#statusFilter"].forEach(selector => {
  $(selector)?.addEventListener("input", renderInventory);
  $(selector)?.addEventListener("change", () => {
    if (selector === "#categoryFilter") {
      document.querySelectorAll(".chip").forEach(chip => chip.classList.toggle("active", chip.dataset.category === $("#categoryFilter").value));
    }
    renderInventory();
  });
});
$("#shoppingForm")?.addEventListener("submit", addShoppingItem);
$("#shoppingFilter")?.addEventListener("change", renderShopping);
$("#shoppingCategoryFilter")?.addEventListener("change", renderShopping);
$("#shoppingEditForm")?.addEventListener("submit", saveShoppingEdit);
$("#closeShoppingEdit")?.addEventListener("click", () => $("#shoppingEditDialog").close());
$("#clearBought")?.addEventListener("click", clearPurchased);
$("#mealForm")?.addEventListener("submit", saveMeal);
$("#closeMeal")?.addEventListener("click", () => $("#mealDialog").close());
$("#removeMealButton")?.addEventListener("click", removeMeal);
$("#prevWeek")?.addEventListener("click", () => { plannerWeekStart = addDays(plannerWeekStart, -7); loadMealPlans(); });
$("#nextWeek")?.addEventListener("click", () => { plannerWeekStart = addDays(plannerWeekStart, 7); loadMealPlans(); });
$("#todayWeek")?.addEventListener("click", () => { plannerWeekStart = getMonday(new Date()); loadMealPlans(); });
$("#plannerDateJump")?.addEventListener("change", event => {
  if (event.target.value) {
    const [year, month, day] = event.target.value.split("-").map(Number);
    plannerWeekStart = getMonday(new Date(year, month - 1, day));
    loadMealPlans();
  }
});
$("#clearPlan")?.addEventListener("click", clearMealWeek);
$("#addProductBtn")?.addEventListener("click", () => { resetProductForm(); $("#productDialog").showModal(); });
$("#closeProduct")?.addEventListener("click", () => $("#productDialog").close());
$("#productDialog")?.addEventListener("close", resetProductForm);
$("#productForm")?.addEventListener("submit", saveProduct);
$("#mobileMenu")?.addEventListener("click", () => $("#sidebar").classList.toggle("open"));
$("#todayLabel").textContent = new Intl.DateTimeFormat("en-IN", { weekday: "short", day: "numeric", month: "short" }).format(new Date()).toUpperCase();

renderRecipes();
renderMealRecipeOptions();
loadPantryData();
loadShopping();
loadMealPlans();

$("#accountForm")?.addEventListener("submit", saveAccountProfile);
$("#passwordForm")?.addEventListener("submit", changeAccountPassword);
