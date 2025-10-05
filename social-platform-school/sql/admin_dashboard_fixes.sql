-- Admin Dashboard Database Schema Fixes
-- Run this script to fix the admin dashboard functionality

-- Fix user_warnings table structure
ALTER TABLE `user_warnings` 
ADD COLUMN `user_id` int(11) NOT NULL AFTER `id`,
ADD COLUMN `warned_by_user_id` int(11) DEFAULT NULL AFTER `user_id`;

-- Copy data from old columns to new columns if data exists
UPDATE `user_warnings` SET `user_id` = `warned_user_id` WHERE `user_id` = 0 OR `user_id` IS NULL;

-- Add indexes for the new columns
ALTER TABLE `user_warnings` 
ADD KEY `idx_user_id` (`user_id`),
ADD KEY `idx_warned_by_user_id` (`warned_by_user_id`);

-- Fix user_suspensions table structure
ALTER TABLE `user_suspensions` 
ADD COLUMN `suspended_by_user_id` int(11) DEFAULT NULL AFTER `user_id`,
ADD COLUMN `suspension_type` enum('temporary','permanent') DEFAULT 'temporary' AFTER `reason`;

-- Add indexes for the new columns
ALTER TABLE `user_suspensions` 
ADD KEY `idx_suspended_by_user_id` (`suspended_by_user_id`);

-- Fix user_reports table structure
ALTER TABLE `user_reports` 
ADD COLUMN `content_id` int(11) DEFAULT NULL AFTER `reason`,
ADD COLUMN `admin_notes` text DEFAULT NULL AFTER `status`;

-- Add index for content_id
ALTER TABLE `user_reports` 
ADD KEY `idx_content_id` (`content_id`);

-- Create warning_actions table with correct structure
DROP TABLE IF EXISTS `warning_actions`;
CREATE TABLE `warning_actions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `action_type` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_action_type` (`action_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add 'wow' reaction type to reactions table
ALTER TABLE `reactions` 
MODIFY COLUMN `type` enum('like','haha','heart','sad','angry','wow') NOT NULL;

-- Add foreign key constraints for new columns (optional, but recommended)
-- Note: Only add these if you want strict referential integrity

-- ALTER TABLE `user_warnings` 
-- ADD CONSTRAINT `fk_user_warnings_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
-- ADD CONSTRAINT `fk_user_warnings_warned_by` FOREIGN KEY (`warned_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

-- ALTER TABLE `user_suspensions` 
-- ADD CONSTRAINT `fk_user_suspensions_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
-- ADD CONSTRAINT `fk_user_suspensions_suspended_by` FOREIGN KEY (`suspended_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

-- ALTER TABLE `warning_actions` 
-- ADD CONSTRAINT `fk_warning_actions_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;