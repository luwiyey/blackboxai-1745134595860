<?php
function get_payment_confirmation_email($name, $payment_details) {
    return '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Payment Confirmation - ' . SITE_NAME . '</title>
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
            .success-notice {
                background-color: #d4edda;
                border: 1px solid #c3e6cb;
                color: #155724;
                padding: 15px;
                border-radius: 5px;
                margin: 20px 0;
            }
            .payment-details {
                background-color: #f8f9fa;
                border: 1px solid #dee2e6;
                border-radius: 5px;
                padding: 20px;
                margin: 20px 0;
            }
            .receipt-number {
                font-size: 1.2em;
                color: #1E4B87;
                font-weight: bold;
                text-align: center;
                padding: 10px;
                background-color: #e9ecef;
                border-radius: 5px;
                margin: 20px 0;
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
                background-color: #4F7F3A;
                color: #ffffff;
                text-decoration: none;
                border-radius: 5px;
                margin: 20px 0;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <img src="' . SITE_URL . '/photos/PUlogo.png" alt="' . SITE_NAME . ' Logo">
            </div>
            
            <div class="content">
                <div class="success-icon">✓</div>
                
                <h2 style="text-align: center;">Payment Confirmed</h2>
                
                <p>Dear ' . htmlspecialchars($name) . ',</p>
                
                <p>Thank you for your payment. This email confirms that your payment has been successfully processed and verified.</p>
                
                <div class="payment-details">
                    <div class="amount">₱' . number_format($amount, 2) . '</div>
                    
                    <p style="text-align: center;">Reference Number:</p>
                    <div class="reference">' . $reference . '</div>
                    
                    <p style="text-align: center;">Date: ' . date('F j, Y g:i A') . '</p>
                </div>
                
                <p>You can view your payment history and download receipts by clicking the button below:</p>
                
                <p style="text-align: center;">
                    <a href="' . SITE_URL . '/dashboard.php?section=payments" class="button">View Payment History</a>
                </p>
                
                <p>If you have any questions about this payment, please don\'t hesitate to contact our support team.</p>
                
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
                
                <p>Transaction ID: ' . $reference . '<br>
                Time: ' . date('Y-m-d H:i:s') . '</p>
            </div>
        </div>
    </body>
    </html>';
}

function get_payment_confirmation_text($name, $amount, $reference) {
    return "
    Payment Confirmation - " . SITE_NAME . "

    Dear " . $name . ",

    Thank you for your payment. This email confirms that your payment has been successfully processed and verified.

    Payment Details:
    Amount: ₱" . number_format($amount, 2) . "
    Reference Number: " . $reference . "
    Date: " . date('F j, Y g:i A') . "

    You can view your payment history and download receipts by visiting:
    " . SITE_URL . "/dashboard.php?section=payments

    If you have any questions about this payment, please don't hesitate to contact our support team.

    Best regards,
    The " . SITE_NAME . " Team

    --
    " . SITE_NAME . "
    " . LIBRARY_ADDRESS . "
    " . LIBRARY_PHONE . "
    Library Hours: " . LIBRARY_HOURS . "

    For support, contact us at " . SUPPORT_EMAIL . "

    Transaction Details:
    Transaction ID: " . $reference . "
    Time: " . date('Y-m-d H:i:s') . "

    This is an automated message. Please do not reply to this email.";
}
?>
