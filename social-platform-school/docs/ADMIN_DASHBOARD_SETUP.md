# Admin Dashboard Setup Guide

## Overview
The admin dashboard provides comprehensive management tools for warnings, suspensions, reports, posts, and events in the social platform.

## Database Setup

### Step 1: Run the Database Migration
Execute the SQL script to fix database schema issues:

```sql
-- Run this in your MySQL/MariaDB database
SOURCE sql/admin_dashboard_fixes.sql;
```

Or manually execute the commands in `social-platform-school/sql/admin_dashboard_fixes.sql`

### Step 2: Verify Tables
Ensure these tables exist with the correct structure:

- `user_warnings` - with `user_id` and `warned_by_user_id` columns
- `user_suspensions` - with `suspended_by_user_id` and `suspension_type` columns  
- `user_reports` - with `content_id` and `admin_notes` columns
- `warning_actions` - for logging admin actions
- `reactions` - updated to include 'wow' reaction type

## Features

### 1. User Management
- View all users with their roles and join dates
- Issue warnings (low, medium, high, severe levels)
- Suspend users (temporary or permanent)
- Unsuspend users
- Track user status and moderation history

### 2. Warning System
- Issue warnings with different severity levels
- View all active warnings
- Dismiss warnings when resolved
- Automatic logging of warning actions

### 3. Suspension System
- Temporary suspensions with end dates
- Permanent suspensions
- Automatic cleanup of expired suspensions
- Track who suspended each user and when

### 4. Report Management
- View all user reports
- Update report status (pending, reviewed, resolved, dismissed)
- Add admin notes to reports
- Filter reports by status

### 5. Content Management
- View and delete posts
- View and delete comments
- Manage post attachments
- Content moderation tools

### 6. Event Management
- Create new events
- View all events
- Delete events
- Event scheduling with date/time

## Usage Instructions

### Accessing the Dashboard
1. Log in as an admin user
2. Navigate to `/admin.php`
3. Use the tabs to switch between different management sections

### Issuing Warnings
1. Go to the "Users" tab
2. Click "Warn" next to a user
3. Select warning level and provide reason
4. Submit the warning

### Suspending Users
1. Go to the "Users" tab  
2. Click "Suspend" next to a user
3. Choose temporary or permanent suspension
4. Set end date for temporary suspensions
5. Provide suspension reason

### Managing Reports
1. Go to the "Reports" tab
2. Click "Review" on any report
3. Update status and add admin notes
4. Submit changes

### Creating Events
1. Go to the "Events" tab
2. Fill out the "Create New Event" form
3. Set title, description, and date/time
4. Submit to create the event

## Error Handling

The system includes comprehensive error handling:

- Database schema compatibility checks
- Graceful fallbacks for missing columns
- User-friendly success/error messages
- Detailed error logging for debugging

## Security Features

- Admin role verification
- CSRF protection through POST requests
- Input validation and sanitization
- Session-based authentication
- SQL injection prevention

## Troubleshooting

### Common Issues

1. **"Failed to issue warning"**
   - Check if `user_warnings` table has correct columns
   - Run the database migration script
   - Verify admin permissions

2. **"Failed to suspend user"**
   - Ensure `user_suspensions` table has required columns
   - Check if suspension type is valid
   - Verify date format for temporary suspensions

3. **"Failed to update report status"**
   - Check if `user_reports` table exists
   - Verify report ID is valid
   - Ensure status value is allowed

### Database Schema Verification

Run these queries to check your schema:

```sql
-- Check user_warnings columns
SHOW COLUMNS FROM user_warnings;

-- Check user_suspensions columns  
SHOW COLUMNS FROM user_suspensions;

-- Check user_reports columns
SHOW COLUMNS FROM user_reports;

-- Check if warning_actions table exists
SHOW TABLES LIKE 'warning_actions';
```

## Performance Considerations

- Tables are indexed for optimal query performance
- Pagination can be added for large datasets
- Regular cleanup of expired suspensions recommended
- Archive old warnings and reports periodically

## Future Enhancements

Potential improvements:
- Bulk user actions
- Advanced filtering and search
- Email notifications for warnings/suspensions
- Detailed audit logs
- User appeal system
- Automated moderation rules

## Support

If you encounter issues:
1. Check the error logs in your web server
2. Verify database schema matches requirements
3. Ensure all required PHP extensions are installed
4. Check file permissions for uploads directory

For additional help, refer to the main project documentation or contact the development team.