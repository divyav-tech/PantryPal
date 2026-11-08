/* PantryPal Recipe Book – vegetarian inspiration by default, with open search filters. */
(() => {
  const API = 'api/recipes.php';
  const grid = document.getElementById('recipeGrid');
  const search = document.getElementById('recipeSearch');
  const searchButton = document.getElementById('searchRecipesButton');
  const randomButton = document.getElementById('randomRecipeButton');
  const status = document.getElementById('recipeMessage');
  const dialog = document.getElementById('recipeDialog');
  const details = document.getElementById('recipeDialogContent');
  const suggestionsBox = document.getElementById('recipeSuggestions');
  const sectionTitle = document.getElementById('recipeSectionTitle');
  const sectionSubtitle = document.getElementById('recipeSectionSubtitle');
  let savedRecipes = [];
  let currentResults = [];
  let showingSaved = false;
  let suggestionResults = [];
  let suggestionTimer = null;
  let suggestionRequest = 0;
  let activeFilter = 'vegetarian';
  let vegetarianPool = [];

  if (!grid) return;
  const escapeHTML = value => String(value ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  const message = (text, error = false) => {
    if (status) { status.textContent = text; status.classList.toggle('is-error', error); }
  };
  const request = async (url, options = {}) => {
    const response = await fetch(url, { ...options, headers: { Accept: 'application/json', ...(options.body ? {'Content-Type':'application/json'} : {}), ...(options.headers || {}) } });
    const data = await response.json();
    if (!response.ok || !data.success) throw new Error(data.message || 'Something went wrong.');
    return data;
  };
  const fromMealDB = meal => {
    const ingredients = [];
    for (let i = 1; i <= 20; i++) {
      const ingredient = (meal[`strIngredient${i}`] || '').trim();
      const measure = (meal[`strMeasure${i}`] || '').trim();
      if (ingredient) ingredients.push([measure, ingredient].filter(Boolean).join(' '));
    }
    return { id: null, mealdb_id: meal.idMeal, title: meal.strMeal, category: meal.strCategory || 'General', area: meal.strArea || 'International', instructions: meal.strInstructions || '', ingredients, image_url: meal.strMealThumb || '', youtube_url: meal.strYoutube || '' };
  };
  const toPlannerRecipe = recipe => ({ id: String(recipe.id), name: recipe.title, time: recipe.category || 'Recipe', match: recipe.area || 'Saved recipe', img: recipe.image_url || '', tags: (recipe.ingredients || []).join(' ') });
  const nonVegWords = /\b(chicken|beef|pork|lamb|mutton|goat|turkey|duck|fish|salmon|tuna|prawn|prawns|shrimp|crab|lobster|seafood|anchovy|anchovies|bacon|ham|sausage|pepperoni|meat|gelatin|egg|eggs)\b/i;
  function isVegetarian(recipe) {
    let ingredients = Array.isArray(recipe.ingredients) ? recipe.ingredients.join(' ') : '';
    if (!ingredients && recipe.strIngredient1 !== undefined) {
      ingredients = Array.from({ length: 20 }, (_, index) => recipe[`strIngredient${index + 1}`] || '').join(' ');
    }
    const category = String(recipe.category || recipe.strCategory || '');
    return !nonVegWords.test(`${ingredients} ${category}`);
  }
  function filterMeals(items, filter = activeFilter) {
    if (filter === 'vegetarian') return items.filter(isVegetarian);
    if (filter === 'nonvegetarian') return items.filter(item => !isVegetarian(item));
    return items;
  }
  function setSection(title, subtitle) {
    if (sectionTitle) sectionTitle.textContent = title;
    if (sectionSubtitle) sectionSubtitle.textContent = subtitle;
  }
  function renderCards(items, external = false) {
    currentResults = items;
    showingSaved = !external;
    if (!items.length) {
      const filterHint = activeFilter === 'vegetarian' ? 'Try another search, or switch to Everything.' : activeFilter === 'nonvegetarian' ? 'Try another search, or switch to Everything.' : 'Try another dish or ingredient.';
      grid.innerHTML = `<div class="recipe-empty"><h3>${external ? 'No recipes found' : 'Your recipe book is waiting'}</h3><p>${external ? filterHint : 'Search for a recipe and save the ones you love.'}</p></div>`;
      return;
    }
    grid.innerHTML = items.map((recipe, index) => `
      <button class="recipe-card" type="button" data-recipe-index="${index}" aria-label="Open ${escapeHTML(recipe.title)}">
        <span class="recipe-card-image"><img src="${escapeHTML(recipe.image_url || 'assets/images/pantry.jpg')}" alt="${escapeHTML(recipe.title)}" loading="lazy"><span class="recipe-photo-tape" aria-hidden="true"></span></span>
        <span class="recipe-card-copy"><span class="recipe-card-meta">${escapeHTML(recipe.category || 'Recipe')} <span>·</span> ${escapeHTML(recipe.area || 'International')}</span><strong>${escapeHTML(recipe.title)}</strong><small>${external ? 'View recipe and save to your book' : 'Open saved recipe'}</small></span>
      </button>`).join('');
  }
  function hideSuggestions() {
    if (!suggestionsBox) return;
    suggestionsBox.hidden = true;
    suggestionsBox.innerHTML = '';
  }
  function renderSuggestions(meals) {
    suggestionResults = filterMeals(meals || []).slice(0, 6);
    if (!suggestionsBox || !suggestionResults.length) { hideSuggestions(); return; }
    suggestionsBox.innerHTML = suggestionResults.map((meal, index) => `
      <button type="button" class="recipe-suggestion" role="option" data-suggestion-index="${index}">
        <img src="${escapeHTML(meal.strMealThumb || '')}" alt="" loading="lazy">
        <span><strong>${escapeHTML(meal.strMeal)}</strong><small>${escapeHTML(meal.strCategory || 'Recipe')} · ${escapeHTML(meal.strArea || 'International')}</small></span>
        <span class="suggestion-arrow" aria-hidden="true">↗</span>
      </button>`).join('');
    suggestionsBox.hidden = false;
  }
  async function getExternalMeals(query) {
    const data = await request(`${API}?action=search_external&q=${encodeURIComponent(query)}`);
    return data.meals || [];
  }
  async function getVegetarianPool() {
    if (vegetarianPool.length) return vegetarianPool;
    const filtered = await request(`${API}?action=vegetarian_external`);
    const ids = (filtered.meals || []).slice(0, 12);
    const full = await Promise.all(ids.map(async meal => {
      try {
        const result = await request(`${API}?action=lookup_external&id=${encodeURIComponent(meal.idMeal)}`);
        return result.meals?.[0] ? fromMealDB(result.meals[0]) : null;
      } catch { return null; }
    }));
    vegetarianPool = full.filter(Boolean);
    return vegetarianPool;
  }
  async function loadDiscoveries() {
    grid.innerHTML = '<p class="recipe-loading">Gathering a few delicious ideas…</p>';
    if (activeFilter === 'vegetarian') {
      setSection('A few vegetarian ideas to get you started', 'A little inspiration to begin with. You can switch filters or search for any dish.');
      try {
        const featured = (await getVegetarianPool()).slice(0, 9);
        if (featured.length) {
          renderCards(featured, true);
          message('Starting with vegetarian favourites. Choose Everything or Non-vegetarian whenever you like.');
        } else throw new Error('No vegetarian recipes were returned.');
      } catch (error) {
        const fallbackGroups = await Promise.all(['salad', 'pasta', 'rice'].map(q => getExternalMeals(q).catch(() => [])));
        const fallback = [...new Map(fallbackGroups.flat().filter(isVegetarian).map(meal => [meal.idMeal, fromMealDB(meal)])).values()].slice(0, 9);
        renderCards(fallback, true);
        message(fallback.length ? 'Showing vegetarian ideas from the recipe collection.' : 'The recipe service is unavailable. Please try again.', !fallback.length);
      }
      return;
    }
    setSection('A few ideas to get you started', 'Browse the collection, or search for a dish or ingredient.');
    const groups = await Promise.all(['pasta', 'salad', 'rice'].map(q => getExternalMeals(q).catch(() => [])));
    const unique = new Map();
    groups.flat().forEach(meal => { if (meal?.idMeal && !unique.has(meal.idMeal)) unique.set(meal.idMeal, fromMealDB(meal)); });
    renderCards(filterMeals([...unique.values()]).slice(0, 9), true);
    message('Browse these ideas or start typing to find something specific.');
  }
  function ingredientsMarkup(items) {
    return (items || []).length ? items.map(item => `<li>${escapeHTML(item)}</li>`).join('') : '<li>Ingredients are not listed.</li>';
  }
  function openRecipe(recipe, external) {
    if (!dialog || !details) return;
    details.innerHTML = `
      <div class="recipe-detail-head"><img src="${escapeHTML(recipe.image_url || 'assets/images/pantry.jpg')}" alt="${escapeHTML(recipe.title)}"><div><span class="eyebrow">${escapeHTML(recipe.area || 'International')} · ${escapeHTML(recipe.category || 'Recipe')}</span><h2>${escapeHTML(recipe.title)}</h2><div class="recipe-detail-actions">${external ? '<button class="primary-btn" id="saveDiscoveredRecipe" type="button">Save to my Recipe Book</button>' : '<button class="outline-btn" id="deleteSavedRecipe" type="button">Remove saved recipe</button>'}${recipe.youtube_url ? `<a class="outline-btn" href="${escapeHTML(recipe.youtube_url)}" target="_blank" rel="noopener">Watch video</a>` : ''}</div></div></div>
      <div class="recipe-detail-columns"><section><h3>Ingredients</h3><ul>${ingredientsMarkup(recipe.ingredients)}</ul></section><section><h3>Method</h3><p class="recipe-instructions">${escapeHTML(recipe.instructions || 'No instructions available.').replace(/\r?\n/g, '<br>')}</p></section></div>`;
    dialog.dataset.recipeId = recipe.id || '';
    dialog.dataset.external = external ? '1' : '0';
    dialog.dataset.recipe = JSON.stringify(recipe);
    dialog.showModal();
    document.getElementById('saveDiscoveredRecipe')?.addEventListener('click', () => saveRecipe(recipe));
    document.getElementById('deleteSavedRecipe')?.addEventListener('click', () => deleteRecipe(recipe.id));
  }
  async function loadSaved() {
    try {
      const data = await request(`${API}?action=list`);
      savedRecipes = (data.data || []).map(recipe => ({ ...recipe, id: Number(recipe.id), ingredients: Array.isArray(recipe.ingredients) ? recipe.ingredients : [] }));
      window.pantrypalSavedRecipes = savedRecipes;
      if (typeof recipes !== 'undefined') {
        recipes = savedRecipes.map(toPlannerRecipe);
        if (typeof renderMealRecipeOptions === 'function') renderMealRecipeOptions();
      }
      await loadDiscoveries();
    } catch (error) {
      grid.innerHTML = `<p class="recipe-loading">${escapeHTML(error.message)}</p>`;
      message(error.message, true);
    }
  }
  async function searchRecipes() {
    const query = search?.value.trim() || '';
    if (!query) { message('Enter a dish or ingredient to search.'); search?.focus(); return; }
    hideSuggestions();
    message('Searching TheMealDB…');
    setSection(`Results for “${query}”`, 'Choose a recipe to view the ingredients and cooking instructions.');
    grid.innerHTML = '<p class="recipe-loading">Finding something delicious…</p>';
    try {
      const meals = await getExternalMeals(query);
      const results = filterMeals(meals.map(fromMealDB));
      renderCards(results, true);
      message(`${results.length} ${activeFilter === 'vegetarian' ? 'vegetarian ' : activeFilter === 'nonvegetarian' ? 'non-vegetarian ' : ''}recipe${results.length === 1 ? '' : 's'} found. Select one to view and save it.`);
    } catch (error) { message(error.message, true); grid.innerHTML = `<p class="recipe-loading">${escapeHTML(error.message)}</p>`; }
  }
  async function updateSuggestions() {
    const query = search?.value.trim() || '';
    const requestId = ++suggestionRequest;
    if (query.length < 2) { hideSuggestions(); return; }
    try {
      const meals = await getExternalMeals(query);
      if (requestId !== suggestionRequest || search.value.trim() !== query) return;
      renderSuggestions(meals);
    } catch { if (requestId === suggestionRequest) hideSuggestions(); }
  }
  async function randomRecipe() {
    hideSuggestions();
    message('Finding a surprise recipe…');
    try {
      if (activeFilter === 'vegetarian') {
        const pool = await getVegetarianPool();
        if (!pool.length) throw new Error('No vegetarian surprise recipes are available right now.');
        openRecipe(pool[Math.floor(Math.random() * pool.length)], true);
      } else {
        let selected = null;
        for (let attempt = 0; attempt < 6; attempt++) {
          const data = await request(`${API}?action=random_external`);
          const meal = data.meals?.[0];
          if (meal && (activeFilter === 'all' || !isVegetarian(fromMealDB(meal)))) { selected = meal; break; }
        }
        if (!selected) throw new Error('Could not find a matching surprise. Try again.');
        openRecipe(fromMealDB(selected), true);
      }
      message('A surprise recipe for you.');
    } catch (error) { message(error.message, true); }
  }
  async function saveRecipe(recipe) {
    try {
      const data = await request(API, { method: 'POST', body: JSON.stringify(recipe) });
      message(data.message || 'Recipe saved.');
      dialog.close();
      await loadSaved();
    } catch (error) { message(error.message, true); }
  }
  async function deleteRecipe(id) {
    if (!confirm('Remove this recipe from your Recipe Book?')) return;
    try {
      const data = await request(`${API}?id=${encodeURIComponent(id)}`, { method: 'DELETE' });
      message(data.message || 'Recipe removed.');
      dialog.close();
      await loadSaved();
    } catch (error) { message(error.message, true); }
  }
  function setFilter(filter) {
    activeFilter = filter;
    document.querySelectorAll('[data-recipe-filter]').forEach(button => {
      const active = button.dataset.recipeFilter === filter;
      button.classList.toggle('is-active', active);
      button.setAttribute('aria-pressed', active ? 'true' : 'false');
    });
    hideSuggestions();
    if (search?.value.trim()) searchRecipes();
    else loadDiscoveries();
  }

  searchButton?.addEventListener('click', searchRecipes);
  search?.addEventListener('input', () => {
    clearTimeout(suggestionTimer);
    suggestionTimer = setTimeout(updateSuggestions, 350);
  });
  search?.addEventListener('focus', () => { if ((search.value || '').trim().length >= 2) updateSuggestions(); });
  search?.addEventListener('keydown', event => {
    if (event.key === 'Enter') { event.preventDefault(); searchRecipes(); }
    if (event.key === 'Escape') hideSuggestions();
  });
  randomButton?.addEventListener('click', randomRecipe);
  document.querySelectorAll('[data-recipe-filter]').forEach(button => button.addEventListener('click', () => setFilter(button.dataset.recipeFilter)));
  document.getElementById('showSavedRecipes')?.addEventListener('click', () => {
    if (search) search.value = '';
    hideSuggestions();
    setSection('Your saved recipes', 'Your personal collection, ready whenever you need a little inspiration.');
    message(savedRecipes.length ? 'Showing your saved recipes.' : 'You have not saved any recipes yet. Browse the featured ideas and save your favourites.');
    renderCards(savedRecipes);
  });
  suggestionsBox?.addEventListener('click', event => {
    const option = event.target.closest('[data-suggestion-index]');
    if (!option) return;
    const meal = suggestionResults[Number(option.dataset.suggestionIndex)];
    if (!meal) return;
    search.value = meal.strMeal;
    hideSuggestions();
    searchRecipes();
  });
  document.addEventListener('click', event => {
    if (!event.target.closest('.recipe-search-row') && !event.target.closest('#recipeSuggestions')) hideSuggestions();
  });
  grid.addEventListener('click', event => {
    const card = event.target.closest('[data-recipe-index]');
    if (!card) return;
    const recipe = currentResults[Number(card.dataset.recipeIndex)];
    if (recipe) openRecipe(recipe, !showingSaved);
  });
  document.getElementById('closeRecipeDialog')?.addEventListener('click', () => dialog.close());
  dialog?.addEventListener('click', event => { if (event.target === dialog) dialog.close(); });
  loadSaved();
})();
