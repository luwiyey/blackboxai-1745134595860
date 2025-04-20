<?php
function get_book_available_email($name, $book_title, $reservation_id, $expiry_date) {
    return '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Book Available for Pickup - ' . SITE_NAME . '</title>
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
                background-color: #d4edda;
                border: 1px solid #c3e6cb;
                color: #155724;
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
            .expiry-notice {
                background-color: #fff3cd;
                border: 1px solid #ffeeba;
                color: #856404;
                padding: 15px;
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
                <h2>Book Available for Pickup</h2>
                
                <p>Dear ' . htmlspecialchars($name) . ',</p>
                
                <div class="alert">
                    <strong>Good News!</strong><br>
                    The book you reserved is now available for pickup.
                </div>
                
                <div class="book-details">
                    <h3>Book Details:</h3>
                    <p><strong>Title:</strong> ' . htmlspecialchars($book_title) . '</p>
                    <p><strong>Reservation ID:</strong> ' . $reservation_id . '</p>
                </div>
                
                <div class="expiry-notice">
                    <strong>Important:</strong><br>
                    Please pick up your book by ' . date('F j, Y', strtotime($expiry_date)) . '.<br>
                    After this date, the reservation will expire and the book will be made available to other users.
                </div>
                
                <p>To pick up your book:</p>
                <ul>
                    <li>Visit the library circulation desk during operating hours</li>
                    <li>Present your student ID</li>
                    <li>Mention your reservation ID</li>
                </ul>
                
                <p style="text-align: center;">
                    <a href="' . SITE_URL . '/dashboard.php?section=reservations" class="button">View Reservation Details</a>
                </p>
                
                <p><strong>Library Hours:</strong></p>
                <p>' . LIBRARY_HOURS . '</p>
                
                <p>If you no longer need this book, please cancel your reservation through your dashboard to make it available for other users.</p>
                
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

function get_book_available_text($name, $book_title, $reservation_id, $expiry_date) {
    return "
    Book Available for Pickup - " . SITE_NAME . "

    Dear " . $name . ",

    Good News!
    The book you reserved is now available for pickup.

    Book Details:
    Title: " . $book_title . "
    Reservation ID: " . $reservation_id . "

    Important:
    Please pick up your book by " . date('F j, Y', strtotime($expiry_date)) . ".
    After this date, the reservation will expire and the book will be made available to other users.

    To pick up your book:
    - Visit the library circulation desk during operating hours
    - Present your student ID
    - Mention your reservation ID

    View your reservation details at:
    " . SITE_URL . "/dashboard.php?section=reservations

    Library Hours:
    " . LIBRARY_HOURS . "

    If you no longer need this book, please cancel your reservation through your dashboard to make it available for other users.

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
