-- Add advanced features to menu system

-- Allergens table
CREATE TABLE IF NOT EXISTS `menu_allergens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name_tr` varchar(100) NOT NULL,
  `name_en` varchar(100) NOT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Item allergens junction table
CREATE TABLE IF NOT EXISTS `menu_item_allergens` (
  `item_id` int(11) NOT NULL,
  `allergen_id` int(11) NOT NULL,
  PRIMARY KEY (`item_id`, `allergen_id`),
  FOREIGN KEY (`item_id`) REFERENCES `business_menu_items` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`allergen_id`) REFERENCES `menu_allergens` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Feedbacks table
CREATE TABLE IF NOT EXISTS `menu_feedbacks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `business_id` int(11) NOT NULL,
  `rating` int(1) NOT NULL,
  `comment` text,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`business_id`) REFERENCES `business_listings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add new columns to menu items
ALTER TABLE `business_menu_items` 
ADD COLUMN `ingredients_tr` TEXT DEFAULT NULL AFTER `composer`,
ADD COLUMN `ingredients_en` TEXT DEFAULT NULL AFTER `ingredients_tr`,
ADD COLUMN `is_chef_special` tinyint(1) DEFAULT 0 AFTER `is_spicy`,
ADD COLUMN `calories` int(11) DEFAULT NULL AFTER `price`,
ADD COLUMN `preparation_time` int(11) DEFAULT NULL AFTER `calories`;

-- Insert default allergens
INSERT INTO `menu_allergens` (`name_tr`, `name_en`, `icon`) VALUES
('Gluten', 'Gluten', 'fa-wheat-awn'),
('Süt Ürünleri', 'Dairy', 'fa-cheese'),
('Yumurta', 'Eggs', 'fa-egg'),
('Balık', 'Fish', 'fa-fish'),
('Kabuklu Deniz Ürünleri', 'Shellfish', 'fa-shrimp'),
('Fındık', 'Tree Nuts', 'fa-seedling'),
('Yer Fıstığı', 'Peanuts', 'fa-peanut'),
('Soya', 'Soy', 'fa-leaf'),
('Susam', 'Sesame', 'fa-seedling'),
('Hardal', 'Mustard', 'fa-pepper-hot'),
('Kereviz', 'Celery', 'fa-carrot'),
('Kükürt Dioksit', 'Sulphites', 'fa-flask');
