<?php
session_start();
require_once '../config/config.php';
require_once '../includes/Auth.php';

// Check if user is logged in and is a student or faculty
Auth::requireRole(['student', 'faculty']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Borrowing Calendar - PPU Library</title>
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/main.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/main.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/htmx.org@1.9.2"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&amp;display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="../assets/style.css" />
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'ppu-green': '#4F7F3A',
                        'ppu-blue': '#1E4B87',
                        'ppu-light-blue': '#3A75D4',
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50">
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between h-16 items-center">
                <h1 class="text-xl font-bold text-ppu-blue">PPU Library - Borrowing Calendar</h1>
                <div class="flex items-center space-x-4">
                    <a href="../logout.php" class="text-red-600 hover:text-red-800 flex items-center">
                        <i class="fas fa-sign-out-alt mr-1"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto p-6">
        <div id="calendar"></div>

        <!-- Faculty Reading Deadline Form -->
        <?php if ($_SESSION['user']['role'] === 'faculty'): ?>
        <div class="mt-8 bg-white p-6 rounded-lg shadow-lg max-w-md">
            <h2 class="text-lg font-semibold mb-4 text-ppu-blue">Create Reading Deadline</h2>
            <form id="readingDeadlineForm" hx-post="../api/create_reading_deadline.php" hx-target="#formMessage" hx-swap="innerHTML">
                <div class="mb-4">
                    <label for="class" class="block text-gray-700 mb-2">Class/Course</label>
                    <input type="text" id="class" name="class" required class="w-full border rounded px-3 py-2" />
                </div>
                <div class="mb-4">
                    <label for="deadline" class="block text-gray-700 mb-2">Deadline Date</label>
                    <input type="date" id="deadline" name="deadline" required class="w-full border rounded px-3 py-2" />
                </div>
                <button type="submit" class="bg-ppu-blue text-white px-4 py-2 rounded hover:bg-ppu-light-blue transition">Create Deadline</button>
            </form>
            <div id="formMessage" class="mt-4 text-sm"></div>
        </div>
        <?php endif; ?>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                events: '../api/get_borrowing_events.php',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                eventColor: '#1E4B87',
                eventDisplay: 'block',
                height: 'auto'
            });
            calendar.render();
        });
    </script>
</body>
</html>
