-- ============================================
-- Remove Old Maintenance System Tables
-- Luviora Hotel Management System
-- ============================================
-- This script removes the old maintenance tables
-- (maintenance_schedule and maintenance_inventory)
-- and keeps only maintenance_issues table
-- ============================================

-- Drop old maintenance tables (in order of dependencies)
DROP TABLE IF EXISTS maintenance_schedule;
DROP TABLE IF EXISTS maintenance_inventory;

-- ============================================
-- Verification Query
-- ============================================
-- Run this query to verify tables were removed:
-- SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES 
-- WHERE TABLE_SCHEMA = 'luviora_hotel_system' 
-- AND TABLE_NAME LIKE 'maintenance%';
-- 
-- Expected Result: Only maintenance_issues should remain
-- ============================================

-- ============================================
-- How to Execute
-- ============================================
-- Using MySQL Command Line:
-- mysql -u root -p luviora_hotel_system < database/remove-old-maintenance.sql
--
-- Using phpMyAdmin:
-- 1. Open http://localhost/phpmyadmin
-- 2. Select database: luviora_hotel_system
-- 3. Go to SQL tab
-- 4. Copy and paste these commands
-- 5. Click "Go"
-- ============================================

