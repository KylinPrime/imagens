<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

$category = $_GET['category'] ?? '';
$search = $_GET['search'] ?? '';
$offset = (int)($_GET['offset'] ?? 0);
$limit = (int)($_GET['limit'] ?? 20);

// Monta query
$query = "SELECT i.*, c.name as category_name 
          FROM images i 
          JOIN categories c ON i.category = c.id 
          WHERE 1=1";
$params = [];

if ($category) {
    $query .= " AND i.category = ?";
    $params[] = $category;
}

if ($search) {
    $query .= " AND i.sku LIKE ?";
    $params[] = "%$search%";
}

$query .= " ORDER BY i.created_at DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$images = $stmt->fetchAll();

// Formata resultado
$result = array_map(function($img) {
    return [
        'id' => $img['id'],
        'sku' => $img['sku'],
        'category' => $img['category_name'],
        'background_type' => $img['background_type'],
        'created_at' => $img['created_at'],
        'thumbnail' => 'uploads/thumbnails/' . $img['sku'] . '.webp',
        'carousel' => 'uploads/carousel/' . $img['sku'] . '.webp',
        'original' => 'uploads/original/' . $img['sku'] . '.' . $img['file_extension']
    ];
}, $images);

jsonResponse([
    'success' => true,
    'images' => $result,
    'has_more' => count($images) === $limit
]);
?>