-- Add visibility column to group_posts
-- 'everyone' = shows in feed + group, 'group_only' = shows only in group
ALTER TABLE group_posts 
ADD COLUMN visibility VARCHAR(20) DEFAULT 'everyone' 
COMMENT 'everyone or group_only';
