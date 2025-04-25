<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Manage Tags - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
</head>
<body class="bg-gray-100 min-h-screen p-6">
    <div class="max-w-4xl mx-auto bg-white p-6 rounded shadow">
        <h1 class="text-2xl font-bold mb-4">Manage Tags</h1>
        <form id="addTagForm" class="mb-6 flex gap-2">
            <input type="text" id="tagName" placeholder="New tag name" class="flex-grow border border-gray-300 rounded px-3 py-2" required />
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition">Add Tag</button>
        </form>
        <table class="w-full border-collapse border border-gray-300">
            <thead>
                <tr class="bg-gray-200">
                    <th class="border border-gray-300 px-4 py-2 text-left">ID</th>
                    <th class="border border-gray-300 px-4 py-2 text-left">Name</th>
                    <th class="border border-gray-300 px-4 py-2 text-left">Actions</th>
                </tr>
            </thead>
            <tbody id="tagsTableBody">
                <!-- Tags will be loaded here -->
            </tbody>
        </table>
    </div>

    <script>
        async function fetchTags() {
            const res = await fetch('/api/tags.php');
            const data = await res.json();
            if (data.success) {
                const tbody = document.getElementById('tagsTableBody');
                tbody.innerHTML = '';
                data.data.forEach(tag => {
                    const tr = document.createElement('tr');
                    tr.className = 'border border-gray-300';
                    tr.innerHTML = `
                        <td class="border border-gray-300 px-4 py-2">${tag.id}</td>
                        <td class="border border-gray-300 px-4 py-2">
                            <input type="text" value="${tag.name}" data-id="${tag.id}" class="tag-name-input border border-gray-300 rounded px-2 py-1 w-full" />
                        </td>
                        <td class="border border-gray-300 px-4 py-2">
                            <button class="update-btn bg-green-500 text-white px-3 py-1 rounded hover:bg-green-600 transition mr-2" data-id="${tag.id}"><i class="fas fa-check"></i></button>
                            <button class="delete-btn bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600 transition" data-id="${tag.id}"><i class="fas fa-trash"></i></button>
                        </td>
                    `;
                    tbody.appendChild(tr);
                });
                attachEventListeners();
            } else {
                alert('Failed to load tags');
            }
        }

        function attachEventListeners() {
            document.querySelectorAll('.update-btn').forEach(btn => {
                btn.onclick = async () => {
                    const id = btn.dataset.id;
                    const input = btn.closest('tr').querySelector('.tag-name-input');
                    const name = input.value.trim();
                    if (!name) {
                        alert('Tag name cannot be empty');
                        return;
                    }
                    const res = await fetch('/api/tags.php', {
                        method: 'PUT',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id, name })
                    });
                    const data = await res.json();
                    alert(data.message);
                    if (data.success) fetchTags();
                };
            });

            document.querySelectorAll('.delete-btn').forEach(btn => {
                btn.onclick = async () => {
                    if (!confirm('Are you sure you want to delete this tag?')) return;
                    const id = btn.dataset.id;
                    const res = await fetch('/api/tags.php', {
                        method: 'DELETE',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id })
                    });
                    const data = await res.json();
                    alert(data.message);
                    if (data.success) fetchTags();
                };
            });
        }

        document.getElementById('addTagForm').onsubmit = async (e) => {
            e.preventDefault();
            const nameInput = document.getElementById('tagName');
            const name = nameInput.value.trim();
            if (!name) {
                alert('Tag name is required');
                return;
            }
            const res = await fetch('/api/tags.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ name })
            });
            const data = await res.json();
            alert(data.message);
            if (data.success) {
                nameInput.value = '';
                fetchTags();
            }
        };

        fetchTags();
    </script>
</body>
</html>
