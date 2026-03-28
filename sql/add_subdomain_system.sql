-- Add subdomain column to business_listings
ALTER TABLE `business_listings` 
ADD COLUMN `subdomain` varchar(100) DEFAULT NULL UNIQUE,
ADD COLUMN `subdomain_status` enum('pending','approved','rejected') DEFAULT 'pending',
ADD COLUMN `subdomain_requested_at` timestamp NULL DEFAULT NULL,
ADD COLUMN `subdomain_approved_at` timestamp NULL DEFAULT NULL,
ADD COLUMN `subdomain_approved_by` int(11) DEFAULT NULL;

-- Add index for subdomain lookups
ALTER TABLE `business_listings` ADD INDEX `idx_subdomain` (`subdomain`);

-- Add foreign key for approved_by
ALTER TABLE `business_listings` 
ADD CONSTRAINT `fk_subdomain_approver` 
FOREIGN KEY (`subdomain_approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;
