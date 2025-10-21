# Email Configuration Guide

## 📧 **Complete Email Setup for Password Reset with OTP**

This guide will help you configure email sending for the password reset functionality with OTP support.

## 🚀 **Quick Setup (Gmail - Recommended)**

### **Step 1: Enable 2-Factor Authentication**
1. Go to your [Google Account settings](https://myaccount.google.com/)
2. Click on **Security** in the left sidebar
3. Under "Signing in to Google", click **2-Step Verification**
4. Follow the setup process to enable 2FA

### **Step 2: Generate App Password**
1. In Google Account settings, go to **Security**
2. Under "Signing in to Google", click **App passwords**
3. Select **Mail** from the dropdown
4. Click **Generate**
5. Copy the 16-character password (e.g., `abcd efgh ijkl mnop`)

### **Step 3: Configure Email Settings**
Edit `social-platform-school/config/email.php`:

```php
$emailConfig = [
    'service' => 'gmail',
    
    'gmail' => [
        'username' => 'your-email@gmail.com',        // Your Gmail address
        'password' => 'abcd efgh ijkl mnop',         // App password from Step 2
    ],
    
    'from' => [
        'email' => 'your-email@gmail.com',           // Same as username
        'name' => 'Academic Excellence Platform'
    ],
];
```

### **Step 4: Test the Configuration**
1. Go to your login page
2. Click "Forgot your password?"
3. Enter a valid email address
4. Check your email for the OTP and reset link!

## 🔧 **Alternative Configurations**

### **Custom SMTP Server**
```php
$emailConfig = [
    'service' => 'smtp',
    
    'smtp' => [
        'host' => 'mail.yourdomain.com',
        'port' => 587,
        'encryption' => 'tls',
        'username' => 'noreply@yourdomain.com',
        'password' => 'your-password',
    ],
];
```

### **Other Email Providers**

#### **Outlook/Hotmail**
```php
'smtp' => [
    'host' => 'smtp-mail.outlook.com',
    'port' => 587,
    'encryption' => 'tls',
    'username' => 'your-email@outlook.com',
    'password' => 'your-password',
],
```

#### **Yahoo Mail**
```php
'smtp' => [
    'host' => 'smtp.mail.yahoo.com',
    'port' => 587,
    'encryption' => 'tls',
    'username' => 'your-email@yahoo.com',
    'password' => 'your-app-password', // Generate from Yahoo security settings
],
```

## 📋 **Email Features**

### **What's Included:**
- ✅ **Beautiful HTML emails** with professional design
- ✅ **OTP codes** for additional security
- ✅ **Direct reset links** for convenience
- ✅ **Mobile-responsive** email templates
- ✅ **Security warnings** and best practices
- ✅ **Multiple email providers** support
- ✅ **Fallback to PHP mail()** if SMTP fails

### **Email Content:**
1. **Professional header** with platform branding
2. **6-digit OTP code** in highlighted box
3. **Direct reset link** button
4. **Security warnings** and expiration info
5. **Professional footer** with contact info

## 🛠️ **Troubleshooting**

### **Common Issues:**

#### **Gmail "Less secure app access" Error**
- **Solution**: Use App Passwords (Step 2 above)
- **Don't use**: Your regular Gmail password

#### **SMTP Connection Failed**
- Check your internet connection
- Verify SMTP settings (host, port, encryption)
- Check firewall settings
- Try different ports (587, 465, 25)

#### **Authentication Failed**
- Double-check username and password
- For Gmail: Use App Password, not regular password
- For other providers: Check if 2FA requires app passwords

#### **Emails Going to Spam**
- Add your domain to SPF records
- Use a professional "from" email address
- Avoid spam trigger words in subject/content

### **Debug Mode**
Enable debugging in `config/email.php`:
```php
'settings' => [
    'debug' => true,
]
```

Check your error logs for detailed SMTP communication.

## 🔒 **Security Best Practices**

### **Email Configuration Security:**
1. **Never commit passwords** to version control
2. **Use environment variables** for sensitive data
3. **Use App Passwords** instead of regular passwords
4. **Enable 2FA** on your email account
5. **Regularly rotate** email passwords

### **OTP Security:**
- OTP codes expire in 1 hour
- Codes are 6 digits (100,000+ combinations)
- Each code is single-use only
- Secure random generation

## 📱 **Testing Checklist**

### **Development Testing:**
- [ ] Email configuration loads without errors
- [ ] SMTP connection successful
- [ ] Email sends successfully
- [ ] Email arrives in inbox (check spam folder)
- [ ] OTP code is readable and correct
- [ ] Reset link works properly
- [ ] Email displays correctly on mobile

### **Production Testing:**
- [ ] Test with multiple email providers
- [ ] Verify email delivery speed
- [ ] Check spam folder placement
- [ ] Test email formatting on different clients
- [ ] Monitor error logs for issues

## 🚀 **Advanced Configuration**

### **Environment Variables (Recommended)**
Create `.env` file:
```
EMAIL_SERVICE=gmail
GMAIL_USERNAME=your-email@gmail.com
GMAIL_PASSWORD=your-app-password
EMAIL_FROM_NAME="Academic Excellence Platform"
```

Update `config/email.php`:
```php
$emailConfig = [
    'service' => $_ENV['EMAIL_SERVICE'] ?? 'gmail',
    'gmail' => [
        'username' => $_ENV['GMAIL_USERNAME'],
        'password' => $_ENV['GMAIL_PASSWORD'],
    ],
    'from' => [
        'name' => $_ENV['EMAIL_FROM_NAME'],
    ],
];
```

### **Email Queue (For High Volume)**
For high-volume applications, consider implementing an email queue system to prevent blocking the web request.

## 📊 **Monitoring**

### **What to Monitor:**
- Email delivery success rate
- SMTP connection failures
- OTP generation and usage
- Password reset completion rate

### **Log Files:**
- Check PHP error logs for email failures
- Monitor SMTP authentication issues
- Track OTP usage patterns

## 🎉 **Success!**

Once configured, your users will receive:
1. **Professional emails** with your branding
2. **Secure OTP codes** for verification
3. **Direct reset links** for convenience
4. **Clear instructions** and security warnings

The email system is now ready for production use with enterprise-grade security and reliability!

## 📞 **Support**

If you encounter issues:
1. Check the troubleshooting section above
2. Enable debug mode for detailed logs
3. Verify your email provider's documentation
4. Test with a simple email first

Happy emailing! 🎉