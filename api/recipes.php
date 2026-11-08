<?php
header('Content-Type: application/json; charset=utf-8');
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please log in to use your recipe book.']);
    exit;
}

require_once __DIR__ . '/../config/db.php';
$userId = (int) $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

function recipeResponse(array $payload, int $status = 200): void {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function fetchMealDb(string $url): array {
    $context = stream_context_create(['http' => [
        'timeout' => 12,
        'ignore_errors' => true,
        'header' => "User-Agent: PantryPal/1.0\r\n"
    ]]);
    $raw = @file_get_contents($url, false, $context);
    if ($raw === false && function_exists('curl_init')) {
        $curl = curl_init($url);
        curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 12, CURLOPT_FOLLOWLOCATION => true]);
        $raw = curl_exec($curl);
        curl_close($curl);
    }
    if (!is_string($raw) || $raw === '') {
        recipeResponse(['success' => false, 'message' => 'The recipe service could not be reached. Check your internet connection and try again.'], 502);
    }
    $data = json_decode($raw, true);
    if (!is_array($data)) recipeResponse(['success' => false, 'message' => 'The recipe service returned an unreadable response.'], 502);
    return $data;
}

try {
    if ($method === 'GET') {
        $action = $_GET['action'] ?? 'list';
        if ($action === 'search_external') {
            $query = trim($_GET['q'] ?? '');
            if ($query === '') recipeResponse(['success' => false, 'message' => 'Enter a dish or ingredient to search.'], 400);
            $data = fetchMealDb('https://www.themealdb.com/api/json/v1/1/search.php?s=' . rawurlencode($query));
            recipeResponse(['success' => true, 'meals' => $data['meals'] ?? []]);
        }
        if ($action === 'random_external') {
            $data = fetchMealDb('https://www.themealdb.com/api/json/v1/1/random.php');
            recipeResponse(['success' => true, 'meals' => $data['meals'] ?? []]);
        }
        if ($action === 'vegetarian_external') {
            $data = fetchMealDb('https://www.themealdb.com/api/json/v1/1/filter.php?c=Vegetarian');
            recipeResponse(['success' => true, 'meals' => $data['meals'] ?? []]);
        }
        if ($action === 'lookup_external') {
            $mealId = preg_replace('/[^0-9]/', '', (string)($_GET['id'] ?? ''));
            if ($mealId === '') recipeResponse(['success' => false, 'message' => 'A valid recipe ID is required.'], 400);
            $data = fetchMealDb('https://www.themealdb.com/api/json/v1/1/lookup.php?i=' . rawurlencode($mealId));
            recipeResponse(['success' => true, 'meals' => $data['meals'] ?? []]);
        }

        $stmt = $pdo->prepare('SELECT id, mealdb_id, title, category, area, instructions, ingredients, image_url, youtube_url, created_at FROM recipes WHERE user_id = ? ORDER BY created_at DESC');
        $stmt->execute([$userId]);
        $recipes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($recipes as &$recipe) {
            $recipe['ingredients'] = json_decode($recipe['ingredients'] ?? '[]', true) ?: [];
        }
        unset($recipe);
        recipeResponse(['success' => true, 'data' => $recipes]);
    }

    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $title = trim($input['title'] ?? '');
        $instructions = trim($input['instructions'] ?? '');
        if ($title === '' || $instructions === '') recipeResponse(['success' => false, 'message' => 'Recipe title and instructions are required.'], 400);

        $mealdbId = trim((string)($input['mealdb_id'] ?? ''));
        $ingredients = json_encode(array_values($input['ingredients'] ?? []), JSON_UNESCAPED_UNICODE);
        if ($mealdbId !== '') {
            $stmt = $pdo->prepare('INSERT INTO recipes (user_id, mealdb_id, title, category, area, instructions, ingredients, image_url, youtube_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE title = VALUES(title), category = VALUES(category), area = VALUES(area), instructions = VALUES(instructions), ingredients = VALUES(ingredients), image_url = VALUES(image_url), youtube_url = VALUES(youtube_url)');
            $stmt->execute([$userId, $mealdbId, $title, $input['category'] ?? 'General', $input['area'] ?? 'International', $instructions, $ingredients, $input['image_url'] ?? '', $input['youtube_url'] ?? '']);
        } else {
            $stmt = $pdo->prepare('INSERT INTO recipes (user_id, mealdb_id, title, category, area, instructions, ingredients, image_url, youtube_url) VALUES (?, NULL, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([$userId, $title, $input['category'] ?? 'Custom', $input['area'] ?? 'Homemade', $instructions, $ingredients, $input['image_url'] ?? '', $input['youtube_url'] ?? '']);
        }
        recipeResponse(['success' => true, 'message' => 'Recipe saved to your Recipe Book.']);
    }

    if ($method === 'DELETE') {
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if (!$id) recipeResponse(['success' => false, 'message' => 'A valid recipe ID is required.'], 400);
        $stmt = $pdo->prepare('DELETE FROM recipes WHERE id = ? AND user_id = ?');
        $stmt->execute([$id, $userId]);
        if (!$stmt->rowCount()) recipeResponse(['success' => false, 'message' => 'Recipe not found.'], 404);
        recipeResponse(['success' => true, 'message' => 'Recipe removed from your Recipe Book.']);
    }

    recipeResponse(['success' => false, 'message' => 'Method not allowed.'], 405);
} catch (PDOException $error) {
    error_log('PantryPal recipes API: ' . $error->getMessage());
    recipeResponse(['success' => false, 'message' => 'A database error occurred while handling recipes.'], 500);
}
