<?php
function get_password_reset_email($name, $token) {
    $resetUrl = SITE_URL . '/reset-password.php?token=' . $token;
    
    return '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Reset Your Password - ' . SITE_NAME . '</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                line-height: 1.6;
                color: #333;
                margin: 0;
                padding: 0;
            }
            .container {
                max-width: 600px;
                margin: 0 auto;
                padding: 20px;
            }
            .header {
                text-align: center;
                padding: 20px 0;
                background-color: #1E4B87;
            }
            .header img {
                max-width: 150px;
            }
            .content {
                background-color: #ffffff;
                padding: 30px;
                border-radius: 5px;
                box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            }
            .button {
                display: inline-block;
                padding: 12px 24px;
                background-color: #4F7F3A;
                color: #ffffff;
                text-decoration: none;
                border-radius: 5px;
                margin: 20px 0;
            }
            .warning {
                background-color: #fff3cd;
                border: 1px solid #ffeeba;
                color: #856404;
                padding: 15px;
                border-radius: 5px;
                margin: 20px 0;
            }
            .footer {
                text-align: center;
                padding: 20px;
                color: #666;
                font-size: 12px;
            }
            .social-links {
                text-align: center;
                padding: 20px 0;
            }
            .social-links a {
                margin: 0 10px;
                color: #1E4B87;
                text-decoration: none;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <img src="' . SITE_URL . '/photos/PUlogo.png" alt="' . SITE_NAME . ' Logo">
            </div>
            
            <div class="content">
                <h2>Password Reset Request</h2>
                
                <p>Dear ' . htmlspecialchars($name) . ',</p>
                
                <p>We received a request to reset the password for your ' . SITE_NAME . ' account. To proceed with the password reset, please click the button below:</p>
                
                <p style="text-align: center;">
                    <a href="' . $resetUrl . '" class="button">Reset Password</a>
                </p>
                
                <p>If the button above doesn\'t work, you can also copy and paste the following link into your browser:</p>
                
                <p style="word-break: break-all;">' . $resetUrl . '</p>
                
                <div class="warning">
                    <strong>Important Security Notice:</strong>
                    <ul>
                        <li>This password reset link will expire in 1 hour.</li>
                        <li>If you didn\'t request a password reset, please ignore this email.</li>
                        <li>For security, please create a strong password that you haven\'t used before.</li>
                    </ul>
                </div>
                
                <p>If you did not request a password reset, please contact our support team immediately as your account may be compromised.</p>
                
                <p>Best regards,<br>The ' . SITE_NAME . ' Team</p>
            </div>
            
            <div class="social-links">
                <p>Follow us on social media:</p>
                <a href="' . FACEBOOK_URL . '">Facebook</a> |
                <a href="' . TWITTER_URL . '">Twitter</a> |
                <a href="' . INSTAGRAM_URL . '">Instagram</a> |
                <a href="' . LINKEDIN_URL . '">LinkedIn</a>
            </div>
            
            <div class="footer">
                <p>' . SITE_NAME . '<br>
                ' . LIBRARY_ADDRESS . '<br>
                ' . LIBRARY_PHONE . '</p>
                
                <p>Library Hours: ' . LIBRARY_HOURS . '</p>
                
                <p>This is an automated message. Please do not reply to this email.<br>
                For support, contact us at ' . SUPPORT_EMAIL . '</p>
                
                <p>IP Address: ' . $_SERVER['REMOTE_ADDR'] . '<br>
                Time: ' . date('Y-m-d H:i:s') . '</p>
            </div>
        </div>
    </body>
    </html>';
}

function get_password_reset_text($name, $token) {
    $resetUrl = SITE_URL . '/reset-password.php?token=' . $token;
    
    return "
    Password Reset Request - " . SITE_NAME . "

    Dear " . $name . ",

    We received a request to reset the password for your " . SITE_NAME . " account. To proceed with the password reset, please visit the following link:

    " . $resetUrl . "

    Important Security Notice:
    - This password reset link will expire in 1 hour.
    - If you didn't request a password reset, please ignore this email.
    - For security, please create a strong password that you haven't used before.

    If you did not request a password reset, please contact our support team immediately as your account may be compromised.

    Best regards,
    The " . SITE_NAME . " Team

    --
    " . SITE_NAME . "
    " . LIBRARY_ADDRESS . "
    " . LIBRARY_PHONE . "
    Library Hours: " . LIBRARY_HOURS . "

    For support, contact us at " . SUPPORT_EMAIL . "

    Security Information:
    IP Address: " . $_SERVER['REMOTE_ADDR'] . "
    Time: " . date('Y-m-d H:i:s') . "

    This is an automated message. Please do not reply to this email.";
}
?>
