<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please log in again.']);
    exit;
}

require_once __DIR__ . '/../config/db.php';
$userId = (int) $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

function respond(int $status, array $data): void {
    http_response_code($status);
    echo json_encode($data);
    exit;
}
function readInput(): array {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}
function validateProduct(array $input, PDO $pdo): array {
    $name = trim((string)($input['product_name'] ?? ''));
    $categoryId = filter_var($input['category_id'] ?? null, FILTER_VALIDATE_INT);
    $quantityRaw = $input['quantity'] ?? null;
    $unit = trim((string)($input['unit'] ?? 'piece'));
    $brand = trim((string)($input['brand'] ?? ''));
    $purchaseDate = trim((string)($input['purchase_date'] ?? ''));
    $expiryDate = trim((string)($input['expiry_date'] ?? ''));
    $minStockRaw = $input['min_stock'] ?? '0';
    $location = trim((string)($input['storage_location'] ?? ''));
    $notes = trim((string)($input['notes'] ?? ''));

    if ($name === '' || mb_strlen($name) > 120) respond(422, ['success'=>false,'message'=>'Enter a product name (maximum 120 characters).']);
    if (!$categoryId) respond(422, ['success'=>false,'message'=>'Please choose a category.']);
    if (!is_numeric($quantityRaw) || (float)$quantityRaw <= 0) respond(422, ['success'=>false,'message'=>'Quantity must be greater than zero.']);
    if ($unit === '' || mb_strlen($unit) > 30) respond(422, ['success'=>false,'message'=>'Enter a valid unit (maximum 30 characters).']);
    if (mb_strlen($brand) > 100) respond(422, ['success'=>false,'message'=>'Brand must be 100 characters or fewer.']);
    if (!is_numeric($minStockRaw) || (float)$minStockRaw < 0) respond(422, ['success'=>false,'message'=>'Minimum stock must be zero or greater.']);
    if (mb_strlen($location) > 50) respond(422, ['success'=>false,'message'=>'Storage location must be 50 characters or fewer.']);
    foreach ([$purchaseDate, $expiryDate] as $date) {
        if ($date !== '') {
            $parsed = DateTime::createFromFormat('Y-m-d', $date);
            if (!$parsed || $parsed->format('Y-m-d') !== $date) respond(422, ['success'=>false,'message'=>'Please enter valid dates.']);
        }
    }
    $cat = $pdo->prepare('SELECT category_id FROM categories WHERE category_id = ?');
    $cat->execute([$categoryId]);
    if (!$cat->fetchColumn()) respond(422, ['success'=>false,'message'=>'That category does not exist. Refresh and try again.']);
    return [$categoryId, $name, $brand ?: null, (float)$quantityRaw, $unit, $purchaseDate ?: null, $expiryDate ?: null, (float)$minStockRaw, $location ?: null, $notes ?: null];
}

try {
    if ($method === 'GET') {
        $categories = $pdo->query('SELECT category_id, category_name FROM categories ORDER BY category_name')->fetchAll(PDO::FETCH_ASSOC);
        $stmt = $pdo->prepare(
            'SELECT p.product_id, p.product_name, p.brand, p.quantity, p.unit,
                    p.purchase_date, p.expiry_date, p.min_stock, p.storage_location,
                    p.notes, p.category_id, c.category_name
             FROM products p INNER JOIN categories c ON c.category_id = p.category_id
             WHERE p.user_id = ?
             ORDER BY CASE WHEN p.expiry_date IS NULL THEN 1 ELSE 0 END, p.expiry_date ASC, p.product_name ASC'
        );
        $stmt->execute([$userId]);
        respond(200, ['success'=>true, 'categories'=>$categories, 'products'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }

    if ($method === 'POST') {
        $values = validateProduct(readInput(), $pdo);
        $stmt = $pdo->prepare('INSERT INTO products (user_id, category_id, product_name, brand, quantity, unit, purchase_date, expiry_date, min_stock, storage_location, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute(array_merge([$userId], $values));
        respond(201, ['success'=>true, 'message'=>'Product added to your pantry!', 'product_id'=>(int)$pdo->lastInsertId()]);
    }

    if ($method === 'PUT') {
        $input = readInput();
        $productId = filter_var($input['product_id'] ?? null, FILTER_VALIDATE_INT);
        if (!$productId) respond(422, ['success'=>false,'message'=>'A valid product ID is required.']);
        $values = validateProduct($input, $pdo);
        $stmt = $pdo->prepare('UPDATE products SET category_id=?, product_name=?, brand=?, quantity=?, unit=?, purchase_date=?, expiry_date=?, min_stock=?, storage_location=?, notes=? WHERE product_id=? AND user_id=?');
        $stmt->execute(array_merge($values, [$productId, $userId]));
        if ($stmt->rowCount() === 0) {
            $check = $pdo->prepare('SELECT product_id FROM products WHERE product_id=? AND user_id=?');
            $check->execute([$productId, $userId]);
            if (!$check->fetchColumn()) respond(404, ['success'=>false,'message'=>'Product not found in your pantry.']);
        }
        respond(200, ['success'=>true, 'message'=>'Product updated successfully.']);
    }

    if ($method === 'DELETE') {
        $input = readInput();
        $productId = filter_var($input['product_id'] ?? null, FILTER_VALIDATE_INT);
        if (!$productId) respond(422, ['success'=>false,'message'=>'A valid product ID is required.']);
        $stmt = $pdo->prepare('DELETE FROM products WHERE product_id=? AND user_id=?');
        $stmt->execute([$productId, $userId]);
        if ($stmt->rowCount() === 0) respond(404, ['success'=>false,'message'=>'Product not found in your pantry.']);
        respond(200, ['success'=>true, 'message'=>'Product deleted from your pantry.']);
    }

    header('Allow: GET, POST, PUT, DELETE');
    respond(405, ['success'=>false, 'message'=>'Method not allowed.']);
} catch (PDOException $e) {
    error_log('PantryPal products API: ' . $e->getMessage());
    respond(500, ['success'=>false, 'message'=>'Something went wrong while processing pantry data. Please try again.']);
}
