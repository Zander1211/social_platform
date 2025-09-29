-- Add online status tracking to users table
ALTER TABLE users 
ADD COLUMN is_online TINYINT(1) DEFAULT 0,
ADD COLUMN last_seen TIMESTAMP NULL DEFAULT NULL;

-- Create index for better performance
CREATE INDEX idx_users_online ON users(is_online);
CREATE INDEX idx_users_last_seen ON users(last_seen);