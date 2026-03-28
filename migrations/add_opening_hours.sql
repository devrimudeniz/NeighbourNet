-- Add opening_hours column to business_listings table
-- Run this migration on your database

ALTER TABLE business_listings 
ADD COLUMN opening_hours JSON DEFAULT NULL COMMENT 'JSON object with day:hours pairs';

-- Example JSON format:
-- {
--   "monday": {"open": "09:00", "close": "22:00", "closed": false},
--   "tuesday": {"open": "09:00", "close": "22:00", "closed": false},
--   "wednesday": {"open": "09:00", "close": "22:00", "closed": false},
--   "thursday": {"open": "09:00", "close": "22:00", "closed": false},
--   "friday": {"open": "09:00", "close": "23:00", "closed": false},
--   "saturday": {"open": "10:00", "close": "23:00", "closed": false},
--   "sunday": {"open": "10:00", "close": "21:00", "closed": false}
-- }
