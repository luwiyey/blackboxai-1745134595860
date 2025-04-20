# Pan Pacific University Library Management System

A modern, feature-rich library management system built for Pan Pacific University. The system provides comprehensive tools for managing library resources, user accounts, and administrative tasks.

## 🚀 Features

### 🔒 User Authentication & Security
- Multi-role authentication (Students, Librarians, Faculty, Admin)
- Password reset via email
- Role-based access control
- JWT-based token authentication
- Gesture-based login using MediaPipe
- CSRF protection and secure session management
- Google OAuth2 login (optional)
- Captcha protection for login/registration

### 🧍 User Roles & Dashboards

#### 👨‍🎓 Student Dashboard
- Book search by title, author, year, genre, keywords
- Voice-powered search
- Smart book recommendations
- Borrow books & view loan history
- Real-time notifications
- Submit reviews & ratings
- View book previews (PDF snippets)
- Access reading lists
- Profile management

#### 👨‍🏫 Faculty Dashboard
- Full book search & previews
- Create & manage course reading lists
- Reserve books
- Recommend books
- Upload academic content
- Track student engagement
- Request bulk borrowing
- Profile settings

#### 👩‍💼 Librarian Dashboard
- Manage book records
- Upload cover images and metadata
- View and manage inventory
- Manage loans
- Overdue tracking
- Export data (CSV, Excel, PDF)
- Approve faculty recommendations
- Manage user reviews

#### 🛡️ Admin Dashboard
- User management
- System settings
- Analytics and statistics
- Database backup
- System logs
- Audit trail

### 📚 Book & Loan Management
- Comprehensive book metadata
- Keyword tagging & filtering
- Real-time availability tracking
- QR code generation
- Auto-calculated due dates
- Rating and review system

### 💡 AI-Powered Features
- Smart Search using NLP
- AI-based recommendations
- Gesture-based login
- Voice commands
- Book popularity predictor

### 🧾 Financial System
- Overdue fines calculation
- Lost book penalties
- GCash integration with QR
- Payment verification
- Transaction records
- Financial reports

### 🌐 Frontend / UI Features
- Responsive UI (Bootstrap + Tailwind CSS)
- Dynamic UX with HTMX
- Role-specific themes
- Night mode
- Accessibility features

### 📊 Reports & Analytics
- Loan trends
- Popular books
- Student engagement
- Financial reports
- System logs
- Reading statistics

### 📦 Data Management
- CSV/Excel/PDF exports
- Bulk book uploads
- Database backup
- File management

### 📨 Notifications
- Email notifications
- In-app alerts
- Due date reminders
- Optional SMS notifications

## 🛠️ Technology Stack
- PHP 7.4+
- MySQL/MariaDB
- Tailwind CSS + Bootstrap
- HTMX
- MediaPipe (Gesture Recognition)
- Various PHP libraries (see composer.json)

## 📋 Requirements
- PHP >= 7.4
- MySQL/MariaDB
- Composer
- Web Server (Apache/Nginx)
- SSL Certificate (for production)

## 🔧 Installation

1. Clone the repository:
   ```bash
   git clone https://github.com/panpacificu/library-system.git
   cd library-system
   ```

2. Install dependencies:
   ```bash
   composer install
   ```

3. Set up environment:
   ```bash
   cp .env.example .env
   # Edit .env with your configuration
   ```

4. Initialize the system:
   ```bash
   php database/migrate.php init
   ```

5. Default admin credentials:
   - Email: admin@panpacificu.edu.ph
   - Password: admin123
   (Change these immediately after first login)

## 🚀 Available Commands

```bash
# Database Management
php database/migrate.php migrate   # Run pending migrations
php database/migrate.php rollback  # Rollback last batch
php database/migrate.php reset     # Reset database
php database/migrate.php refresh   # Reset and re-run migrations
php database/migrate.php init      # Initialize system

# Development Tools
composer test           # Run tests
composer test-coverage  # Generate test coverage
composer phpstan        # Static analysis
composer check-style    # Check code style
composer fix-style      # Fix code style
```

## 🔒 Security Features
- CSRF protection
- Input sanitization
- Session security
- Role-based access
- Password hashing
- JWT tokens
- Upload validation

## 🌎 Internationalization
- Multi-language support
- Filipino and English
- Extensible for more languages

## 🎯 Future Features
- Moodle/LMS Integration
- Library Chatbot
- AI-trained recommendations
- Department dashboards
- PWA support

## 📝 License
Proprietary - All rights reserved

## 🤝 Support
For support, email library@panpacificu.edu.ph
