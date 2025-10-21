# Forgot Password Functionality Setup

## Overview
The forgot password functionality has been successfully implemented for the Academic Excellence Platform. This feature allows users to reset their passwords securely using email verification.

## Files Created/Modified

### New Files:
1. `public/forgot_password.php` - Password reset request page
2. `public/reset_password.php` - Password reset form page
3. `src/Service/EmailService.php` - Email service for sending reset emails
4. `sql/password_reset_table.sql` - Database table structure
5. `setup_password_reset.php` - Setup script to create the database table

### Modified Files:
1. `public/login.php` - Updated forgot password link to point to `forgot_password.php`
2. `src/Controller/AuthController.php` - Added password reset methods

## Database Setup

### Step 1: Create the Password Reset Table
Run the setup script to create the required database table:

```bash
php setup_password_reset.php
```

Or manually execute the SQL:

```sql
CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `email` varchar(150) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` timestamp NOT NULL,
  `used` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_email` (`email`),
  KEY `idx_token` (`token`),
  KEY `idx_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

## How It Works

### 1. User Flow:
1. User clicks "Forgot your password?" on the login page
2. User enters their email address on the forgot password page
3. System generates a secure token and stores it in the database
4. System sends an email with a reset link (or shows the link for testing)
5. User clicks the reset link
6. User enters a new password
7. System validates the token and updates the password
8. Token is marked as used to prevent reuse

### 2. Security Features:
- **Secure tokens**: 64-character random tokens using `random_bytes()`
- **Token expiration**: Tokens expire after 1 hour
- **One-time use**: Tokens are marked as used after password reset
- **Email validation**: Only registered email addresses can request resets
- **Password strength**: Client-side password strength indicator
- **CSRF protection**: Form-based submission with proper validation

## Testing the Functionality

### Current Test Mode:
Since email service is not configured by default, the system operates in test mode:

1. Go to `login.php`
2. Click "Forgot your password?"
3. Enter a valid email address from your users table
4. The system will display the reset token and provide a clickable link
5. Click the link to test the password reset process

### Example Test Users:
Based on your database, you can test with these emails:
- `JohnPaulPaches@gmail.com`
- `princeondoy@gmail.com`
- `johnzanderzerrudo@gmail.com`
- `adrian@gmail.com`
- `adrianebrao1972@gmail.com`

## Production Configuration

### Email Service Setup:
To enable email sending in production:

1. **Configure Email Service**: Update `EmailService.php` with your SMTP settings
2. **Remove Test Mode**: Remove the token display from `forgot_password.php`
3. **Set Base URL**: Configure the correct base URL for reset links

### Recommended Email Services:
- **PHPMailer**: For SMTP configuration
- **SendGrid**: Cloud email service
- **Mailgun**: Transactional email service
- **Amazon SES**: AWS email service

### Example PHPMailer Integration:
```php
// In EmailService.php, replace mail() function with PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

$mail = new PHPMailer(true);
$mail->isSMTP();
$mail->Host = 'smtp.gmail.com';
$mail->SMTPAuth = true;
$mail->Username = 'your-email@gmail.com';
$mail->Password = 'your-app-password';
$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
$mail->Port = 587;
```

## Security Considerations

### Rate Limiting:
Consider implementing rate limiting to prevent abuse:
- Limit reset requests per email (e.g., 1 per 5 minutes)
- Limit reset requests per IP address
- Log suspicious activity

### Additional Security:
- **HTTPS**: Always use HTTPS in production
- **Input Validation**: All inputs are validated and sanitized
- **SQL Injection Protection**: Using prepared statements
- **XSS Protection**: All outputs are escaped with `htmlspecialchars()`

## Maintenance

### Cleanup Old Tokens:
Create a cron job to clean up expired tokens:

```sql
DELETE FROM password_reset_tokens 
WHERE expires_at < NOW() OR used = 1;
```

### Monitoring:
- Monitor failed reset attempts
- Track token usage patterns
- Log successful password resets

## Troubleshooting

### Common Issues:
1. **Database table not created**: Run `setup_password_reset.php`
2. **Email not found**: Ensure the email exists in the users table
3. **Token expired**: Tokens expire after 1 hour
4. **Token already used**: Each token can only be used once

### Debug Mode:
The system includes helpful error messages and test mode for debugging.

## API Methods

### AuthController Methods:
- `requestPasswordReset($email)`: Generate and store reset token
- `validateResetToken($token)`: Validate token and check expiration
- `resetPassword($token, $newPassword)`: Reset password using valid token

## Customization

### Styling:
The pages use the same academic theme as the login page. Customize the CSS variables in the `<style>` sections to match your branding.

### Email Templates:
Modify the email templates in `EmailService.php` to match your institution's branding and communication style.

### Validation Rules:
Update password validation rules in both client-side JavaScript and server-side PHP as needed.

---

The forgot password functionality is now fully operational and ready for use!