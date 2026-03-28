-- Review System Upgrade Migration
-- Add photo and business reply support to reviews

-- Add image column for photo reviews
ALTER TABLE business_reviews 
ADD COLUMN IF NOT EXISTS image VARCHAR(500) NULL COMMENT 'Review photo URL',
ADD COLUMN IF NOT EXISTS business_reply TEXT NULL COMMENT 'Business owner reply',
ADD COLUMN IF NOT EXISTS reply_at DATETIME NULL COMMENT 'Reply timestamp';

-- Add index for faster queries
ALTER TABLE business_reviews
ADD INDEX IF NOT EXISTS idx_business_id (business_id),
ADD INDEX IF NOT EXISTS idx_user_id (user_id);
