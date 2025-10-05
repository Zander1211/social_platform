-- Update user_suspensions table to add missing columns for better functionality

-- Add suspended_by_user_id column if it doesn't exist
ALTER TABLE `user_suspensions` 
ADD COLUMN `suspended_by_user_id` INT(11) NULL AFTER `user_id`,
ADD COLUMN `suspension_type` ENUM('temporary', 'permanent') NOT NULL DEFAULT 'temporary' AFTER `reason`,
ADD COLUMN `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`;

-- Add foreign key constraint for suspended_by_user_id
ALTER TABLE `user_suspensions` 
ADD CONSTRAINT `fk_suspended_by_user` FOREIGN KEY (`suspended_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

-- Add index for better performance
ALTER TABLE `user_suspensions` 
ADD INDEX `idx_suspension_type` (`suspension_type`),
ADD INDEX `idx_suspended_until` (`suspended_until`),
ADD INDEX `idx_is_active` (`is_active`);

-- Update warning_actions table to support user_id instead of warning_id
ALTER TABLE `warning_actions` 
ADD COLUMN `user_id` INT(11) NULL AFTER `warning_id`,
ADD COLUMN `description` TEXT NULL AFTER `notes`;

-- Add foreign key for user_id in warning_actions
ALTER TABLE `warning_actions` 
ADD CONSTRAINT `fk_warning_action_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;