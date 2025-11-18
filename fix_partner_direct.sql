-- Fix partner login for user_id 4
-- Run this SQL directly against your production MySQL database

-- First, check if partner record exists
SELECT 'Checking for existing partner record...' as status;
SELECT * FROM partners WHERE user_id = 4;

-- Create partner record if it doesn't exist
INSERT INTO partners (user_id, name, email, company_name, is_active, kyc_status, partner_tier, created_at, updated_at)
SELECT 4, u.name, u.email, 'Demo Partner LLC', 1, 'approved', 'free', NOW(), NOW()
FROM users u
WHERE u.id = 4
AND NOT EXISTS (SELECT 1 FROM partners WHERE user_id = 4);

-- Check partner was created
SELECT 'Partner record after insert:' as status;
SELECT id, user_id, name, email, is_active, kyc_status FROM partners WHERE user_id = 4;

-- Create partner-company link to company 2
INSERT INTO partner_company_links (partner_id, company_id, is_primary, is_active, created_at, updated_at)
SELECT p.id, 2, 1, 1, NOW(), NOW()
FROM partners p
WHERE p.user_id = 4
AND NOT EXISTS (
    SELECT 1 FROM partner_company_links
    WHERE partner_id = p.id AND company_id = 2
);

-- Verify the link
SELECT 'Partner company links:' as status;
SELECT pcl.*, c.name as company_name
FROM partner_company_links pcl
JOIN partners p ON p.id = pcl.partner_id
JOIN companies c ON c.id = pcl.company_id
WHERE p.user_id = 4;

SELECT 'Fix complete!' as status;
