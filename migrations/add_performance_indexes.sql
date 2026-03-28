-- Kalkan Social - Performance Indexes
-- Run via: php migrations/run_performance_indexes.php

-- Posts: feed queries, soft delete filter
-- CREATE INDEX idx_posts_deleted_created ON posts(deleted_at, created_at);
-- CREATE INDEX idx_posts_user_created ON posts(user_id, created_at);
-- CREATE INDEX idx_posts_created_at ON posts(created_at);

-- Post likes: reaction lookups
-- CREATE INDEX idx_post_likes_post_user ON post_likes(post_id, user_id);
-- CREATE INDEX idx_post_likes_post ON post_likes(post_id);

-- Post comments: count subqueries
-- CREATE INDEX idx_post_comments_post ON post_comments(post_id);

-- Notifications: unread badge
-- CREATE INDEX idx_notifications_user_read ON notifications(user_id, is_read);

-- Messages: unread count
-- CREATE INDEX idx_messages_receiver_read ON messages(receiver_id, is_read);

-- Events: index page, approved filter
-- CREATE INDEX idx_events_date_status ON events(event_date, status);
-- CREATE INDEX idx_events_user ON events(user_id, event_date);
