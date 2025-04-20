<?php
function get_verification_email($name, $token) {
    $verifyUrl = SITE_URL . '/verify.php?token=' . $token;
    
    return '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Verify Your Email - ' . SITE_NAME . '</title>
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
                <h2>Welcome to ' . SITE_NAME . '!</h2>
                
                <p>Dear ' . htmlspecialchars($name) . ',</p>
                
                <p>Thank you for registering with the ' . SITE_NAME . '. To complete your registration and activate your account, please verify your email address by clicking the button below:</p>
                
                <p style="text-align: center;">
                    <a href="' . $verifyUrl . '" class="button">Verify Email Address</a>
                </p>
                
                <p>If the button above doesn\'t work, you can also copy and paste the following link into your browser:</p>
                
                <p style="word-break: break-all;">' . $verifyUrl . '</p>
                
                <p>This verification link will expire in 24 hours for security purposes.</p>
                
                <p>If you did not create an account with us, please ignore this email or contact our support team if you believe this is an error.</p>
                
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
            </div>
        </div>
    </body>
    </html>';
}

function get_verification_text($name, $token) {
    $verifyUrl = SITE_URL . '/verify.php?token=' . $token;
    
    return "
    Welcome to " . SITE_NAME . "!

    Dear " . $name . ",

    Thank you for registering with the " . SITE_NAME . ". To complete your registration and activate your account, please verify your email address by visiting the following link:

    " . $verifyUrl . "

    This verification link will expire in 24 hours for security purposes.

    If you did not create an account with us, please ignore this email or contact our support team if you believe this is an error.

    Best regards,
    The " . SITE_NAME . " Team

    --
    " . SITE_NAME . "
    " . LIBRARY_ADDRESS . "
    " . LIBRARY_PHONE . "
    Library Hours: " . LIBRARY_HOURS . "

    For support, contact us at " . SUPPORT_EMAIL . "

    This is an automated message. Please do not reply to this email.";
}
?>
