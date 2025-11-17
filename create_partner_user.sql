-- Create Partner User in Railway Database
-- Run this SQL via Railway dashboard or mysql client

-- Step 1: Create the partner user
INSERT INTO users (name, email, password, role, created_at, updated_at)
VALUES (
    'Partner Demo',
    'partner@demo.mk',
    '$2y$12$UCWueKd0MY3TWajWDpn4iegCgoqC4bYq8sRtA3o3ZXfYYjEwlLMte', -- Password: Partner2025!
    'partner',
    NOW(),
    NOW()
);

-- Step 2: Get the user ID (will be needed for next step)
SET @user_id = LAST_INSERT_ID();
SELECT @user_id as 'New User ID';

-- Step 3: Get first company ID
SELECT @company_id := id FROM companies ORDER BY id ASC LIMIT 1;
SELECT @company_id as 'Company ID';

-- Step 4: Attach user to company
INSERT INTO company_user (user_id, company_id, is_owner, created_at, updated_at)
VALUES (@user_id, @company_id, 0, NOW(), NOW());

-- Step 5: Verify the user was created
SELECT u.id, u.name, u.email, u.role, c.name as company_name
FROM users u
LEFT JOIN company_user cu ON u.id = cu.user_id
LEFT JOIN companies c ON cu.company_id = c.id
WHERE u.email = 'partner@demo.mk';
