<?php
session_start();
require_once '../config/config.php';
require_once '../includes/Auth.php';
require_once '../includes/Database.php';
require_once '../includes/Settings.php';

// Require user to be logged in
Auth::requireLogin();

$db = new Database();
$conn = $db->getConnection();

// Load user settings or set defaults
$userId = $_SESSION['user']['id'];
$settings = new Settings($conn);
$userSettings = $settings->getUserSettings($userId);

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize inputs
    $highContrast = isset($_POST['high_contrast']) ? 1 : 0;
    $fontSize = filter_input(INPUT_POST, 'font_size', FILTER_VALIDATE_INT, ['options' => ['min_range' => 12, 'max_range' => 24]]);
    $screenReader = isset($_POST['screen_reader_enabled']) ? 1 : 0;
    $voiceNav = isset($_POST['voice_navigation_enabled']) ? 1 : 0;
    $emailNotif = isset($_POST['email_notifications']) ? 1 : 0;
    $twoFactor = isset($_POST['two_factor_auth']) ? 1 : 0;

    if ($fontSize === false) {
        $errors[] = "Font size must be between 12 and 24.";
    }

    if (empty($errors)) {
        $result = $settings->saveUserSettings($userId, [
            'high_contrast' => $highContrast,
            'font_size' => $fontSize,
            'screen_reader_enabled' => $screenReader,
            'voice_navigation_enabled' => $voiceNav,
            'email_notifications' => $emailNotif,
            'two_factor_auth' => $twoFactor
        ]);

        if ($result) {
            $success = "Settings saved successfully.";
            $userSettings = $settings->getUserSettings($userId); // reload
        } else {
            $errors[] = "Failed to save settings. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>User Settings - PPU Library</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&amp;display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
</head>
<body class="bg-gray-50 min-h-screen flex flex-col items-center justify-start py-10">
    <div class="w-full max-w-4xl bg-white rounded-lg shadow-lg p-8">
        <h1 class="text-3xl font-semibold mb-6 text-center">Settings</h1>

        <?php if (!empty($errors)): ?>
            <div class="mb-4 bg-red-100 text-red-700 p-3 rounded">
                <ul class="list-disc list-inside">
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="mb-4 bg-green-100 text-green-700 p-3 rounded">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-8">
            <section>
                <h2 class="text-xl font-semibold mb-4">Customization Settings</h2>
                <div class="flex items-center mb-4">
                    <label for="high_contrast" class="mr-3 font-medium">High Contrast Mode</label>
                    <input type="checkbox" id="high_contrast" name="high_contrast" class="toggle-checkbox" <?= $userSettings['high_contrast'] ? 'checked' : '' ?> />
                </div>
                <div class="mb-4">
                    <label for="font_size" class="block font-medium mb-1">Font Size: <span id="fontSizeValue"><?= $userSettings['font_size'] ?></span>px</label>
                    <input type="range" id="font_size" name="font_size" min="12" max="24" value="<?= $userSettings['font_size'] ?>" class="w-full" oninput="document.getElementById('fontSizeValue').textContent = this.value" />
                </div>
                <div class="flex items-center mb-4">
                    <label for="screen_reader_enabled" class="mr-3 font-medium">Screen Reader Settings</label>
                    <div>
                        <label class="mr-4"><input type="radio" name="screen_reader_enabled" value="1" <?= $userSettings['screen_reader_enabled'] ? 'checked' : '' ?> /> Enabled</label>
                        <label><input type="radio" name="screen_reader_enabled" value="0" <?= !$userSettings['screen_reader_enabled'] ? 'checked' : '' ?> /> Disabled</label>
                    </div>
                </div>
                <div class="flex items-center">
                    <label for="voice_navigation_enabled" class="mr-3 font-medium">Voice-Controlled Navigation</label>
                    <input type="checkbox" id="voice_navigation_enabled" name="voice_navigation_enabled" class="toggle-checkbox" <?= $userSettings['voice_navigation_enabled'] ? 'checked' : '' ?> />
                </div>
            </section>

            <section>
                <h2 class="text-xl font-semibold mb-4">Account & Privacy Management</h2>
                <div class="mb-4">
                    <label class="block font-medium mb-1">Account Name</label>
                    <input type="text" value="<?= htmlspecialchars($_SESSION['user']['name']) ?>" disabled class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-100 cursor-not-allowed" />
                </div>
                <div class="mb-4">
                    <label class="block font-medium mb-1">Email</label>
                    <input type="email" value="<?= htmlspecialchars($_SESSION['user']['email']) ?>" disabled class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-100 cursor-not-allowed" />
                </div>
                <div class="flex items-center mb-4">
                    <input type="checkbox" id="email_notifications" name="email_notifications" <?= $userSettings['email_notifications'] ? 'checked' : '' ?> />
                    <label for="email_notifications" class="ml-2 font-medium">Receive Email Notifications</label>
                </div>
                <div class="flex items-center">
                    <input type="checkbox" id="two_factor_auth" name="two_factor_auth" <?= $userSettings['two_factor_auth'] ? 'checked' : '' ?> />
                    <label for="two_factor_auth" class="ml-2 font-medium">Enable Two-factor Authentication</label>
                </div>
            </section>

            <div class="mt-8 flex justify-center">
                <button type="submit" class="bg-ppu-blue text-white px-6 py-3 rounded hover:bg-ppu-light-blue transition-colors">Save Settings</button>
            </div>
        </form>
    </div>
</body>
</html>
