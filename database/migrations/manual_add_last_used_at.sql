-- Manual migration to add last_used_at column to certificates table
-- Run this manually on Railway if migration doesn't execute

ALTER TABLE `certificates`
ADD COLUMN `last_used_at` TIMESTAMP NULL AFTER `updated_at`,
ADD INDEX `certificates_last_used_at_index` (`last_used_at`);

-- CLAUDE-CHECKPOINT
