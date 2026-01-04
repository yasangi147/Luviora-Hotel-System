-- ============================================
-- Remove Housekeeping System Tables
-- Luviora Hotel Management System
-- ============================================
-- This script removes all housekeeping-related tables and data
-- Run this if you want to completely remove the housekeeping system

-- Step 1: Drop housekeeping tables (in order of dependencies)
-- Drop checklist first (depends on tasks)
DROP TABLE IF EXISTS housekeeping_checklist;

-- Drop staff availability
DROP TABLE IF EXISTS housekeeping_staff_availability;

-- Drop schedule
DROP TABLE IF EXISTS housekeeping_schedule;

-- Drop tasks (main table)
DROP TABLE IF EXISTS housekeeping_tasks;

-- ============================================
-- Verification Query
-- ============================================
-- Run this to verify all housekeeping tables have been removed:
-- SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES 
-- WHERE TABLE_SCHEMA = 'luviora_hotel_system' 
-- AND TABLE_NAME LIKE 'housekeeping%';
-- (Should return no results)

-- ============================================
-- Notes:
-- ============================================
-- 1. This script only removes the housekeeping tables
-- 2. The housekeeping.php admin page will no longer work after this
-- 3. To restore, re-import database/housekeeping_system.sql
-- 4. No data from other tables will be affected
-- 5. Make sure to backup your database before running this script

