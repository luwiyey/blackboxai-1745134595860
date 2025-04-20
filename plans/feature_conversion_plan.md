# Feature Conversion Plan: Django to PHP for PPU Library System

## Overview
This document outlines the plan to convert the existing Django-based features into PHP implementations, fitting the current PHP project structure and codebase.

---

## 1. Student Dashboard Features

- **Book Search**
  - Search by title, author, year, genre, keywords
  - Implement full-text search or SQL LIKE queries in PHP
- **Voice-powered Search**
  - Integrate Web Speech API (JavaScript) for speech recognition on frontend
  - Connect recognized text to backend search API
- **Smart Book Recommendations**
  - Analyze borrowing history stored in DB
  - Recommend books based on user behavior using PHP logic
- **Borrow Books & Loan History**
  - Borrowing functionality with DB updates
  - Display loan history with due dates and statuses
- **Notifications**
  - Real-time notifications via WebSocket or polling (optional)
  - Email notifications using PHP mail or SMTP libraries
- **Reviews & Ratings**
  - Submit and display user reviews and ratings for books
- **Book Previews**
  - Use PyMuPDF or PHP PDF libraries to generate PDF snippets
  - Display previews in frontend viewer
- **Reading Lists**
  - Access and manage reading lists shared by faculty
- **Profile Management**
  - Update profile picture, email, password securely

---

## 2. Faculty Dashboard Features

- **Full Book Search & Previews**
- **Course Reading Lists**
  - Create, update, delete reading lists
- **Book Reservations**
  - Reserve books in advance with availability checks
- **Recommendations**
  - Recommend books for course materials
- **Academic Content Uploads**
  - Upload PDFs, links, and metadata
- **Engagement Tracking**
  - Track student interactions with assigned books
- **Bulk Borrowing Requests**
- **Profile & Preference Settings**

---

## 3. Librarian Dashboard Features

- **Book Records Management**
  - Add, update, delete book records
  - Upload cover images and metadata
- **Inventory Management**
- **Loan Management**
  - Issue and return books
- **Overdue Tracking**
  - Use background jobs (e.g., cron) for reminders
- **Data Export**
  - Export loans and books data to CSV, Excel, PDF
- **Approvals**
  - Approve faculty recommendations
- **Review & Upload Management**

---

## 4. Admin Dashboard Features

- **User Management**
  - Create, edit, delete users
  - Assign roles and permissions
- **System Settings**
  - Configure borrowing limits, fine rates, etc.
- **Analytics & Statistics**
- **Database Export & Backup**
- **System Logs Monitoring**
  - Integrate Sentry or PHP logging libraries
- **Audit Trail**
  - Track user actions and changes

---

## Implementation Notes

- Use existing PHP classes and DB schema where possible.
- Follow MVC or similar design patterns for maintainability.
- Use Tailwind CSS and Font Awesome for UI consistency.
- Implement secure authentication and authorization.
- Use AJAX and modern JS for dynamic features.
- Plan for incremental development and testing.

---

## Next Steps

- Prioritize features based on user roles and criticality.
- Begin with Student Dashboard core features.
- Develop reusable components and APIs.
- Schedule regular demos for feedback.

---

Prepared by: BLACKBOXAI  
Date: 2025-04-20
