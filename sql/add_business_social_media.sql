-- Add social media and additional info columns to business_listings table

ALTER TABLE `business_listings` 
ADD COLUMN `instagram_url` varchar(255) DEFAULT NULL,
ADD COLUMN `facebook_url` varchar(255) DEFAULT NULL,
ADD COLUMN `tripadvisor_url` varchar(255) DEFAULT NULL,
ADD COLUMN `google_maps_url` TEXT DEFAULT NULL,
ADD COLUMN `latitude` DECIMAL(10, 8) DEFAULT NULL,
ADD COLUMN `longitude` DECIMAL(11, 8) DEFAULT NULL,
ADD COLUMN `menu_theme` ENUM('default', 'elegant', 'modern', 'minimal') DEFAULT 'default',
ADD COLUMN `menu_primary_color` varchar(7) DEFAULT '#0055FF',
ADD COLUMN `menu_logo` varchar(255) DEFAULT NULL;

-- Add indexes for better performance
ALTER TABLE `business_listings` ADD INDEX `idx_coordinates` (`latitude`, `longitude`);
