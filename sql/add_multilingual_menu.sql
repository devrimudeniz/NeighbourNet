-- Add multilingual support for menu items and categories

ALTER TABLE `business_menu_categories` 
ADD COLUMN `name_en` varchar(255) DEFAULT NULL AFTER `name`,
ADD COLUMN `description_en` TEXT DEFAULT NULL AFTER `description`;

ALTER TABLE `business_menu_items` 
ADD COLUMN `name_en` varchar(255) DEFAULT NULL AFTER `name`,
ADD COLUMN `description_en` TEXT DEFAULT NULL AFTER `description`;

-- Update existing data to have English names (copy from Turkish)
UPDATE `business_menu_categories` SET `name_en` = `name` WHERE `name_en` IS NULL;
UPDATE `business_menu_items` SET `name_en` = `name` WHERE `name_en` IS NULL;
