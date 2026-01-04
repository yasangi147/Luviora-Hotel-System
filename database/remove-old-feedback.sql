-- ============================================
-- CLEANUP SCRIPT: Remove Old Feedback Tables
-- ============================================

-- Drop old feedback table
DROP TABLE IF EXISTS feedback;

-- Drop old contact_messages table
DROP TABLE IF EXISTS contact_messages;

-- ============================================
-- NOTE: After running this script, execute:
-- mysql -u root -p luviora_hotel_system < database/feedback_queries_new.sql
-- ============================================

