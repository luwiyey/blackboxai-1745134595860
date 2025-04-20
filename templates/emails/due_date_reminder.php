<?php
function get_due_date_reminder_email($name, $book_title, $due_date) {
    $daysRemaining = ceil((strtotime($due_date) - time()) / (60 * 60 * 24));
    $finePerDay = number_format(FINE_PER_DAY, 2);
    
    return '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Book Due Date Reminder - ' . SITE_NAME . '</title>
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
            .alert {
                background-color: ' . ($daysRemaining <= 1 ? '#fff3cd' : '#d4edda') . ';
                border: 1px solid ' . ($daysRemaining <= 1 ? '#ffeeba' : '#c3e6cb') . ';
                color: ' . ($daysRemaining <= 1 ? '#856404' : '#155724') . ';
                padding: 15px;
                border-radius: 5px;
                margin: 20px 0;
            }
            .book-details {
                background-color: #f8f9fa;
                border: 1px solid #dee2e6;
                border-radius: 5px;
                padding: 20px;
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
                <h2>Book Due Date Reminder</h2>
                
                <p>Dear ' . htmlspecialchars($name) . ',</p>
                
                <div class="alert">
                    <strong>' . ($daysRemaining <= 1 ? 'Important Notice:' : 'Friendly Reminder:') . '</strong><br>
                    Your borrowed book is due ' . ($daysRemaining <= 0 ? 'today' : 'in ' . $daysRemaining . ' day(s)') . '.
                </div>
                
                <div class="book-details">
                    <h3>Book Details:</h3>
                    <p><strong>Title:</strong> ' . htmlspecialchars($book_title) . '</p>
                    <p><strong>Due Date:</strong> ' . date('F j, Y', strtotime($due_date)) . '</p>
                    <p><strong>Days Remaining:</strong> ' . $daysRemaining . '</p>
                </div>
                
                <p>Please ensure to return the book by the due date to avoid late fees (₱' . $finePerDay . ' per day).</p>
                
                <p>You have the following options:</p>
                <ul>
                    <li>Return the book to the library</li>
                    <li>Renew the book online (if eligible)</li>
                    <li>Contact the library for assistance</li>
                </ul>
                
                <p style="text-align: center;">
                    <a href="' . SITE_URL . '/dashboard.php?section=loans" class="button">Manage Your Loans</a>
                </p>
                
                <p><strong>Note:</strong> If you have already returned this book, please disregard this message.</p>
                
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

function get_due_date_reminder_text($name, $book_title, $due_date) {
    $daysRemaining = ceil((strtotime($due_date) - time()) / (60 * 60 * 24));
    $finePerDay = number_format(FINE_PER_DAY, 2);
    
    return "
    Book Due Date Reminder - " . SITE_NAME . "

    Dear " . $name . ",

    " . ($daysRemaining <= 1 ? 'IMPORTANT NOTICE:' : 'FRIENDLY REMINDER:') . "
    Your borrowed book is due " . ($daysRemaining <= 0 ? 'today' : 'in ' . $daysRemaining . ' day(s)') . ".

    Book Details:
    Title: " . $book_title . "
    Due Date: " . date('F j, Y', strtotime($due_date)) . "
    Days Remaining: " . $daysRemaining . "

    Please ensure to return the book by the due date to avoid late fees (₱" . $finePerDay . " per day).

    You have the following options:
    - Return the book to the library
    - Renew the book online (if eligible)
    - Contact the library for assistance

    To manage your loans, visit:
    " . SITE_URL . "/dashboard.php?section=loans

    Note: If you have already returned this book, please disregard this message.

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
