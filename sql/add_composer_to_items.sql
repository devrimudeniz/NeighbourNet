-- Add composer column to menu items

ALTER TABLE `business_menu_items` 
ADD COLUMN `composer` varchar(255) DEFAULT NULL AFTER `description_en`;
