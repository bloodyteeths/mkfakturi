# Password Double-Hash Bug - Visual Explanation

## The Bug in Action

```
┌─────────────────────────────────────────────────────────────────┐
│ BROKEN FLOW (Current Implementation - Login Fails)             │
└─────────────────────────────────────────────────────────────────┘

Step 1: admin:reset command (Line 30 or 72)
┌─────────────────────────────────────────────────────────┐
│  $admin->password = Hash::make('your-secure-password')  │
│                                                         │
│  Input:  "your-secure-password"  (plain text)           │
│  Output: "$2y$12$AbC123...XyZ"  (60 char bcrypt hash)   │
└─────────────────────────────────────────────────────────┘
                           ↓
Step 2: User model setPasswordAttribute() mutator (Line 96)
┌─────────────────────────────────────────────────────────┐
│  $this->attributes['password'] = bcrypt($value)         │
│                                                         │
│  Input:  "$2y$12$AbC123...XyZ"  (already hashed!)       │
│  Output: "$2y$12$DeF456...UvW"  (hash of the hash!)     │
└─────────────────────────────────────────────────────────┘
                           ↓
Step 3: Saved to database
┌─────────────────────────────────────────────────────────┐
│  Database 'users' table:                                │
│                                                         │
│  | email              | password               |       │
│  | admin@example.com  | $2y$12$DeF456...UvW  |       │
│                        └─ DOUBLE HASHED! ─────┘       │
└─────────────────────────────────────────────────────────┘
                           ↓
Step 4: User tries to log in
┌─────────────────────────────────────────────────────────┐
│  POST /login with:                                      │
│  - email: "admin@example.com"                           │
│  - password: "your-secure-password"                     │
└─────────────────────────────────────────────────────────┘
                           ↓
Step 5: Auth::attempt() hashes input and compares
┌─────────────────────────────────────────────────────────┐
│  $inputHash = Hash::make('your-secure-password')        │
│  $inputHash = "$2y$12$GhI789...StU"  (single hash)      │
│                                                         │
│  $dbHash = "$2y$12$DeF456...UvW"  (double hash from DB) │
│                                                         │
│  Compare: $inputHash === $dbHash ?                      │
│  Result: FALSE ❌                                        │
└─────────────────────────────────────────────────────────┘
                           ↓
                    LOGIN FAILS ❌
      "These credentials do not match our records"


┌─────────────────────────────────────────────────────────────────┐
│ CORRECT FLOW (After Fix - Login Works)                         │
└─────────────────────────────────────────────────────────────────┘

Step 1: admin:reset command (FIXED - Line 30 or 72)
┌─────────────────────────────────────────────────────────┐
│  $admin->password = 'your-secure-password'  ← NO Hash::make() │
│                                                         │
│  Input:  "your-secure-password"  (plain text)           │
│  Output: "your-secure-password"  (still plain text)     │
└─────────────────────────────────────────────────────────┘
                           ↓
Step 2: User model setPasswordAttribute() mutator (Line 96)
┌─────────────────────────────────────────────────────────┐
│  $this->attributes['password'] = bcrypt($value)         │
│                                                         │
│  Input:  "your-secure-password"  (plain text)           │
│  Output: "$2y$12$JkL012...WxY"  (single hash)           │
└─────────────────────────────────────────────────────────┘
                           ↓
Step 3: Saved to database
┌─────────────────────────────────────────────────────────┐
│  Database 'users' table:                                │
│                                                         │
│  | email              | password               |       │
│  | admin@example.com  | $2y$12$JkL012...WxY  |       │
│                        └─ SINGLE HASH ✅ ──────┘       │
└─────────────────────────────────────────────────────────┘
                           ↓
Step 4: User tries to log in
┌─────────────────────────────────────────────────────────┐
│  POST /login with:                                      │
│  - email: "admin@example.com"                           │
│  - password: "your-secure-password"                     │
└─────────────────────────────────────────────────────────┘
                           ↓
Step 5: Auth::attempt() hashes input and compares
┌─────────────────────────────────────────────────────────┐
│  $inputHash = Hash::make('your-secure-password')        │
│  $inputHash = "$2y$12$MnO345...ZaB"  (single hash)      │
│                                                         │
│  $dbHash = "$2y$12$JkL012...WxY"  (single hash from DB) │
│                                                         │
│  Compare: Hash::check(plain, $dbHash) ?                 │
│  Result: TRUE ✅                                         │
└─────────────────────────────────────────────────────────┘
                           ↓
                    LOGIN SUCCEEDS ✅
                Redirects to dashboard


┌─────────────────────────────────────────────────────────────────┐
│ Why Hash::check() Works Even with Different Hash Strings       │
└─────────────────────────────────────────────────────────────────┘

Bcrypt includes a SALT in each hash, so the same password
generates different hash strings each time:

  Hash 1: $2y$12$JkL012...WxY
  Hash 2: $2y$12$MnO345...ZaB
  Hash 3: $2y$12$PqR678...CdE

All three hashes are DIFFERENT strings, but Hash::check()
can verify the original password against ANY of them because
bcrypt extracts the salt from the hash and re-hashes the
input with that same salt, then compares the results.

This is why:
  Hash::check('password', $hash1)  ✅ Returns TRUE
  Hash::check('password', $hash2)  ✅ Returns TRUE
  Hash::check('password', $hash3)  ✅ Returns TRUE

But when double-hashed:
  $doubleHash = bcrypt($hash1)  // Hash the hash
  Hash::check('password', $doubleHash)  ❌ Returns FALSE
  Hash::check($hash1, $doubleHash)      ✅ Returns TRUE (!)

The double hash can only verify the FIRST HASH, not the
original password!


┌─────────────────────────────────────────────────────────────────┐
│ Code Comparison                                                 │
└─────────────────────────────────────────────────────────────────┘

❌ BROKEN CODE (ResetAdminCommand.php)
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Line 28-32:
    if ($admin) {
        $this->info("Found existing user. Resetting password...");
        $admin->password = Hash::make($password);  ← Remove Hash::make()
        $admin->save();
    }

Lines 69-74:
    $admin = User::create([
        'name' => 'Администратор',
        'email' => $email,
        'password' => Hash::make($password),  ← Remove Hash::make()
        'role' => 'super admin',
    ]);

✅ FIXED CODE (ResetAdminCommand.php)
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Line 28-32:
    if ($admin) {
        $this->info("Found existing user. Resetting password...");
        $admin->password = $password;  ← Just assign plain password
        $admin->save();
    }

Lines 69-74:
    $admin = User::create([
        'name' => 'Администратор',
        'email' => $email,
        'password' => $password,  ← Just assign plain password
        'role' => 'super admin',
    ]);


┌─────────────────────────────────────────────────────────────────┐
│ The User Model Mutator (DO NOT CHANGE THIS)                    │
└─────────────────────────────────────────────────────────────────┘

File: app/Models/User.php (Lines 93-98)

    public function setPasswordAttribute($value)
    {
        if ($value != null) {
            $this->attributes['password'] = bcrypt($value);
        }
    }

This mutator is CORRECT and should NOT be modified.
It automatically hashes any password assigned to the model.

This is a Laravel best practice called "Mutators & Casting"
and ensures passwords are always hashed when set.

The bug is in the COMMAND that calls Hash::make() BEFORE
assigning to the model, causing the mutator to hash an
already-hashed value.


┌─────────────────────────────────────────────────────────────────┐
│ Quick Verification Test                                         │
└─────────────────────────────────────────────────────────────────┘

After applying the fix, run this test:

    php artisan tinker

Then execute:

    $user = \App\Models\User::create([
        'name' => 'Test User',
        'email' => 'test@test.com',
        'password' => 'testpass',  // Plain password
        'role' => 'admin'
    ]);

    // Try to authenticate
    \Auth::attempt(['email' => 'test@test.com', 'password' => 'testpass'])

Expected result: true ✅

If you see 'true', the fix is working correctly!


┌─────────────────────────────────────────────────────────────────┐
│ Summary                                                         │
└─────────────────────────────────────────────────────────────────┘

Problem:  Double password hashing in admin:reset command
Location: app/Console/Commands/ResetAdminCommand.php (Lines 30, 72)
Cause:    Using Hash::make() before assigning to model with mutator
Effect:   Authentication always fails - password hash doesn't match
Fix:      Remove Hash::make() calls - let model mutator handle hashing
Impact:   All users created via admin:reset cannot log in
Testing:  Login test after fix deployment

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
