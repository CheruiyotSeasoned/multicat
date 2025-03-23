-- Create the database
CREATE DATABASE IF NOT EXISTS category_system;

-- Use the database
USE category_system;

-- Categories table
CREATE TABLE `categories` (
    `cat_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `cat_name` varchar(50) NOT NULL,
    PRIMARY KEY (`cat_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Category relationships table (for hierarchical structure)
CREATE TABLE `category_relationships` (
    `parent_id` int(10) unsigned NOT NULL,
    `child_id` int(10) unsigned NOT NULL,
    PRIMARY KEY (`parent_id`, `child_id`),
    FOREIGN KEY (`parent_id`) REFERENCES categories(`cat_id`),
    FOREIGN KEY (`child_id`) REFERENCES categories(`cat_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Items table with price and description
CREATE TABLE `items` (
    `item_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `item_name` varchar(100) NOT NULL,
    `price` decimal(10,2) NOT NULL DEFAULT '0.00',
    `description` text,
    PRIMARY KEY (`item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Item-category relationships (many-to-many)
CREATE TABLE `item_categories` (
    `item_id` int(10) unsigned NOT NULL,
    `cat_id` int(10) unsigned NOT NULL,
    PRIMARY KEY (`item_id`, `cat_id`),
    FOREIGN KEY (`item_id`) REFERENCES items(`item_id`),
    FOREIGN KEY (`cat_id`) REFERENCES categories(`cat_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

