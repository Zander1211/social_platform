-- Create events and RSVP support, plus chat rooms/members, and link posts to events

-- events core fields with location and banner
CREATE TABLE IF NOT EXISTS events (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  description TEXT,
  event_date DATETIME NOT NULL,
  location VARCHAR(255) NULL,
  banner_path VARCHAR(255) NULL,
  created_by INT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- RSVPs for events
CREATE TABLE IF NOT EXISTS event_rsvps (
  id INT AUTO_INCREMENT PRIMARY KEY,
  event_id INT NOT NULL,
  user_id INT NOT NULL,
  status ENUM('going','interested','not_going') NOT NULL DEFAULT 'interested',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_rsvp (event_id, user_id),
  FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- add event_id link to posts if not exists
ALTER TABLE posts ADD COLUMN IF NOT EXISTS event_id INT NULL;
ALTER TABLE posts ADD CONSTRAINT IF NOT EXISTS fk_posts_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE SET NULL;

-- chat structures (rooms + membership) if not present
CREATE TABLE IF NOT EXISTS chat_rooms (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255),
  is_group TINYINT(1) NOT NULL DEFAULT 0,
  created_by INT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS chat_members (
  id INT AUTO_INCREMENT PRIMARY KEY,
  chat_id INT NOT NULL,
  user_id INT NOT NULL,
  joined_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_member (chat_id, user_id),
  FOREIGN KEY (chat_id) REFERENCES chat_rooms(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- messages table extension: ensure it references chat_rooms if using room-based chat
-- If a legacy messages table exists with sender/receiver columns, consider migration to chat_rooms/messages schema.
