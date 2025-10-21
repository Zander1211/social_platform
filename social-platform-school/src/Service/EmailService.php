<?php

class EmailService
{
    private $config;
    private $fromEmail;
    private $fromName;
    
    public function __construct()
    {
        // Load email configuration
        $this->config = require_once __DIR__ . '/../../config/email.php';
        $this->fromEmail = $this->config['from']['email'];
        $this->fromName = $this->config['from']['name'];
    }
    
    /**
     * Send password reset email with OTP
     * 
     * @param string $toEmail
     * @param string $resetToken
     * @param string $otp Optional OTP code
     * @param string $baseUrl
     * @return bool
     */
    public function sendPasswordResetEmail($toEmail, $resetToken, $otp = null, $baseUrl = '')
    {
        try {
            // If no base URL provided, try to determine it
            if (empty($baseUrl)) {
                $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
                $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                $baseUrl = $protocol . '://' . $host . dirname($_SERVER['REQUEST_URI']);
            }
            
            $resetUrl = rtrim($baseUrl, '/') . '/reset_password.php?token=' . $resetToken;
            
            $subject = 'Password Reset Request - Academic Excellence Platform';
            
            // Generate OTP if not provided
            if ($otp === null) {
                $otp = $this->generateOTP();
            }
            
            $htmlMessage = $this->getPasswordResetEmailTemplate($resetUrl, $otp);
            $textMessage = $this->getPasswordResetEmailTextTemplate($resetUrl, $otp);
            
            // Send email based on configured service
            return $this->sendEmail($toEmail, $subject, $htmlMessage, $textMessage);
            
        } catch (Exception $e) {
            error_log('Email sending failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send OTP email
     * 
     * @param string $toEmail
     * @param string $otp
     * @param string $purpose
     * @return bool
     */
    public function sendOTPEmail($toEmail, $otp, $purpose = 'Password Reset')
    {
        try {
            $subject = "Your OTP Code - Academic Excellence Platform";
            
            $htmlMessage = $this->getOTPEmailTemplate($otp, $purpose);
            $textMessage = $this->getOTPEmailTextTemplate($otp, $purpose);
            
            return $this->sendEmail($toEmail, $subject, $htmlMessage, $textMessage);
            
        } catch (Exception $e) {
            error_log('OTP email sending failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Generate a 6-digit OTP
     * 
     * @return string
     */
    public function generateOTP()
    {
        return str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }
    
    /**
     * Send email using configured service
     * 
     * @param string $toEmail
     * @param string $subject
     * @param string $htmlMessage
     * @param string $textMessage
     * @return bool
     */
    private function sendEmail($toEmail, $subject, $htmlMessage, $textMessage = '')
    {
        switch ($this->config['service']) {
            case 'gmail':
                return $this->sendViaGmail($toEmail, $subject, $htmlMessage);
                
            case 'smtp':
                return $this->sendViaSMTP($toEmail, $subject, $htmlMessage);
                
            case 'mail':
            default:
                return $this->sendViaPHPMail($toEmail, $subject, $htmlMessage);
        }
    }
    
    /**
     * Send email via Gmail SMTP
     * 
     * @param string $toEmail
     * @param string $subject
     * @param string $htmlMessage
     * @return bool
     */
    private function sendViaGmail($toEmail, $subject, $htmlMessage)
    {
        $gmail = $this->config['gmail'];
        
        // Gmail SMTP settings
        $smtpHost = 'smtp.gmail.com';
        $smtpPort = 587;
        $smtpUsername = $gmail['username'];
        $smtpPassword = $gmail['password'];
        
        return $this->sendViaSMTPSocket($smtpHost, $smtpPort, $smtpUsername, $smtpPassword, $toEmail, $subject, $htmlMessage, 'tls');
    }
    
    /**
     * Send email via custom SMTP
     * 
     * @param string $toEmail
     * @param string $subject
     * @param string $htmlMessage
     * @return bool
     */
    private function sendViaSMTP($toEmail, $subject, $htmlMessage)
    {
        $smtp = $this->config['smtp'];
        
        return $this->sendViaSMTPSocket(
            $smtp['host'], 
            $smtp['port'], 
            $smtp['username'], 
            $smtp['password'], 
            $toEmail, 
            $subject, 
            $htmlMessage, 
            $smtp['encryption']
        );
    }
    
    /**
     * Send email via SMTP socket connection
     * 
     * @param string $host
     * @param int $port
     * @param string $username
     * @param string $password
     * @param string $toEmail
     * @param string $subject
     * @param string $htmlMessage
     * @param string $encryption
     * @return bool
     */
    private function sendViaSMTPSocket($host, $port, $username, $password, $toEmail, $subject, $htmlMessage, $encryption = null)
    {
        try {
            // Create socket connection
            $socket = fsockopen($host, $port, $errno, $errstr, $this->config['settings']['timeout']);
            
            if (!$socket) {
                throw new Exception("Could not connect to SMTP server: $errstr ($errno)");
            }
            
            // Read initial response
            $this->readSMTPResponse($socket);
            
            // Send EHLO
            fwrite($socket, "EHLO " . $_SERVER['HTTP_HOST'] . "\r\n");
            $this->readSMTPResponse($socket);
            
            // Start TLS if required
            if ($encryption === 'tls') {
                fwrite($socket, "STARTTLS\r\n");
                $this->readSMTPResponse($socket);
                
                if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new Exception('Failed to enable TLS encryption');
                }
                
                // Send EHLO again after TLS
                fwrite($socket, "EHLO " . $_SERVER['HTTP_HOST'] . "\r\n");
                $this->readSMTPResponse($socket);
            }
            
            // Authenticate
            fwrite($socket, "AUTH LOGIN\r\n");
            $this->readSMTPResponse($socket);
            
            fwrite($socket, base64_encode($username) . "\r\n");
            $this->readSMTPResponse($socket);
            
            fwrite($socket, base64_encode($password) . "\r\n");
            $this->readSMTPResponse($socket);
            
            // Send email
            fwrite($socket, "MAIL FROM: <{$this->fromEmail}>\r\n");
            $this->readSMTPResponse($socket);
            
            fwrite($socket, "RCPT TO: <{$toEmail}>\r\n");
            $this->readSMTPResponse($socket);
            
            fwrite($socket, "DATA\r\n");
            $this->readSMTPResponse($socket);
            
            // Email headers and content
            $headers = [
                "From: {$this->fromName} <{$this->fromEmail}>",
                "To: {$toEmail}",
                "Subject: {$subject}",
                "MIME-Version: 1.0",
                "Content-Type: text/html; charset=UTF-8",
                "Date: " . date('r'),
                "Message-ID: <" . uniqid() . "@{$_SERVER['HTTP_HOST']}>"
            ];
            
            $emailContent = implode("\r\n", $headers) . "\r\n\r\n" . $htmlMessage . "\r\n.\r\n";
            fwrite($socket, $emailContent);
            $this->readSMTPResponse($socket);
            
            // Quit
            fwrite($socket, "QUIT\r\n");
            $this->readSMTPResponse($socket);
            
            fclose($socket);
            
            return true;
            
        } catch (Exception $e) {
            if (isset($socket) && $socket) {
                fclose($socket);
            }
            error_log('SMTP sending failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Read SMTP response
     * 
     * @param resource $socket
     * @return string
     * @throws Exception
     */
    private function readSMTPResponse($socket)
    {
        $response = '';
        while ($line = fgets($socket, 515)) {
            $response .= $line;
            if (substr($line, 3, 1) == ' ') {
                break;
            }
        }
        
        if ($this->config['settings']['debug']) {
            error_log('SMTP Response: ' . trim($response));
        }
        
        $code = substr($response, 0, 3);
        if ($code >= 400) {
            throw new Exception('SMTP Error: ' . trim($response));
        }
        
        return $response;
    }
    
    /**
     * Send email via PHP mail() function
     * 
     * @param string $toEmail
     * @param string $subject
     * @param string $htmlMessage
     * @return bool
     */
    private function sendViaPHPMail($toEmail, $subject, $htmlMessage)
    {
        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $this->fromName . ' <' . $this->fromEmail . '>',
            'Reply-To: ' . $this->fromEmail,
            'X-Mailer: PHP/' . phpversion()
        ];
        
        return mail($toEmail, $subject, $htmlMessage, implode("\r\n", $headers));
    }
    
    /**
     * Get HTML email template for password reset with OTP
     */
    private function getPasswordResetEmailTemplate($resetUrl, $otp)
    {
        return '
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Password Reset Request</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; padding: 30px 20px; text-align: center; border-radius: 12px 12px 0 0; }
                .content { background: #ffffff; padding: 30px; border: 1px solid #e5e7eb; }
                .footer { background: #f9fafb; padding: 20px; text-align: center; border-radius: 0 0 12px 12px; border: 1px solid #e5e7eb; border-top: none; }
                .button { display: inline-block; background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; margin: 20px 0; font-weight: bold; }
                .otp-box { background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); border: 2px solid #3b82f6; padding: 20px; border-radius: 12px; text-align: center; margin: 25px 0; }
                .otp-code { font-size: 32px; font-weight: bold; color: #1e40af; letter-spacing: 8px; font-family: monospace; margin: 10px 0; }
                .warning { background: #fef2f2; border: 2px solid #fecaca; color: #dc2626; padding: 20px; border-radius: 8px; margin: 20px 0; }
                .url-box { background: #f3f4f6; padding: 15px; border-radius: 6px; word-break: break-all; font-family: monospace; font-size: 14px; margin: 15px 0; }
                .icon { font-size: 48px; margin-bottom: 15px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <div class="icon">🎓</div>
                    <h1>Academic Excellence Platform</h1>
                    <h2>Password Reset Request</h2>
                </div>
                <div class="content">
                    <p>Hello,</p>
                    
                    <p>We received a request to reset your password for your Academic Excellence Platform account. To proceed with the password reset, you can use either of the following methods:</p>
                    
                    <div class="otp-box">
                        <h3 style="margin-top: 0; color: #1e40af;">🔐 Your OTP Code</h3>
                        <div class="otp-code">' . htmlspecialchars($otp) . '</div>
                        <p style="margin-bottom: 0; font-size: 14px; color: #6b7280;">Enter this code on the password reset page</p>
                    </div>
                    
                    <p><strong>OR</strong></p>
                    
                    <p style="text-align: center;">
                        <a href="' . htmlspecialchars($resetUrl) . '" class="button">🔑 Reset Password Directly</a>
                    </p>
                    
                    <p>You can also copy and paste this link into your browser:</p>
                    <div class="url-box">' . htmlspecialchars($resetUrl) . '</div>
                    
                    <div class="warning">
                        <strong>⚠️ Important Security Information:</strong>
                        <ul style="margin: 10px 0;">
                            <li>This OTP and reset link will expire in <strong>1 hour</strong></li>
                            <li>If you did not request this password reset, please ignore this email</li>
                            <li>Never share your OTP code or reset link with anyone</li>
                            <li>Our support team will never ask for your password or OTP</li>
                        </ul>
                    </div>
                    
                    <p>If you continue to have problems, please contact our support team.</p>
                    
                    <p>Best regards,<br>
                    <strong>The Academic Excellence Platform Team</strong></p>
                </div>
                <div class="footer">
                    <p style="margin: 0; font-size: 12px; color: #6b7280;">This is an automated message. Please do not reply to this email.</p>
                    <p style="margin: 5px 0 0 0; font-size: 12px; color: #6b7280;">© ' . date('Y') . ' Academic Excellence Platform. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>';
    }
    
    /**
     * Get OTP email template
     */
    private function getOTPEmailTemplate($otp, $purpose)
    {
        return '
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Your OTP Code</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
                .container { max-width: 500px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); color: white; padding: 30px 20px; text-align: center; border-radius: 12px 12px 0 0; }
                .content { background: #ffffff; padding: 30px; border: 1px solid #e5e7eb; }
                .footer { background: #f9fafb; padding: 20px; text-align: center; border-radius: 0 0 12px 12px; border: 1px solid #e5e7eb; border-top: none; }
                .otp-box { background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); border: 2px solid #3b82f6; padding: 30px; border-radius: 12px; text-align: center; margin: 25px 0; }
                .otp-code { font-size: 36px; font-weight: bold; color: #1e40af; letter-spacing: 10px; font-family: monospace; margin: 15px 0; }
                .icon { font-size: 48px; margin-bottom: 15px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <div class="icon">🔐</div>
                    <h1>Your OTP Code</h1>
                    <p style="margin: 0; opacity: 0.9;">' . htmlspecialchars($purpose) . '</p>
                </div>
                <div class="content">
                    <p>Hello,</p>
                    
                    <p>Here is your One-Time Password (OTP) for ' . htmlspecialchars($purpose) . ':</p>
                    
                    <div class="otp-box">
                        <div class="otp-code">' . htmlspecialchars($otp) . '</div>
                        <p style="margin: 0; font-size: 14px; color: #6b7280;">This code will expire in 10 minutes</p>
                    </div>
                    
                    <p><strong>Important:</strong></p>
                    <ul>
                        <li>Enter this code exactly as shown</li>
                        <li>Do not share this code with anyone</li>
                        <li>If you did not request this code, please ignore this email</li>
                    </ul>
                    
                    <p>Best regards,<br>
                    <strong>The Academic Excellence Platform Team</strong></p>
                </div>
                <div class="footer">
                    <p style="margin: 0; font-size: 12px; color: #6b7280;">This is an automated message. Please do not reply to this email.</p>
                </div>
            </div>
        </body>
        </html>';
    }
    
    /**
     * Get plain text email template for password reset
     */
    private function getPasswordResetEmailTextTemplate($resetUrl, $otp)
    {
        return "
Academic Excellence Platform - Password Reset Request

Hello,

We received a request to reset your password for your Academic Excellence Platform account.

YOUR OTP CODE: {$otp}

You can also use this link to reset your password:
{$resetUrl}

IMPORTANT SECURITY INFORMATION:
- This OTP and reset link will expire in 1 hour
- If you did not request this password reset, please ignore this email
- Never share your OTP code or reset link with anyone
- Our support team will never ask for your password or OTP

If you continue to have problems, please contact our support team.

Best regards,
The Academic Excellence Platform Team

---
This is an automated message. Please do not reply to this email.
© " . date('Y') . " Academic Excellence Platform. All rights reserved.
        ";
    }
    
    /**
     * Get plain text OTP email template
     */
    private function getOTPEmailTextTemplate($otp, $purpose)
    {
        return "
Academic Excellence Platform - Your OTP Code

Hello,

Here is your One-Time Password (OTP) for {$purpose}:

OTP CODE: {$otp}

IMPORTANT:
- Enter this code exactly as shown
- This code will expire in 10 minutes
- Do not share this code with anyone
- If you did not request this code, please ignore this email

Best regards,
The Academic Excellence Platform Team

---
This is an automated message. Please do not reply to this email.
        ";
    }
}
?>