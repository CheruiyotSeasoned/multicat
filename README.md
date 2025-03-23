# Restaurant Menu Category Management System

A PHP-based menu management system demonstrating hierarchical category organization with a focus on restaurant menu items. Features multi-parent categories, dynamic filtering, and a clean user interface.

![Category Management Interface](screenshots/categories.png)

## Core Features

### 1. Category System
- Multi-level category hierarchy (e.g., Foods → Common → Appetizers)
- Multiple parent categories support
- Category grouping by:
  - Traditional menu sections (Appetizers, Main Courses)
  - Cuisine types (Asian, Italian)
  - Dietary preferences (Gluten Free, Vegan)
  - Price segments (Value Menu, Premium)

### 2. Menu Items
- Items with prices and descriptions
- Multiple category assignments
- Grid-based item display
- Category-based filtering

### 3. Menu Display
- Interactive category pills for filtering
- Hierarchical category navigation
- Responsive grid layout
- Real-time AJAX filtering

### 3. Key SQL Queries Implemented

#### Getting Root Categories

#### Recursive Category Tree

## Project Structure

## Codebase Structure

### Core Files 

## Key Components

### Category Management
- Tree-based category display
- Edit/Delete functionality
- Parent category selection
- Subcategory tracking

### Item Management
- Grid-based item display
- Price and description fields
- Multiple category assignment
- Edit/Delete capabilities

### Menu Display
- Category-based filtering
- Hierarchical navigation
- Price and description display
- Real-time updates

## Implementation Notes

### Category Hierarchy
- Uses adjacency list model with relationships table
- Supports multiple parent-child relationships
- Prevents circular references
- Maintains category counts

### Item Categorization
- Many-to-many relationship with categories
- Inherits parent category associations
- Supports flexible categorization
- Enables efficient filtering

### UI/UX Considerations
- Clean, intuitive interface
- Real-time category filtering
- Responsive design
- Clear action buttons

## License

MIT License - Free to use and modify for your own projects.

## Contributing

Contributions welcome for:
1. Additional features
2. UI improvements
3. Performance optimizations
4. Documentation enhancements 

## Key Takeaways

1. **Flexible Architecture**: Supports complex category relationships while maintaining data integrity

2. **Efficient Queries**: Uses optimized SQL for tree operations and filtering

3. **Clean Implementation**: Demonstrates proper separation of concerns and error handling

4. **Scalable Design**: Supports growing category structures and item collections

This implementation serves as a practical example of handling complex categorization systems in web applications.

### Item Operations

## Learning Points

### 1. Database Design
- Many-to-many relationships
- Hierarchical data structures
- Transaction management
- Foreign key constraints

### 2. PHP Patterns
- Class-based organization
- PDO for database operations
- Error handling
- Transaction management

### 3. Category Management
- Multi-parent relationships
- Tree structure handling
- Recursive operations
- Data integrity

### 4. Item Categorization
- Multiple category assignment
- Hierarchical filtering
- Dynamic category updates

## Project Structure
```
project/
├── Category.php          # Core category management logic
├── category_actions.php  # CRUD operations handler
├── item_actions.php      # Item operations handler
├── index.php            # Main interface
├── database.sql         # Database schema
└── styles.css          # UI styling
```

## Usage Examples

### Category Operations
```php
// Create category with multiple parents
$categoryManager->createCategory('Appetizers', [1, 2]);

// Update category relationships
$categoryManager->updateCategory($id, $name, $newParentIds);

// Get category tree
$tree = $categoryManager->getCategories();
```

### Item Operations
```php
// Create item with multiple categories
$itemManager->createItem($name, $price, $description, $categoryIds);

// Filter items by category
$items = $categoryManager->displayMenuByCategories($categoryId);
```

## Key Takeaways

1. **Flexible Architecture**: Supports complex category relationships while maintaining data integrity

2. **Efficient Queries**: Uses optimized SQL for tree operations and filtering

3. **Clean Implementation**: Demonstrates proper separation of concerns and error handling

4. **Scalable Design**: Supports growing category structures and item collections

This implementation serves as a practical example of handling complex categorization systems in web applications. 