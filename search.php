<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Search - Pan Pacific University Library</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f9fafb;
        }
        .section-title {
            border-bottom: 4px solid #FDB913;
            display: inline-block;
            padding-bottom: 4px;
            margin-bottom: 1rem;
        }
        .icon-box {
            width: 100px;
            height: 100px;
            border: 2px solid #5C0000;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            transition: all 0.3s ease;
            color: #5C0000;
            font-size: 2rem;
        }
        .quick-link {
            border: 1px solid #ddd;
            border-radius: 0.5rem;
            padding: 2rem 1rem;
            text-align: center;
            transition: all 0.3s ease;
            background-color: white;
        }
        .quick-link:hover {
            box-shadow: 0 4px 12px rgba(92, 0, 0, 0.3);
            border-color: #5C0000;
            cursor: pointer;
        }
        .quick-link:hover .icon-box {
            background-color: #5C0000;
            color: white;
        }
        a {
            text-decoration: none;
            color: #5C0000;
            font-weight: 600;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <header class="bg-upd-maroon py-6">
        <div class="container mx-auto px-4">
            <a href="index.php" class="text-white text-3xl font-semibold">Pan Pacific University Library</a>
        </div>
    </header>

    <main class="container mx-auto px-4 py-12">
        <h1 class="text-4xl font-bold mb-8 text-center text-[#5C0000]">Library Search</h1>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
            <a href="search_books.php" class="quick-link">
                <div class="icon-box"><i class="fas fa-book"></i></div>
                Library Catalog
