<?php
require_once 'Category.php';

try {
    // Database connection with port 3309
    $db = new PDO(
        "mysql:host=localhost;port=3309;dbname=category_system",
        "root",
        "",
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // Initialize Category class
    $categoryManager = new Category($db);
    
    // Get all categories
    $categories = $categoryManager->getCategories();
    $totalCategories = $categoryManager->getCategoryCount();
    
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Category Management System</title>
        <link rel="stylesheet" href="styles.css">
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>Category Management System</h1>
                <p>Total Categories: <?php echo $totalCategories; ?></p>
            </div>
            
            <div class="tab-container">
                <button class="tab-button active" onclick="switchTab('categories')">Categories</button>
                <button class="tab-button" onclick="switchTab('items')">Menu Items</button>
                <button class="tab-button" onclick="switchTab('menu')">Menu Display</button>
            </div>

            <div id="categories-tab" class="tab-content active">
                <div class="actions-bar">
                    <button onclick="showAddCategory()" class="btn-primary">Add New Category</button>
                </div>
                
                <?php echo $categoryManager->displayCategoryTree($categories); ?>
            </div>

            <div id="items-tab" class="tab-content">
                <div class="actions-bar">
                    <button onclick="showAddItem()" class="btn-primary">Add New Menu Item</button>
                </div>
                
                <div class="items-grid">
                    <?php echo $categoryManager->displayMenuItems(); ?>
                </div>
            </div>

            <div id="menu-tab" class="tab-content">
                <div class="menu-filters">
                    <div class="category-pills">
                        <button class="category-pill active" data-id="" onclick="filterMenu(this, '')">All</button>
                        <?php
                        // Get root categories first
                        $rootCategories = $categoryManager->getRootCategories();
                        foreach($rootCategories as $root) {
                            echo sprintf(
                                '<div class="category-group">
                                    <button class="category-pill" data-id="%d" onclick="filterMenu(this, %d)">%s</button>
                                    <div class="sub-categories">',
                                $root['cat_id'],
                                $root['cat_id'],
                                htmlspecialchars($root['cat_name'])
                            );
                            
                            // Get child categories
                            $children = $categoryManager->getChildCategories($root['cat_id']);
                            foreach($children as $child) {
                                echo sprintf(
                                    '<button class="category-pill sub" data-id="%d" onclick="filterMenu(this, %d)">%s</button>',
                                    $child['cat_id'],
                                    $child['cat_id'],
                                    htmlspecialchars($child['cat_name'])
                                );
                            }
                            
                            echo '</div></div>';
                        }
                        ?>
                    </div>
                </div>
                
                <div id="menuDisplay" class="menu-display">
                    <?php echo $categoryManager->displayMenuByCategories(); ?>
                </div>
            </div>
        </div>

        <!-- Add this before closing body tag -->
        <div id="categoryModal" class="modal" onclick="closeModalOnBackground(event)">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 id="modalTitle">Add Category</h2>
                    <button type="button" class="btn-close" onclick="closeModal()">&times;</button>
                </div>
                <form id="categoryForm">
                    <input type="hidden" id="categoryId">
                    <div class="form-group">
                        <label for="categoryName">Category Name</label>
                        <input type="text" id="categoryName" required>
                    </div>
                    <div class="form-group">
                        <label for="parentCategories">Parent Categories</label>
                        <select id="parentCategories" multiple size="5">
                            <?php
                            $categories = $categoryManager->getCategoryDropdown();
                            foreach($categories as $cat) {
                                echo sprintf(
                                    '<option value="%d">%s</option>',
                                    $cat['cat_id'],
                                    htmlspecialchars($cat['cat_name'])
                                );
                            }
                            ?>
                        </select>
                        <small class="help-text">Hold Ctrl/Cmd to select multiple categories</small>
                    </div>
                    <button type="submit" class="btn-primary">Save</button>
                </form>
            </div>
        </div>

        <!-- Add this new modal for menu items -->
        <div id="itemModal" class="modal" onclick="closeModalOnBackground(event)">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 id="itemModalTitle">Add Menu Item</h2>
                    <button type="button" class="btn-close" onclick="closeItemModal()">&times;</button>
                </div>
                <form id="itemForm">
                    <input type="hidden" id="itemId">
                    <div class="form-group">
                        <label for="itemName">Item Name</label>
                        <input type="text" id="itemName" required>
                    </div>
                    <div class="form-group">
                        <label for="itemPrice">Price</label>
                        <input type="number" id="itemPrice" step="0.01" min="0" required>
                    </div>
                    <div class="form-group">
                        <label for="itemDescription">Description</label>
                        <textarea id="itemDescription" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="itemCategories">Categories</label>
                        <select id="itemCategories" multiple size="5">
                            <?php
                            $categories = $categoryManager->getCategoryDropdown();
                            foreach($categories as $cat) {
                                echo sprintf(
                                    '<option value="%d">%s</option>',
                                    $cat['cat_id'],
                                    htmlspecialchars($cat['cat_name'])
                                );
                            }
                            ?>
                        </select>
                        <small class="help-text">Hold Ctrl/Cmd to select multiple categories</small>
                    </div>
                    <button type="submit" class="btn-primary">Save</button>
                </form>
            </div>
        </div>

        <script>
        function showAddCategory() {
            document.getElementById('categoryId').value = '';
            document.getElementById('categoryName').value = '';
            document.getElementById('parentCategories').selectedIndex = -1; // Clear selection
            document.getElementById('modalTitle').textContent = 'Add New Category';
            document.getElementById('categoryModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('categoryModal').style.display = 'none';
            document.getElementById('categoryForm').reset();
        }

        function closeModalOnBackground(event) {
            if (event.target === event.currentTarget) {
                closeModal();
            }
        }

        function editCategory(id) {
            fetch('category_actions.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `action=get&id=${id}`
            })
            .then(response => response.json())
            .then(category => {
                document.getElementById('categoryId').value = category.cat_id;
                document.getElementById('categoryName').value = category.cat_name;
                
                // Clear and set multiple parent categories
                const select = document.getElementById('parentCategories');
                Array.from(select.options).forEach(option => {
                    option.selected = category.parent_ids.includes(parseInt(option.value));
                });
                
                document.getElementById('modalTitle').textContent = 'Edit Category';
                document.getElementById('categoryModal').style.display = 'block';
            });
        }

        function deleteCategory(id) {
            if(confirm('Are you sure you want to delete this category?')) {
                fetch('category_actions.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `action=delete&id=${id}`
                })
                .then(response => response.json())
                .then(result => {
                    if(result.success) {
                        location.reload();
                    }
                });
            }
        }

        document.getElementById('categoryForm').onsubmit = function(e) {
            e.preventDefault();
            const id = document.getElementById('categoryId').value;
            const name = document.getElementById('categoryName').value;
            
            // Get all selected parent categories
            const parentSelect = document.getElementById('parentCategories');
            const parentIds = Array.from(parentSelect.selectedOptions).map(option => option.value);
            
            const formData = new FormData();
            formData.append('action', id ? 'update' : 'create');
            formData.append('name', name);
            if (id) {
                formData.append('id', id);
            }
            
            // Append each parent ID separately
            parentIds.forEach(pid => {
                formData.append('parent_ids[]', pid);
            });
            
            fetch('category_actions.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(result => {
                if(result.success) {
                    closeModal();
                    location.reload();
                } else {
                    alert(result.error || 'An error occurred');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while saving the category');
            });
        };

        function switchTab(tabName) {
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelectorAll('.tab-button').forEach(button => {
                button.classList.remove('active');
            });
            
            document.getElementById(tabName + '-tab').classList.add('active');
            document.querySelector(`[onclick="switchTab('${tabName}')"]`).classList.add('active');
        }

        function showAddItem() {
            document.getElementById('itemId').value = '';
            document.getElementById('itemName').value = '';
            document.getElementById('itemPrice').value = '';
            document.getElementById('itemDescription').value = '';
            document.getElementById('itemCategories').selectedIndex = -1;
            document.getElementById('itemModalTitle').textContent = 'Add Menu Item';
            document.getElementById('itemModal').style.display = 'block';
        }

        function closeItemModal() {
            document.getElementById('itemModal').style.display = 'none';
            document.getElementById('itemForm').reset();
        }

        function editItem(id) {
            fetch('item_actions.php', {
                method: 'POST',
                body: new URLSearchParams({
                    action: 'get',
                    id: id
                })
            })
            .then(response => response.json())
            .then(item => {
                document.getElementById('itemId').value = item.item_id;
                document.getElementById('itemName').value = item.item_name;
                document.getElementById('itemPrice').value = item.price;
                document.getElementById('itemDescription').value = item.description;
                
                const select = document.getElementById('itemCategories');
                Array.from(select.options).forEach(option => {
                    option.selected = item.category_ids.includes(parseInt(option.value));
                });
                
                document.getElementById('itemModalTitle').textContent = 'Edit Menu Item';
                document.getElementById('itemModal').style.display = 'block';
            });
        }

        function deleteItem(id) {
            if(confirm('Are you sure you want to delete this menu item?')) {
                fetch('item_actions.php', {
                    method: 'POST',
                    body: new URLSearchParams({
                        action: 'delete',
                        id: id
                    })
                })
                .then(response => response.json())
                .then(result => {
                    if(result.success) {
                        location.reload();
                    } else {
                        alert(result.error || 'An error occurred');
                    }
                });
            }
        }

        document.getElementById('itemForm').onsubmit = function(e) {
            e.preventDefault();
            const formData = new FormData();
            
            formData.append('action', document.getElementById('itemId').value ? 'update' : 'create');
            formData.append('name', document.getElementById('itemName').value);
            formData.append('price', document.getElementById('itemPrice').value);
            formData.append('description', document.getElementById('itemDescription').value);
            
            if(document.getElementById('itemId').value) {
                formData.append('id', document.getElementById('itemId').value);
            }
            
            Array.from(document.getElementById('itemCategories').selectedOptions)
                .forEach(option => formData.append('category_ids[]', option.value));
            
            fetch('item_actions.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(result => {
                if(result.success) {
                    closeItemModal();
                    location.reload();
                } else {
                    alert(result.error || 'An error occurred');
                }
            });
        };

        function filterMenu(button, categoryId) {
            // Update active state of buttons
            document.querySelectorAll('.category-pill').forEach(pill => {
                pill.classList.remove('active');
            });
            button.classList.add('active');

            // Filter the menu
            fetch('item_actions.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `action=filter&category_id=${categoryId}`
            })
            .then(response => response.json())
            .then(html => {
                document.getElementById('menuDisplay').innerHTML = html.content;
            });
        }
        </script>
    </body>
    </html>
    <?php
    
} catch (PDOException $e) {
    echo '<div class="error">Database Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
} 