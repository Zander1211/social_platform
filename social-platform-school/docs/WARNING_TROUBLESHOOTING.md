# Warning System Troubleshooting Guide

## Problem: Student warnings are not appearing

### Quick Diagnosis

1. **Run the test script:**
   ```
   http://your-domain/social-platform-school/test_warnings.php
   ```

2. **Check the browser console for JavaScript errors**

3. **Verify database schema**

### Common Issues and Solutions

#### Issue 1: Database Schema Mismatch

**Symptoms:**
- Warnings created in admin panel but not showing for students
- Error logs showing "column not found" errors

**Solution:**
Run the database migration script:

```sql
-- Navigate to your database and run:
SOURCE social-platform-school/sql/admin_dashboard_fixes.sql;

-- Or manually execute the commands:
ALTER TABLE `user_warnings` 
ADD COLUMN `user_id` int(11) NOT NULL AFTER `id`,
ADD COLUMN `warned_by_user_id` int(11) DEFAULT NULL AFTER `user_id`;

UPDATE `user_warnings` SET `user_id` = `warned_user_id` WHERE `user_id` = 0 OR `user_id` IS NULL;
```

#### Issue 2: AdminController Not Found

**Symptoms:**
- Fatal error about AdminController class not found

**Solution:**
Ensure the AdminController is properly included:

```php
require_once '../src/Controller/AdminController.php';
```

#### Issue 3: Session Issues

**Symptoms:**
- Warnings not showing for the correct user
- getUserWarnings returning empty results

**Solution:**
Check session variables:

```php
// Add this to debug:
echo "Current user ID: " . ($_SESSION['user_id'] ?? 'Not set');
```

#### Issue 4: Database Connection Issues

**Symptoms:**
- No warnings showing despite being created
- Database errors in logs

**Solution:**
Verify database connection in `config/database.php`:

```php
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Database connected successfully";
} catch(PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
```

### Step-by-Step Verification

#### Step 1: Verify Database Schema

```sql
-- Check if user_warnings table exists
SHOW TABLES LIKE 'user_warnings';

-- Check table structure
DESCRIBE user_warnings;

-- Expected columns:
-- id, user_id, warned_user_id, warned_by_user_id, reason, warning_level, is_active, created_at
```

#### Step 2: Check Warning Creation

1. Login as admin
2. Go to `admin.php`
3. Issue a warning to a student
4. Check if warning appears in database:

```sql
SELECT * FROM user_warnings WHERE is_active = 1;
```

#### Step 3: Test getUserWarnings Method

```php
// Add this to test_warnings.php or create a debug script:
$adminController = new AdminController($pdo);
$warnings = $adminController->getUserWarnings($studentUserId);
var_dump($warnings);
```

#### Step 4: Verify Frontend Display

1. Login as the warned student
2. Go to `index.php`
3. Check if warning appears at the top
4. Inspect browser console for errors

### Manual Database Verification

```sql
-- Check if warnings exist for a specific user
SELECT w.*, u.name as user_name 
FROM user_warnings w 
LEFT JOIN users u ON w.user_id = u.id 
WHERE w.user_id = [STUDENT_USER_ID] AND w.is_active = 1;

-- If using old schema:
SELECT w.*, u.name as user_name 
FROM user_warnings w 
LEFT JOIN users u ON w.warned_user_id = u.id 
WHERE w.warned_user_id = [STUDENT_USER_ID] AND w.is_active = 1;
```

### Debug Mode

Add debug output to `index.php`:

```php
// Add after the warning check:
echo "<!-- DEBUG: User ID: " . $_SESSION['user_id'] . " -->";
echo "<!-- DEBUG: Warnings found: " . count($userWarnings) . " -->";
if (!empty($userWarnings)) {
    echo "<!-- DEBUG: First warning: " . print_r($userWarnings[0], true) . " -->";
}
```

### Error Log Locations

Check these log files for errors:

1. **PHP Error Log:** Usually in `/var/log/php/error.log` or check `php.ini` for `log_errors` setting
2. **Apache/Nginx Error Log:** `/var/log/apache2/error.log` or `/var/log/nginx/error.log`
3. **Application Logs:** Check if any custom logging is implemented

### Common Error Messages and Solutions

#### "Column 'user_id' doesn't exist"
- **Cause:** Database migration not run
- **Solution:** Run the migration script

#### "Call to undefined method AdminController::getUserWarnings"
- **Cause:** Old version of AdminController
- **Solution:** Update AdminController.php with latest version

#### "Trying to get property of non-object"
- **Cause:** Database query returning null
- **Solution:** Check database connection and table structure

#### "Headers already sent"
- **Cause:** Output before header() calls
- **Solution:** Check for echo/print statements before redirects

### Testing Checklist

- [ ] Database migration script executed
- [ ] AdminController.php updated
- [ ] Test script runs without errors
- [ ] Can create warnings in admin panel
- [ ] Warnings appear in database
- [ ] getUserWarnings returns correct data
- [ ] Warnings display on student index.php
- [ ] Warning styling appears correctly
- [ ] Mobile responsive design works

### Performance Considerations

If you have many warnings, consider:

1. **Pagination:** Limit warnings displayed
2. **Caching:** Cache warning data for frequent users
3. **Indexing:** Ensure database indexes on user_id and is_active columns

### Security Notes

- Warnings contain sensitive information
- Ensure proper access controls
- Sanitize all output with `htmlspecialchars()`
- Use prepared statements for all database queries

### Contact Support

If issues persist after following this guide:

1. Run the test script and save output
2. Check error logs for specific error messages
3. Verify database schema matches expected structure
4. Test with a fresh warning creation

Include this information when seeking help:
- PHP version
- Database type and version
- Error messages from logs
- Output from test script
- Steps already attempted