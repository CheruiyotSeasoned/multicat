<?php

class Category {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function getCategories() {
        // Get all root categories (those with no parents)
        $stmt = $this->db->prepare("
            SELECT c.* 
            FROM categories c
            LEFT JOIN category_relationships cr ON c.cat_id = cr.child_id
            WHERE cr.parent_id IS NULL
        ");
        $stmt->execute();
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Build the category tree
        foreach ($categories as &$category) {
            $category['children'] = $this->getSubCategories($category['cat_id']);
            $category['items'] = $this->getCategoryItems($category['cat_id']);
        }
        
        return $categories;
    }
    
    private function getSubCategories($parentId) {
        $stmt = $this->db->prepare("
            SELECT c.* 
            FROM categories c
            JOIN category_relationships cr ON c.cat_id = cr.child_id
            WHERE cr.parent_id = ?
        ");
        $stmt->execute([$parentId]);
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($categories as &$category) {
            $category['children'] = $this->getSubCategories($category['cat_id']);
            $category['items'] = $this->getCategoryItems($category['cat_id']);
        }
        
        return $categories;
    }

    private function getCategoryItems($categoryId) {
        $stmt = $this->db->prepare("
            SELECT i.* 
            FROM items i
            JOIN item_categories ic ON i.item_id = ic.item_id
            WHERE ic.cat_id = ?
        ");
        $stmt->execute([$categoryId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function displayCategoryTree($categories, $level = 0) {
        $html = '<ul class="category-tree">';
        
        foreach ($categories as $category) {
            $childCount = count($category['children']);
            $levelClass = 'category-level-' . $level;
            
            // Get parent categories for display
            $parents = $this->getParentNames($category['cat_id']);
            $parentText = $parents ? sprintf(
                '<div class="category-parents">Parent Categories: %s</div>',
                htmlspecialchars(implode(', ', $parents))
            ) : '';
            
            $html .= sprintf(
                '<li class="%s">
                    <div class="category-item">
                        <div class="category-header">
                            <div class="category-info">
                                <span class="category-name">%s</span>
                                <span class="category-count">(%d subcategories)</span>
                            </div>
                            <div class="category-actions">
                                <button onclick="editCategory(%d)" class="btn-edit">Edit</button>
                                <button onclick="deleteCategory(%d)" class="btn-delete">Delete</button>
                            </div>
                        </div>
                        %s
                    </div>',
                $levelClass,
                htmlspecialchars($category['cat_name']),
                $childCount,
                $category['cat_id'],
                $category['cat_id'],
                $parentText
            );
            
            if (!empty($category['children'])) {
                $html .= $this->displayCategoryTree($category['children'], $level + 1);
            }
            
            $html .= '</li>';
        }
        
        $html .= '</ul>';
        return $html;
    }

    private function getParentNames($categoryId) {
        $stmt = $this->db->prepare("
            SELECT c.cat_name
            FROM categories c
            JOIN category_relationships cr ON c.cat_id = cr.parent_id
            WHERE cr.child_id = ?
        ");
        $stmt->execute([$categoryId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getCategoryCount() {
        $stmt = $this->db->query("SELECT COUNT(*) FROM categories");
        return $stmt->fetchColumn();
    }

    public function createCategory($name, $parentIds = []) {
        $this->db->beginTransaction();
        try {
            // Insert the category
            $stmt = $this->db->prepare("INSERT INTO categories (cat_name) VALUES (?)");
            $stmt->execute([$name]);
            $newCategoryId = $this->db->lastInsertId();
            
            // Create relationships with parent categories
            foreach ($parentIds as $parentId) {
                if ($parentId > 0) {
                    $stmt = $this->db->prepare(
                        "INSERT INTO category_relationships (parent_id, child_id) VALUES (?, ?)"
                    );
                    $stmt->execute([$parentId, $newCategoryId]);
                }
            }
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function getCategory($id) {
        // Get category details
        $stmt = $this->db->prepare("SELECT * FROM categories WHERE cat_id = ?");
        $stmt->execute([$id]);
        $category = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($category) {
            // Get parent IDs
            $stmt = $this->db->prepare("
                SELECT parent_id 
                FROM category_relationships 
                WHERE child_id = ?
            ");
            $stmt->execute([$id]);
            $category['parent_ids'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
        }
        
        return $category;
    }

    public function updateCategory($id, $name, $parentIds = []) {
        $this->db->beginTransaction();
        try {
            // Update category name
            $stmt = $this->db->prepare("UPDATE categories SET cat_name = ? WHERE cat_id = ?");
            $stmt->execute([$name, $id]);
            
            // Remove existing relationships
            $stmt = $this->db->prepare("DELETE FROM category_relationships WHERE child_id = ?");
            $stmt->execute([$id]);
            
            // Add new relationships
            if (!empty($parentIds)) {
                foreach ($parentIds as $parentId) {
                    if ($parentId > 0 && $parentId != $id) { // Prevent self-referencing
                        $stmt = $this->db->prepare(
                            "INSERT INTO category_relationships (parent_id, child_id) VALUES (?, ?)"
                        );
                        $stmt->execute([$parentId, $id]);
                    }
                }
            }
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function deleteCategory($id) {
        $this->db->beginTransaction();
        try {
            // First delete all relationships where this category is a parent
            $stmt = $this->db->prepare("DELETE FROM category_relationships WHERE parent_id = ?");
            $stmt->execute([$id]);
            
            // Then delete all relationships where this category is a child
            $stmt = $this->db->prepare("DELETE FROM category_relationships WHERE child_id = ?");
            $stmt->execute([$id]);
            
            // Finally delete the category itself
            $stmt = $this->db->prepare("DELETE FROM categories WHERE cat_id = ?");
            $stmt->execute([$id]);
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function getCategoryDropdown() {
        $stmt = $this->db->query("SELECT cat_id, cat_name FROM categories ORDER BY cat_name");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function displayMenuItems() {
        $stmt = $this->db->query("
            SELECT i.*, GROUP_CONCAT(c.cat_name) as categories
            FROM items i
            LEFT JOIN item_categories ic ON i.item_id = ic.item_id
            LEFT JOIN categories c ON ic.cat_id = c.cat_id
            GROUP BY i.item_id
            ORDER BY i.item_name
        ");
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $html = '<div class="items-list">';
        foreach ($items as $item) {
            $html .= sprintf(
                '<div class="item-card">
                    <h3>%s</h3>
                    <p class="price">$%.2f</p>
                    <p class="description">%s</p>
                    <p class="categories">Categories: %s</p>
                    <div class="item-actions">
                        <button onclick="editItem(%d)" class="btn-edit">Edit</button>
                        <button onclick="deleteItem(%d)" class="btn-delete">Delete</button>
                    </div>
                </div>',
                htmlspecialchars($item['item_name']),
                $item['price'],
                htmlspecialchars($item['description']),
                htmlspecialchars($item['categories'] ?? 'None'),
                $item['item_id'],
                $item['item_id']
            );
        }
        $html .= '</div>';
        return $html;
    }

    public function displayMenuByCategories() {
        if (isset($_POST['category_id']) && $_POST['category_id'] !== '') {
            // Get the selected category and all its child categories
            $stmt = $this->db->prepare("
                WITH RECURSIVE category_tree AS (
                    -- Base case: selected category
                    SELECT cat_id FROM categories WHERE cat_id = ?
                    UNION ALL
                    -- Recursive case: child categories
                    SELECT c.cat_id
                    FROM categories c
                    JOIN category_relationships cr ON c.cat_id = cr.child_id
                    JOIN category_tree ct ON ct.cat_id = cr.parent_id
                )
                SELECT i.*, GROUP_CONCAT(c.cat_name) as categories
                FROM items i
                JOIN item_categories ic ON i.item_id = ic.item_id
                LEFT JOIN categories c ON ic.cat_id = c.cat_id
                WHERE ic.cat_id IN (SELECT cat_id FROM category_tree)
                GROUP BY i.item_id
                ORDER BY i.item_name
            ");
            $stmt->execute([$_POST['category_id']]);
            
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $html = '<div class="menu-categories">';
            
            foreach ($results as $item) {
                $html .= sprintf(
                    '<div class="menu-item">
                        <h3>%s</h3>
                        <p class="price">$%.2f</p>
                        <p class="description">%s</p>
                        <p class="categories">Categories: %s</p>
                    </div>',
                    htmlspecialchars($item['item_name']),
                    $item['price'],
                    htmlspecialchars($item['description']),
                    htmlspecialchars($item['categories'])
                );
            }
        } else {
            // Show all items grouped by category (existing code)
            $stmt = $this->db->query("
                SELECT c.cat_name as category_name,
                       GROUP_CONCAT(
                           CONCAT_WS('|',
                               i.item_name,
                               i.price,
                               i.description,
                               i.item_id
                           )
                       ) as items
                FROM categories c
                LEFT JOIN item_categories ic ON c.cat_id = ic.cat_id
                LEFT JOIN items i ON ic.item_id = i.item_id
                GROUP BY c.cat_id
                ORDER BY c.cat_name
            ");
            
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $html = '<div class="menu-categories">';
            
            foreach ($results as $category) {
                if ($category['items']) {
                    $html .= $this->renderCategoryGroup($category);
                }
            }
        }
        
        $html .= '</div>';
        return $html;
    }

    private function renderCategoryGroup($category) {
        $html = sprintf('<div class="menu-category">
            <h2>%s</h2>
            <div class="menu-items">',
            htmlspecialchars($category['category_name'])
        );
        
        $items = array_map(function($item) {
            return explode('|', $item);
        }, explode(',', $category['items']));
        
        foreach ($items as $item) {
            if (count($item) >= 4) {
                $html .= sprintf(
                    '<div class="menu-item">
                        <h3>%s</h3>
                        <p class="price">$%.2f</p>
                        <p class="description">%s</p>
                    </div>',
                    htmlspecialchars($item[0]),
                    floatval($item[1]),
                    htmlspecialchars($item[2])
                );
            }
        }
        
        $html .= '</div></div>';
        return $html;
    }

    public function getRootCategories() {
        // Get all categories that have children
        $stmt = $this->db->query("
            SELECT DISTINCT c.* 
            FROM categories c
            JOIN category_relationships cr ON c.cat_id = cr.parent_id
            ORDER BY c.cat_name
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getChildCategories($parentId) {
        // Get direct children and categories that share items with the parent
        $stmt = $this->db->prepare("
            SELECT DISTINCT c.* 
            FROM categories c
            LEFT JOIN category_relationships cr ON c.cat_id = cr.child_id
            LEFT JOIN item_categories ic1 ON c.cat_id = ic1.cat_id
            LEFT JOIN item_categories ic2 ON ic1.item_id = ic2.item_id
            WHERE cr.parent_id = ? 
               OR ic2.cat_id = ?
            ORDER BY c.cat_name
        ");
        $stmt->execute([$parentId, $parentId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} 