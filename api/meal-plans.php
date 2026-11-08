<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

function mealRespond(int $status, array $payload): void {
    http_response_code($status);
    echo json_encode($payload);
    exit;
}
function mealInput(): array {
    $data = json_decode(file_get_contents('php://input'), true);
    return is_array($data) ? $data : [];
}
function validMealDate(string $date): bool {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

if (!isset($_SESSION['user_id'])) mealRespond(401,['success'=>false,'message'=>'Please log in again.']);
require_once __DIR__.'/../config/db.php';
$userId=(int)$_SESSION['user_id'];
$method=$_SERVER['REQUEST_METHOD'];
$mealTypes=['breakfast','lunch','snacks','dinner'];

try {
    if ($method==='GET') {
        $start=$_GET['start'] ?? '';
        $end=$_GET['end'] ?? '';
        if (!validMealDate($start) || !validMealDate($end) || $start>$end) mealRespond(422,['success'=>false,'message'=>'A valid week date range is required.']);
        $stmt=$pdo->prepare('SELECT plan_id, meal_date, meal_type, recipe_source, recipe_id, recipe_name, recipe_details FROM meal_plans WHERE user_id=? AND meal_date BETWEEN ? AND ? ORDER BY meal_date, FIELD(meal_type, "breakfast", "lunch", "snacks", "dinner")');
        $stmt->execute([$userId,$start,$end]);
        mealRespond(200,['success'=>true,'plans'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }

    if ($method==='POST' || $method==='PUT') {
        $input=mealInput();
        $date=trim((string)($input['meal_date'] ?? ''));
        $type=strtolower(trim((string)($input['meal_type'] ?? '')));
        $name=trim((string)($input['recipe_name'] ?? ''));
        $source=trim((string)($input['recipe_source'] ?? 'manual'));
        $recipeId=trim((string)($input['recipe_id'] ?? ''));
        $details=trim((string)($input['recipe_details'] ?? ''));
        if (!validMealDate($date)) mealRespond(422,['success'=>false,'message'=>'Choose a valid meal date.']);
        if (!in_array($type,$mealTypes,true)) mealRespond(422,['success'=>false,'message'=>'Choose breakfast, lunch, snacks or dinner.']);
        if ($name==='' || mb_strlen($name)>160) mealRespond(422,['success'=>false,'message'=>'Choose or enter a recipe name (maximum 160 characters).']);
        if (!in_array($source,['manual','pantrypal','themealdb'],true)) $source='manual';
        if (mb_strlen($recipeId)>100 || mb_strlen($details)>10000) mealRespond(422,['success'=>false,'message'=>'Recipe information is too long.']);

        $existing=$pdo->prepare('SELECT plan_id FROM meal_plans WHERE user_id=? AND meal_date=? AND meal_type=?');
        $existing->execute([$userId,$date,$type]);
        $existingId=$existing->fetchColumn();
        if ($existingId) {
            $stmt=$pdo->prepare('UPDATE meal_plans SET recipe_source=?, recipe_id=?, recipe_name=?, recipe_details=? WHERE plan_id=? AND user_id=?');
            $stmt->execute([$source,$recipeId ?: null,$name,$details ?: null,$existingId,$userId]);
            mealRespond(200,['success'=>true,'plan_id'=>(int)$existingId,'message'=>'Meal plan updated.']);
        }
        $stmt=$pdo->prepare('INSERT INTO meal_plans (user_id, meal_date, meal_type, recipe_source, recipe_id, recipe_name, recipe_details) VALUES (?,?,?,?,?,?,?)');
        $stmt->execute([$userId,$date,$type,$source,$recipeId ?: null,$name,$details ?: null]);
        mealRespond(201,['success'=>true,'plan_id'=>(int)$pdo->lastInsertId(),'message'=>'Meal added to your week.']);
    }

    if ($method==='DELETE') {
        $input=mealInput();
        $id=filter_var($input['plan_id'] ?? null,FILTER_VALIDATE_INT);
        if (!$id) mealRespond(422,['success'=>false,'message'=>'A valid meal plan ID is required.']);
        $stmt=$pdo->prepare('DELETE FROM meal_plans WHERE plan_id=? AND user_id=?');
        $stmt->execute([$id,$userId]);
        if (!$stmt->rowCount()) mealRespond(404,['success'=>false,'message'=>'Meal plan not found.']);
        mealRespond(200,['success'=>true,'message'=>'Meal removed from your week.']);
    }

    header('Allow: GET, POST, PUT, DELETE');
    mealRespond(405,['success'=>false,'message'=>'Method not allowed.']);
} catch (PDOException $e) {
    error_log('PantryPal meal planner API: '.$e->getMessage());
    mealRespond(500,['success'=>false,'message'=>'Could not save the meal plan. Please try again.']);
}
