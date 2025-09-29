-- Schema compatibility adjustments for events, posts, and messages

-- Ensure events table has expected columns used by EventController
ALTER TABLE events 
  ADD COLUMN IF NOT EXISTS location VARCHAR(255) NULL,
  ADD COLUMN IF NOT EXISTS banner_path VARCHAR(255) NULL,
  ADD COLUMN IF NOT EXISTS created_by INT NULL;

-- Ensure posts has title/content and event link for richer feed
ALTER TABLE posts 
  ADD COLUMN IF NOT EXISTS title VARCHAR(255) NULL,
  ADD COLUMN IF NOT EXISTS content TEXT NULL,
  ADD COLUMN IF NOT EXISTS event_id INT NULL;

ALTER TABLE posts 
  ADD CONSTRAINT IF NOT EXISTS fk_posts_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE SET NULL;

-- Ensure messages supports room-based chat
ALTER TABLE messages 
  ADD COLUMN IF NOT EXISTS chat_id INT NULL;

CREATE INDEX IF NOT EXISTS idx_messages_chat ON messages(chat_id, created_at);

-- Add FK if chat_rooms table exists
-- Note: MySQL does not support conditional FKs; attempt to add and ignore if already exists
ALTER TABLE messages 
  ADD CONSTRAINT IF NOT EXISTS fk_messages_room FOREIGN KEY (chat_id) REFERENCES chat_rooms(id) ON DELETE CASCADE;