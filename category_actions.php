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
            $parentIds = $_POST['parent_ids'] ?? [];
            $result = $categoryManager->createCategory($name, $parentIds);
            echo json_encode(['success' => $result]);
            break;
            
        case 'update':
            $id = $_POST['id'] ?? 0;
            $name = $_POST['name'] ?? '';
            $parentIds = $_POST['parent_ids'] ?? [];
            
            if (!$id || !$name) {
                echo json_encode(['error' => 'Missing required fields']);
                break;
            }
            
            try {
                $result = $categoryManager->updateCategory($id, $name, $parentIds);
                echo json_encode(['success' => $result]);
            } catch (Exception $e) {
                echo json_encode(['error' => $e->getMessage()]);
            }
            break;
            
        case 'delete':
            $id = $_POST['id'] ?? 0;
            $result = $categoryManager->deleteCategory($id);
            echo json_encode(['success' => $result]);
            break;
            
        case 'get':
            $id = $_POST['id'] ?? 0;
            $category = $categoryManager->getCategory($id);
            echo json_encode($category);
            break;
            
        default:
            echo json_encode(['error' => 'Invalid action']);
    }
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
} 