<?php
require_once 'Category.php';

try {
    $db = new PDO(
        "mysql:host=localhost;port=3309;dbname=category_system",
        "root",
        "",
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    $categoryManager = new Category($db);
    
    $action = $_POST['action'] ?? '';
    
    switch($action) {
        case 'create':
            $name = $_POST['name'] ?? '';
            $price = $_POST['price'] ?? 0;
            $description = $_POST['description'] ?? '';
            $categoryIds = $_POST['category_ids'] ?? [];
            
            $stmt = $db->prepare("INSERT INTO items (item_name, price, description) VALUES (?, ?, ?)");
            $stmt->execute([$name, $price, $description]);
            $itemId = $db->lastInsertId();
            
            // Add category relationships
            foreach ($categoryIds as $catId) {
                $stmt = $db->prepare("INSERT INTO item_categories (item_id, cat_id) VALUES (?, ?)");
                $stmt->execute([$itemId, $catId]);
            }
            
            echo json_encode(['success' => true]);
            break;
            
        case 'get':
            $id = $_POST['id'] ?? 0;
            $stmt = $db->prepare("SELECT * FROM items WHERE item_id = ?");
            $stmt->execute([$id]);
            $item = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Get category IDs
            $stmt = $db->prepare("SELECT cat_id FROM item_categories WHERE item_id = ?");
            $stmt->execute([$id]);
            $item['category_ids'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            echo json_encode($item);
            break;
            
        case 'update':
            $id = $_POST['id'] ?? 0;
            $name = $_POST['name'] ?? '';
            $price = $_POST['price'] ?? 0;
            $description = $_POST['description'] ?? '';
            $categoryIds = $_POST['category_ids'] ?? [];
            
            $stmt = $db->prepare("UPDATE items SET item_name = ?, price = ?, description = ? WHERE item_id = ?");
            $stmt->execute([$name, $price, $description, $id]);
            
            // Update categories
            $stmt = $db->prepare("DELETE FROM item_categories WHERE item_id = ?");
            $stmt->execute([$id]);
            
            foreach ($categoryIds as $catId) {
                $stmt = $db->prepare("INSERT INTO item_categories (item_id, cat_id) VALUES (?, ?)");
                $stmt->execute([$id, $catId]);
            }
            
            echo json_encode(['success' => true]);
            break;
            
        case 'delete':
            $id = $_POST['id'] ?? 0;
            
            // Delete category relationships first
            $stmt = $db->prepare("DELETE FROM item_categories WHERE item_id = ?");
            $stmt->execute([$id]);
            
            // Delete the item
            $stmt = $db->prepare("DELETE FROM items WHERE item_id = ?");
            $stmt->execute([$id]);
            
            echo json_encode(['success' => true]);
            break;
            
        case 'filter':
            $categoryId = $_POST['category_id'] ?? '';
            $_POST['category_id'] = $categoryId; // Set for displayMenuByCategories method
            $content = $categoryManager->displayMenuByCategories();
            echo json_encode(['content' => $content]);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
} 