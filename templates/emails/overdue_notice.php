<?php
function get_overdue_notice_email($name, $book_title, $due_date, $fine_amount) {
    $daysOverdue = floor((time() - strtotime($due_date)) / (60 * 60 * 24));
    $finePerDay = number_format(FINE_PER_DAY, 2);
    
    return '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Overdue Book Notice - ' . SITE_NAME . '</title>
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
                background-color: #f8d7da;
                border: 1px solid #f5c6cb;
                color: #721c24;
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
            .fine-details {
                background-color: #fff3cd;
                border: 1px solid #ffeeba;
                color: #856404;
                padding: 20px;
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
                <h2>Overdue Book Notice</h2>
                
                <p>Dear ' . htmlspecialchars($name) . ',</p>
                
                <div class="alert">
                    <strong>Important Notice:</strong><br>
                    The following book is overdue by ' . $daysOverdue . ' day(s).
                </div>
                
                <div class="book-details">
                    <h3>Book Details:</h3>
                    <p><strong>Title:</strong> ' . htmlspecialchars($book_title) . '</p>
                    <p><strong>Due Date:</strong> ' . date('F j, Y', strtotime($due_date)) . '</p>
                    <p><strong>Days Overdue:</strong> ' . $daysOverdue . '</p>
                </div>
                
                <div class="fine-details">
                    <h3>Fine Details:</h3>
                    <p><strong>Fine Rate:</strong> ₱' . $finePerDay . ' per day</p>
                    <p><strong>Current Fine Amount:</strong> ₱' . number_format($fine_amount, 2) . '</p>
                </div>
                
                <p>Please take immediate action to:</p>
                <ul>
                    <li>Return the book to the library as soon as possible</li>
                    <li>Pay the accumulated fine</li>
                    <li>Contact the library if you have any issues</li>
                </ul>
                
                <p style="text-align: center;">
                    <a href="' . SITE_URL . '/dashboard.php?section=fines" class="button">Pay Fine Online</a>
                </p>
                
                <p><strong>Important:</strong> Continued failure to return the book and pay the fine may result in:</p>
                <ul>
                    <li>Suspension of borrowing privileges</li>
                    <li>Additional penalties</li>
                    <li>Hold on academic records</li>
                </ul>
                
                <p>If you have already returned this book, please visit the library to resolve this matter.</p>
                
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

function get_overdue_notice_text($name, $book_title, $due_date, $fine_amount) {
    $daysOverdue = floor((time() - strtotime($due_date)) / (60 * 60 * 24));
    $finePerDay = number_format(FINE_PER_DAY, 2);
    
    return "
    Overdue Book Notice - " . SITE_NAME . "

    Dear " . $name . ",

    IMPORTANT NOTICE:
    The following book is overdue by " . $daysOverdue . " day(s).

    Book Details:
    Title: " . $book_title . "
    Due Date: " . date('F j, Y', strtotime($due_date)) . "
    Days Overdue: " . $daysOverdue . "

    Fine Details:
    Fine Rate: ₱" . $finePerDay . " per day
    Current Fine Amount: ₱" . number_format($fine_amount, 2) . "

    Please take immediate action to:
    - Return the book to the library as soon as possible
    - Pay the accumulated fine
    - Contact the library if you have any issues

    To pay your fine online, visit:
    " . SITE_URL . "/dashboard.php?section=fines

    Important: Continued failure to return the book and pay the fine may result in:
    - Suspension of borrowing privileges
    - Additional penalties
    - Hold on academic records

    If you have already returned this book, please visit the library to resolve this matter.

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
