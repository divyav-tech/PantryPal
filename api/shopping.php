<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

function shoppingRespond(int $status, array $payload): void {
    http_response_code($status);
    echo json_encode($payload);
    exit;
}
function shoppingInput(): array {
    $data = json_decode(file_get_contents('php://input'), true);
    return is_array($data) ? $data : [];
}
function shoppingDateNumber($value): bool {
    return is_numeric($value) && is_finite((float)$value);
}

if (!isset($_SESSION['user_id'])) {
    shoppingRespond(401, ['success' => false, 'message' => 'Please log in again.']);
}
require_once __DIR__ . '/../config/db.php';
$userId = (int)$_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        if (($_GET['action'] ?? '') === 'suggestions') {
            $stmt = $pdo->prepare(
                'SELECT p.product_id, p.product_name, p.quantity, p.unit, p.min_stock,
                        p.category_id, c.category_name
                 FROM products p
                 LEFT JOIN categories c ON c.category_id = p.category_id
                 WHERE p.user_id = ? AND p.min_stock IS NOT NULL
                   AND p.quantity <= p.min_stock
                   AND NOT EXISTS (
                     SELECT 1 FROM shopping_items s
                     WHERE s.user_id = p.user_id AND s.product_id = p.product_id
                   )
                 ORDER BY p.product_name'
            );
            $stmt->execute([$userId]);
            shoppingRespond(200, ['success' => true, 'suggestions' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        }

        $stmt = $pdo->prepare(
            'SELECT s.item_id, s.product_id, s.category_id, s.item_name, s.quantity,
                    s.unit, s.is_purchased, s.created_at, c.category_name
             FROM shopping_items s
             LEFT JOIN categories c ON c.category_id = s.category_id
             WHERE s.user_id = ?
             ORDER BY s.is_purchased ASC, s.created_at DESC, s.item_id DESC'
        );
        $stmt->execute([$userId]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $categories = $pdo->query('SELECT category_id, category_name FROM categories ORDER BY category_name')->fetchAll(PDO::FETCH_ASSOC);
        shoppingRespond(200, ['success' => true, 'items' => $items, 'categories' => $categories]);
    }

    if ($method === 'POST') {
        $input = shoppingInput();
        $productId = isset($input['product_id']) && $input['product_id'] !== '' ? filter_var($input['product_id'], FILTER_VALIDATE_INT) : null;
        $name = trim((string)($input['item_name'] ?? ''));
        $quantity = $input['quantity'] ?? 1;
        $unit = trim((string)($input['unit'] ?? 'piece'));
        $categoryId = isset($input['category_id']) && $input['category_id'] !== '' ? filter_var($input['category_id'], FILTER_VALIDATE_INT) : null;

        if ($productId !== null && !$productId) shoppingRespond(422, ['success'=>false,'message'=>'Invalid pantry product.']);
        if ($productId) {
            $product = $pdo->prepare('SELECT product_id, product_name, quantity, unit, category_id FROM products WHERE product_id = ? AND user_id = ?');
            $product->execute([$productId, $userId]);
            $row = $product->fetch(PDO::FETCH_ASSOC);
            if (!$row) shoppingRespond(404, ['success'=>false,'message'=>'Pantry product not found.']);
            $name = $row['product_name'];
            $quantity = $row['quantity'];
            $unit = $row['unit'];
            $categoryId = $row['category_id'];
            $duplicate = $pdo->prepare('SELECT item_id FROM shopping_items WHERE user_id = ? AND product_id = ?');
            $duplicate->execute([$userId, $productId]);
            if ($duplicate->fetchColumn()) shoppingRespond(409, ['success'=>false,'message'=>'This pantry item is already on your shopping list.']);
        }

        if ($name === '' || mb_strlen($name) > 120) shoppingRespond(422, ['success'=>false,'message'=>'Enter an item name (maximum 120 characters).']);
        if (!shoppingDateNumber($quantity) || (float)$quantity <= 0) shoppingRespond(422, ['success'=>false,'message'=>'Quantity must be greater than zero.']);
        if ($unit === '' || mb_strlen($unit) > 30) shoppingRespond(422, ['success'=>false,'message'=>'Enter a valid unit (maximum 30 characters).']);
        if ($categoryId !== null && !$categoryId) shoppingRespond(422, ['success'=>false,'message'=>'Choose a valid category.']);
        if ($categoryId !== null) {
            $cat = $pdo->prepare('SELECT category_id FROM categories WHERE category_id = ?');
            $cat->execute([$categoryId]);
            if (!$cat->fetchColumn()) shoppingRespond(422, ['success'=>false,'message'=>'That category does not exist.']);
        }
        $stmt = $pdo->prepare('INSERT INTO shopping_items (user_id, product_id, category_id, item_name, quantity, unit, is_purchased) VALUES (?, ?, ?, ?, ?, ?, 0)');
        $stmt->execute([$userId, $productId, $categoryId, $name, (float)$quantity, $unit]);
        shoppingRespond(201, ['success'=>true,'message'=>'Added to your shopping list.','item_id'=>(int)$pdo->lastInsertId()]);
    }

    if ($method === 'PUT') {
        $input = shoppingInput();
        $id = filter_var($input['item_id'] ?? null, FILTER_VALIDATE_INT);
        $name = trim((string)($input['item_name'] ?? ''));
        $quantity = $input['quantity'] ?? null;
        $unit = trim((string)($input['unit'] ?? ''));
        $categoryId = isset($input['category_id']) && $input['category_id'] !== '' ? filter_var($input['category_id'], FILTER_VALIDATE_INT) : null;
        if (!$id) shoppingRespond(422, ['success'=>false,'message'=>'A valid item ID is required.']);
        if ($name === '' || mb_strlen($name) > 120) shoppingRespond(422, ['success'=>false,'message'=>'Enter a valid item name.']);
        if (!shoppingDateNumber($quantity) || (float)$quantity <= 0) shoppingRespond(422, ['success'=>false,'message'=>'Quantity must be greater than zero.']);
        if ($unit === '' || mb_strlen($unit) > 30) shoppingRespond(422, ['success'=>false,'message'=>'Enter a valid unit.']);
        if ($categoryId !== null) {
            if (!$categoryId) shoppingRespond(422, ['success'=>false,'message'=>'Choose a valid category.']);
            $cat = $pdo->prepare('SELECT category_id FROM categories WHERE category_id = ?');
            $cat->execute([$categoryId]);
            if (!$cat->fetchColumn()) shoppingRespond(422, ['success'=>false,'message'=>'That category does not exist.']);
        }
        $stmt = $pdo->prepare('UPDATE shopping_items SET item_name=?, quantity=?, unit=?, category_id=? WHERE item_id=? AND user_id=?');
        $stmt->execute([$name,(float)$quantity,$unit,$categoryId,$id,$userId]);
        if ($stmt->rowCount() === 0) {
            $check=$pdo->prepare('SELECT item_id FROM shopping_items WHERE item_id=? AND user_id=?');
            $check->execute([$id,$userId]);
            if (!$check->fetchColumn()) shoppingRespond(404,['success'=>false,'message'=>'Shopping item not found.']);
        }
        shoppingRespond(200,['success'=>true,'message'=>'Shopping item updated.']);
    }

    if ($method === 'PATCH') {
        $input = shoppingInput();
        $id = filter_var($input['item_id'] ?? null, FILTER_VALIDATE_INT);
        $purchased = filter_var($input['is_purchased'] ?? null, FILTER_VALIDATE_INT);
        if (!$id || !in_array($purchased, [0,1], true)) shoppingRespond(422,['success'=>false,'message'=>'Invalid purchase status update.']);
        $stmt=$pdo->prepare('UPDATE shopping_items SET is_purchased=? WHERE item_id=? AND user_id=?');
        $stmt->execute([$purchased,$id,$userId]);
        if ($stmt->rowCount()===0) {
            $check=$pdo->prepare('SELECT item_id FROM shopping_items WHERE item_id=? AND user_id=?');
            $check->execute([$id,$userId]);
            if (!$check->fetchColumn()) shoppingRespond(404,['success'=>false,'message'=>'Shopping item not found.']);
        }
        shoppingRespond(200,['success'=>true,'message'=>'Purchase status updated.']);
    }

    if ($method === 'DELETE') {
        if (($_GET['action'] ?? '') === 'clear-purchased') {
            $stmt=$pdo->prepare('DELETE FROM shopping_items WHERE user_id=? AND is_purchased=1');
            $stmt->execute([$userId]);
            shoppingRespond(200,['success'=>true,'deleted'=>$stmt->rowCount(),'message'=>'Purchased items cleared.']);
        }
        $input=shoppingInput();
        $id=filter_var($input['item_id'] ?? $_GET['item_id'] ?? null,FILTER_VALIDATE_INT);
        if (!$id) shoppingRespond(422,['success'=>false,'message'=>'A valid item ID is required.']);
        $stmt=$pdo->prepare('DELETE FROM shopping_items WHERE item_id=? AND user_id=?');
        $stmt->execute([$id,$userId]);
        if (!$stmt->rowCount()) shoppingRespond(404,['success'=>false,'message'=>'Shopping item not found.']);
        shoppingRespond(200,['success'=>true,'message'=>'Shopping item deleted.']);
    }

    header('Allow: GET, POST, PUT, PATCH, DELETE');
    shoppingRespond(405,['success'=>false,'message'=>'Method not allowed.']);
} catch (PDOException $e) {
    error_log('PantryPal shopping API: '.$e->getMessage());
    shoppingRespond(500,['success'=>false,'message'=>'Could not process the shopping list. Please try again.']);
}
