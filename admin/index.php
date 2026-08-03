<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../helpers/session.php';
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../helpers/upload.php';
require_once __DIR__ . '/../models/Database.php';

if (!is_logged_in()) {
    redirect(BASE_URL . 'auth/login.php');
}

$user = current_user();
if (!$user || (int)$user['role_id'] !== 1) {
    redirect(BASE_URL . 'auth/login.php');
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

$stats = [
    'members' => 0,
    'events' => 0,
    'media_items' => 0,
    'users' => 0,
];

$eventMessage = '';
$eventError = '';
$editingEvent = null;
$events = [];
$usersList = [];
$userMessage = '';
$userError = '';
$galleryItems = [];
$membersList = [];
$memoriesByYear = [];
$prayerRequests = [];
$settings = [];
$settingsMessage = '';
$settingsError = '';
$contactLeaders = [];
$leaderMessage = '';
$leaderError = '';
$downloadFiles = [];
$downloadMessage = '';
$downloadError = '';

try {
    $pdo = Database::connect();

    $eventMediaTypeCheck = $pdo->query("SHOW COLUMNS FROM events LIKE 'media_type'");
    if (!$eventMediaTypeCheck->fetch()) {
        $pdo->exec("ALTER TABLE events ADD COLUMN media_type ENUM('photo', 'video', 'audio') NULL AFTER event_date");
    }

    $eventMediaFilenameCheck = $pdo->query("SHOW COLUMNS FROM events LIKE 'media_filename'");
    if (!$eventMediaFilenameCheck->fetch()) {
        $pdo->exec("ALTER TABLE events ADD COLUMN media_filename VARCHAR(255) NULL AFTER media_type");
    }

    $queries = [
        'members' => 'SELECT COUNT(*) FROM members',
        'events' => 'SELECT COUNT(*) FROM events',
        'media_items' => 'SELECT COUNT(*) FROM media_items',
        'users' => 'SELECT COUNT(*) FROM users',
    ];

    foreach ($queries as $key => $sql) {
        $stmt = $pdo->query($sql);
        $stats[$key] = (int)$stmt->fetchColumn();
    }

    $eventsStmt = $pdo->query('SELECT id, title, description, event_date, media_type, media_filename FROM events ORDER BY event_date DESC, id DESC LIMIT 5');
    $events = $eventsStmt->fetchAll();

    $galleryStmt = $pdo->query('SELECT id, title, year, type, filename, user_id, created_at FROM media_items ORDER BY id DESC LIMIT 8');
    $galleryItems = $galleryStmt->fetchAll();

    $membersStmt = $pdo->query('SELECT m.id, m.name, m.role, m.photo, m.email, m.user_id FROM members m ORDER BY m.id DESC LIMIT 8');
    $membersList = $membersStmt->fetchAll();

    $memoriesStmt = $pdo->query('SELECT m.year, COUNT(*) AS total_items, GROUP_CONCAT(DISTINCT m.type ORDER BY m.type) AS types, GROUP_CONCAT(DISTINCT u.fullname ORDER BY u.fullname SEPARATOR ", ") AS uploaders
        FROM media_items m
        LEFT JOIN users u ON u.id = m.user_id
        GROUP BY m.year
        ORDER BY m.year DESC LIMIT 6');
    $memoriesByYear = $memoriesStmt->fetchAll();

    $prayerStmt = $pdo->query('SELECT id, name, request, status, created_at FROM prayer_requests ORDER BY id DESC LIMIT 8');
    $prayerRequests = $prayerStmt->fetchAll();

    $usersStmt = $pdo->query('SELECT u.id, u.fullname, u.email, u.role_id, m.photo
        FROM users u
        LEFT JOIN members m ON m.user_id = u.id
        ORDER BY u.id DESC');
    $usersList = $usersStmt->fetchAll();

    $settingsStmt = $pdo->query('SELECT setting_key, setting_value FROM site_settings ORDER BY setting_key ASC');
    $settings = $settingsStmt->fetchAll();
    $settingsMap = [];
    foreach ($settings as $setting) {
        $settingsMap[$setting['setting_key']] = $setting['setting_value'];
    }

    $leadersStmt = $pdo->query('SELECT id, name, role, telegram, phone, photo FROM contact_leaders ORDER BY id ASC');
    $contactLeaders = $leadersStmt->fetchAll();

    $downloadFilesStmt = $pdo->query('SELECT id, display_name, file_type, stored_filename FROM about_download_files ORDER BY file_type ASC, display_name ASC');
    $downloadFiles = $downloadFilesStmt->fetchAll();

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_event_id'])) {
        $editEventId = (int)($_POST['edit_event_id'] ?? 0);
        if ($editEventId > 0) {
            $editStmt = $pdo->prepare('SELECT id, title, description, event_date, media_type, media_filename FROM events WHERE id = :id LIMIT 1');
            $editStmt->execute(['id' => $editEventId]);
            $editingEvent = $editStmt->fetch();
            if (!$editingEvent) {
                $eventError = 'The selected event could not be found for editing.';
            }
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_event_form'])) {
        $eventId = (int)($_POST['event_id'] ?? 0);
        $title = trim($_POST['event_title'] ?? '');
        $description = trim($_POST['event_description'] ?? '');
        $eventDate = trim($_POST['event_date'] ?? '');
        $mediaType = trim($_POST['event_media_type'] ?? 'photo');
        $mediaFile = $_FILES['event_media'] ?? null;
        $isUpdate = $eventId > 0;

        if ($title === '' || $eventDate === '' || $description === '') {
            $eventError = 'Please enter the event title, date, and description.';
        } elseif (!$isUpdate && (!$mediaFile || $mediaFile['error'] !== UPLOAD_ERR_OK)) {
            $eventError = 'Please upload an image, video, or audio file for the event.';
        } else {
            $allowedTypes = [];
            $destinationDir = __DIR__ . '/../assets/uploads/events';

            switch ($mediaType) {
                case 'video':
                    $allowedTypes = ['mp4', 'mov', 'avi', 'mkv', 'webm'];
                    break;
                case 'audio':
                    $allowedTypes = ['mp3', 'wav', 'ogg', 'm4a'];
                    break;
                case 'photo':
                default:
                    $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                    $mediaType = 'photo';
                    break;
            }

            $uploadedFilename = null;
            if ($mediaFile && is_array($mediaFile) && ($mediaFile['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
                require_once __DIR__ . '/../helpers/upload.php';
                $uploadedFilename = upload_file($mediaFile, $destinationDir, $allowedTypes, 50 * 1024 * 1024);
                if (!$uploadedFilename) {
                    $eventError = 'The uploaded event media is invalid. Please use a supported file type.';
                }
            }

            if (!$eventError) {
                if ($isUpdate) {
                    $existingStmt = $pdo->prepare('SELECT media_type, media_filename FROM events WHERE id = :id LIMIT 1');
                    $existingStmt->execute(['id' => $eventId]);
                    $existingEvent = $existingStmt->fetch();

                    if (!$existingEvent) {
                        $eventError = 'The event you are trying to update could not be found.';
                    } else {
                        $finalMediaType = $uploadedFilename ? $mediaType : ($existingEvent['media_type'] ?? 'photo');
                        $finalFilename = $uploadedFilename ?: ($existingEvent['media_filename'] ?? null);

                        $updateStmt = $pdo->prepare('UPDATE events SET title = :title, description = :description, event_date = :event_date, media_type = :media_type, media_filename = :media_filename WHERE id = :id');
                        $updateStmt->execute([
                            'title' => $title,
                            'description' => $description,
                            'event_date' => $eventDate,
                            'media_type' => $finalMediaType,
                            'media_filename' => $finalFilename,
                            'id' => $eventId,
                        ]);

                        $eventMessage = 'Event updated successfully.';
                        $editingEvent = null;
                    }
                } else {
                    require_once __DIR__ . '/../helpers/upload.php';
                    $uploadedFilename = upload_file($mediaFile, $destinationDir, $allowedTypes, 50 * 1024 * 1024);
                    if (!$uploadedFilename) {
                        $eventError = 'The uploaded event media is invalid. Please use a supported file type.';
                    } else {
                        $insertStmt = $pdo->prepare('INSERT INTO events (title, description, event_date, media_type, media_filename) VALUES (:title, :description, :event_date, :media_type, :media_filename)');
                        $insertStmt->execute([
                            'title' => $title,
                            'description' => $description,
                            'event_date' => $eventDate,
                            'media_type' => $mediaType,
                            'media_filename' => $uploadedFilename,
                        ]);

                        $eventMessage = 'Event saved successfully with uploaded media.';
                    }
                }

                $eventsStmt = $pdo->query('SELECT id, title, description, event_date, media_type, media_filename FROM events ORDER BY event_date DESC, id DESC LIMIT 5');
                $events = $eventsStmt->fetchAll();
            }
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contact_leader_form'])) {
        $leaderName = trim($_POST['leader_name'] ?? '');
        $leaderRole = trim($_POST['leader_role'] ?? '');
        $leaderTelegram = trim($_POST['leader_telegram'] ?? '');
        $leaderPhone = trim($_POST['leader_phone'] ?? '');
        $leaderPhoto = $_FILES['leader_photo'] ?? null;

        if ($leaderName === '' || $leaderRole === '' || $leaderPhone === '') {
            $leaderError = 'Please enter the leader name, role, and phone number.';
        } else {
            $photoFilename = null;
            if ($leaderPhoto && is_array($leaderPhoto) && ($leaderPhoto['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
                $photoFilename = upload_file($leaderPhoto, __DIR__ . '/../assets/uploads/leaders', ['jpg', 'jpeg', 'png', 'gif', 'webp'], 5 * 1024 * 1024);
                if (!$photoFilename) {
                    $leaderError = 'The leader photo must be a valid JPG, PNG, GIF, or WebP image.';
                }
            }

            if (!$leaderError) {
                $insertStmt = $pdo->prepare('INSERT INTO contact_leaders (name, role, telegram, phone, photo) VALUES (:name, :role, :telegram, :phone, :photo)');
                $insertStmt->execute([
                    'name' => $leaderName,
                    'role' => $leaderRole,
                    'telegram' => $leaderTelegram,
                    'phone' => $leaderPhone,
                    'photo' => $photoFilename,
                ]);

                $leaderMessage = 'Leader contact added successfully.';
                $leadersStmt = $pdo->query('SELECT id, name, role, telegram, phone, photo FROM contact_leaders ORDER BY id ASC');
                $contactLeaders = $leadersStmt->fetchAll();
            }
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_event_id'])) {
        $deleteEventId = (int)($_POST['delete_event_id'] ?? 0);

        if ($deleteEventId > 0) {
            $deleteEventRow = $pdo->prepare('SELECT media_filename FROM events WHERE id = :id LIMIT 1');
            $deleteEventRow->execute(['id' => $deleteEventId]);
            $deleteEventData = $deleteEventRow->fetch();

            if ($deleteEventData && !empty($deleteEventData['media_filename'])) {
                @unlink(__DIR__ . '/../assets/uploads/events/' . $deleteEventData['media_filename']);
            }

            $pdo->prepare('DELETE FROM events WHERE id = :id')->execute(['id' => $deleteEventId]);
            $eventsStmt = $pdo->query('SELECT id, title, description, event_date, media_type, media_filename FROM events ORDER BY event_date DESC, id DESC LIMIT 5');
            $events = $eventsStmt->fetchAll();
            $eventMessage = 'Event removed successfully.';
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_leader_id'])) {
        $deleteLeaderId = (int)($_POST['delete_leader_id'] ?? 0);

        if ($deleteLeaderId > 0) {
            $pdo->prepare('DELETE FROM contact_leaders WHERE id = :id')->execute(['id' => $deleteLeaderId]);
            $leadersStmt = $pdo->query('SELECT id, name, role, telegram, phone, photo FROM contact_leaders ORDER BY id ASC');
            $contactLeaders = $leadersStmt->fetchAll();
            $leaderMessage = 'Leader removed successfully.';
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_download_id'])) {
        $deleteDownloadId = (int)($_POST['delete_download_id'] ?? 0);

        if ($deleteDownloadId > 0) {
            $downloadRow = $pdo->prepare('SELECT stored_filename FROM about_download_files WHERE id = :id')->execute(['id' => $deleteDownloadId]);
            $downloadRow = $pdo->query('SELECT stored_filename FROM about_download_files WHERE id = ' . $deleteDownloadId)->fetch();
            if ($downloadRow) {
                @unlink(__DIR__ . '/../assets/downloads/' . $downloadRow['stored_filename']);
            }
            $pdo->prepare('DELETE FROM about_download_files WHERE id = :id')->execute(['id' => $deleteDownloadId]);
            $downloadFilesStmt = $pdo->query('SELECT id, display_name, file_type, stored_filename FROM about_download_files ORDER BY file_type ASC, display_name ASC');
            $downloadFiles = $downloadFilesStmt->fetchAll();
            $downloadMessage = 'Download file removed successfully.';
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user_id'])) {
        $deleteUserId = (int)($_POST['delete_user_id'] ?? 0);

        if ($deleteUserId <= 0) {
            $userError = 'Invalid user selected for deletion.';
        } elseif ($deleteUserId === (int)$user['id']) {
            $userError = 'You cannot delete your own admin account from this screen.';
        } else {
            $pdo->beginTransaction();
            $pdo->prepare('DELETE FROM members WHERE user_id = :user_id')->execute(['user_id' => $deleteUserId]);
            $pdo->prepare('DELETE FROM users WHERE id = :id')->execute(['id' => $deleteUserId]);
            $pdo->commit();

            $userMessage = 'User removed successfully.';
            $usersStmt = $pdo->query('SELECT u.id, u.fullname, u.email, u.role_id, m.photo
                FROM users u
                LEFT JOIN members m ON m.user_id = u.id
                ORDER BY u.id DESC');
            $usersList = $usersStmt->fetchAll();
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_member_id'])) {
        $deleteMemberId = (int)($_POST['delete_member_id'] ?? 0);

        if ($deleteMemberId > 0) {
            $pdo->prepare('DELETE FROM members WHERE id = :id')->execute(['id' => $deleteMemberId]);
            $membersStmt = $pdo->query('SELECT m.id, m.name, m.role, m.photo, m.email, m.user_id FROM members m ORDER BY m.id DESC LIMIT 8');
            $membersList = $membersStmt->fetchAll();
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_media_id'])) {
        $deleteMediaId = (int)($_POST['delete_media_id'] ?? 0);

        if ($deleteMediaId > 0) {
            $mediaRowStmt = $pdo->prepare('SELECT type, filename FROM media_items WHERE id = :id LIMIT 1');
            $mediaRowStmt->execute(['id' => $deleteMediaId]);
            $mediaRow = $mediaRowStmt->fetch();

            if ($mediaRow && !empty($mediaRow['filename'])) {
                $mediaDirectory = __DIR__ . '/../assets/uploads/memories/' . ($mediaRow['type'] === 'photo' ? 'photos' : ($mediaRow['type'] === 'video' ? 'videos' : 'audio'));
                @unlink($mediaDirectory . DIRECTORY_SEPARATOR . $mediaRow['filename']);
            }

            $pdo->prepare('DELETE FROM media_items WHERE id = :id')->execute(['id' => $deleteMediaId]);
            $galleryStmt = $pdo->query('SELECT id, title, year, type, filename, user_id, created_at FROM media_items ORDER BY id DESC LIMIT 8');
            $galleryItems = $galleryStmt->fetchAll();
            $memoriesStmt = $pdo->query('SELECT m.year, COUNT(*) AS total_items, GROUP_CONCAT(DISTINCT m.type ORDER BY m.type) AS types, GROUP_CONCAT(DISTINCT u.fullname ORDER BY u.fullname SEPARATOR ", ") AS uploaders
                FROM media_items m
                LEFT JOIN users u ON u.id = m.user_id
                GROUP BY m.year
                ORDER BY m.year DESC LIMIT 6');
            $memoriesByYear = $memoriesStmt->fetchAll();
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_prayer_id'])) {
        $deletePrayerId = (int)($_POST['delete_prayer_id'] ?? 0);

        if ($deletePrayerId > 0) {
            $pdo->prepare('DELETE FROM prayer_requests WHERE id = :id')->execute(['id' => $deletePrayerId]);
            $prayerStmt = $pdo->query('SELECT id, name, request, status, created_at FROM prayer_requests ORDER BY id DESC LIMIT 8');
            $prayerRequests = $prayerStmt->fetchAll();
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_settings_form'])) {
        $siteName = trim($_POST['site_name'] ?? '');
        $tagline = trim($_POST['site_tagline'] ?? '');
        $contactEmail = trim($_POST['contact_email'] ?? '');
        $contactPhone = trim($_POST['contact_phone'] ?? '');
        $contactAddress = trim($_POST['contact_address'] ?? '');
        $aboutTitle = trim($_POST['about_download_title'] ?? 'Dambii Ittin Bulmaataa');
        $aboutSubtitle = trim($_POST['about_download_subtitle'] ?? 'Group 2014 Batch');
        $aboutDescription = trim($_POST['about_download_description'] ?? 'Dambii fi qajeelcha group keenya PDF, PowerPoint yookaan txt keessatti argachuu dandeessu.');
        $dailyVerseText = trim($_POST['daily_verse_text'] ?? '');
        $dailyVerseReference = trim($_POST['daily_verse_reference'] ?? '');
        $dailyVerseDate = trim($_POST['daily_verse_date'] ?? date('Y-m-d'));
        $memorialAudioFile = $_FILES['memorial_audio_file'] ?? null;
        $memorialPdfFile = $_FILES['memorial_pdf_file'] ?? null;
        $memorialPptFile = $_FILES['memorial_ppt_file'] ?? null;
        $memorialPhotoFiles = [
            'memorial_photo_1_file' => $_FILES['memorial_photo_1_file'] ?? null,
            'memorial_photo_2_file' => $_FILES['memorial_photo_2_file'] ?? null,
            'memorial_photo_3_file' => $_FILES['memorial_photo_3_file'] ?? null,
        ];
        $deletePhotoFlags = [
            'memorial_photo_1_filename' => !empty($_POST['delete_memorial_photo_1'] ?? ''),
            'memorial_photo_2_filename' => !empty($_POST['delete_memorial_photo_2'] ?? ''),
            'memorial_photo_3_filename' => !empty($_POST['delete_memorial_photo_3'] ?? ''),
        ];

        $payload = [
            'site_name' => $siteName,
            'site_tagline' => $tagline,
            'contact_email' => $contactEmail,
            'contact_phone' => $contactPhone,
            'contact_address' => $contactAddress,
            'about_download_title' => $aboutTitle,
            'about_download_subtitle' => $aboutSubtitle,
            'about_download_description' => $aboutDescription,
        ];

        if ($dailyVerseText !== '' && $dailyVerseReference !== '' && $dailyVerseDate !== '') {
            $dailyVerseStmt = $pdo->prepare('INSERT INTO daily_verses (verse_text, reference, verse_date) VALUES (:verse_text, :reference, :verse_date)
                ON DUPLICATE KEY UPDATE verse_text = VALUES(verse_text), reference = VALUES(reference)');
            $dailyVerseStmt->execute([
                'verse_text' => $dailyVerseText,
                'reference' => $dailyVerseReference,
                'verse_date' => $dailyVerseDate,
            ]);
            $settingsMessage = 'Daily verse saved successfully.';
        }

        $resourceDirs = [
            'photo' => __DIR__ . '/../assets/uploads/memories/photos',
            'audio' => __DIR__ . '/../assets/uploads/memories/audio',
            'pdf' => __DIR__ . '/../assets/downloads',
            'ppt' => __DIR__ . '/../assets/downloads',
        ];

        foreach ($deletePhotoFlags as $settingKey => $shouldDelete) {
            if (!$shouldDelete) {
                continue;
            }

            $currentValue = $settingsMap[$settingKey] ?? null;
            if ($currentValue) {
                @unlink($resourceDirs['photo'] . DIRECTORY_SEPARATOR . $currentValue);
            }

            $payload[$settingKey] = null;
        }

        foreach ($memorialPhotoFiles as $inputKey => $uploadedPhoto) {
            if (!$uploadedPhoto || !is_array($uploadedPhoto) || ($uploadedPhoto['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                continue;
            }

            $resourceExtension = strtolower(pathinfo($uploadedPhoto['name'], PATHINFO_EXTENSION));
            if (!in_array($resourceExtension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
                continue;
            }

            $settingKey = str_replace('_file', '_filename', $inputKey);
            $storedFilename = upload_file($uploadedPhoto, $resourceDirs['photo'], [$resourceExtension], 5 * 1024 * 1024);
            if ($storedFilename) {
                $payload[$settingKey] = $storedFilename;
            }
        }

        $resourceFiles = [
            'memorial_audio_filename' => [
                'file' => $memorialAudioFile,
                'type' => ['mp3', 'wav', 'ogg', 'm4a'],
                'dir' => $resourceDirs['audio'],
                'key' => 'memorial_audio_filename',
            ],
            'memorial_pdf_filename' => [
                'file' => $memorialPdfFile,
                'type' => ['pdf'],
                'dir' => $resourceDirs['pdf'],
                'key' => 'memorial_pdf_filename',
            ],
            'memorial_ppt_filename' => [
                'file' => $memorialPptFile,
                'type' => ['ppt', 'pptx'],
                'dir' => $resourceDirs['ppt'],
                'key' => 'memorial_ppt_filename',
            ],
        ];

        foreach ($resourceFiles as $resourceKey => $resourceMeta) {
            $uploadedResource = $resourceMeta['file'];
            if (!$uploadedResource || !is_array($uploadedResource) || ($uploadedResource['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                continue;
            }

            $resourceExtension = strtolower(pathinfo($uploadedResource['name'], PATHINFO_EXTENSION));
            if (!in_array($resourceExtension, $resourceMeta['type'], true)) {
                continue;
            }

            $storedFilename = upload_file($uploadedResource, $resourceMeta['dir'], [$resourceExtension], $resourceMeta['key'] === 'memorial_audio_filename' ? 15 * 1024 * 1024 : 10 * 1024 * 1024);
            if ($storedFilename) {
                $payload[$resourceMeta['key']] = $storedFilename;
            }
        }

        $uploadedFiles = $_FILES['about_download_files'] ?? null;
        $destinationDir = __DIR__ . '/../assets/downloads';

        if ($uploadedFiles && is_array($uploadedFiles['name'] ?? null)) {
            foreach ($uploadedFiles['name'] as $index => $fileName) {
                $file = [
                    'name' => $uploadedFiles['name'][$index] ?? '',
                    'type' => $uploadedFiles['type'][$index] ?? '',
                    'tmp_name' => $uploadedFiles['tmp_name'][$index] ?? '',
                    'error' => $uploadedFiles['error'][$index] ?? UPLOAD_ERR_NO_FILE,
                    'size' => $uploadedFiles['size'][$index] ?? 0,
                ];

                if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                    continue;
                }

                $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $allowedExtensions = ['pdf', 'ppt', 'pptx', 'doc', 'docx', 'txt'];
                if (!in_array($extension, $allowedExtensions, true)) {
                    continue;
                }

                $fileType = match ($extension) {
                    'pdf' => 'pdf',
                    'ppt', 'pptx' => 'ppt',
                    'doc', 'docx' => 'word',
                    'txt' => 'txt',
                };

                $storedFilename = upload_file($file, $destinationDir, [$extension], 10 * 1024 * 1024);
                if ($storedFilename) {
                    $insertFileStmt = $pdo->prepare('INSERT INTO about_download_files (display_name, file_type, stored_filename) VALUES (:display_name, :file_type, :stored_filename)');
                    $insertFileStmt->execute([
                        'display_name' => pathinfo($file['name'], PATHINFO_FILENAME),
                        'file_type' => $fileType,
                        'stored_filename' => $storedFilename,
                    ]);
                }
            }
        }

        foreach ($payload as $key => $value) {
            $upsertStmt = $pdo->prepare('INSERT INTO site_settings (setting_key, setting_value) VALUES (:key, :value) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
            $upsertStmt->execute(['key' => $key, 'value' => $value]);
        }

        $settingsStmt = $pdo->query('SELECT setting_key, setting_value FROM site_settings ORDER BY setting_key ASC');
        $settings = $settingsStmt->fetchAll();
        $settingsMap = [];
        foreach ($settings as $setting) {
            $settingsMap[$setting['setting_key']] = $setting['setting_value'];
        }

        $downloadFilesStmt = $pdo->query('SELECT id, display_name, file_type, stored_filename FROM about_download_files ORDER BY file_type ASC, display_name ASC');
        $downloadFiles = $downloadFilesStmt->fetchAll();

        $settingsMessage = 'Site settings saved successfully.';
    }
} catch (PDOException $e) {
    $stats['error'] = $e->getMessage();
    $eventError = $e->getMessage();
    $settingsError = $e->getMessage();
}
?>

<main class="admin-dashboard-page py-4 py-lg-5">
    <div class="container-fluid px-3 px-lg-4">
        <div class="row g-4 mb-5 align-items-start">
            <div class="col-lg-3 col-xl-2">
                <?php require_once __DIR__ . '/../includes/admin_nav.php'; ?>
            </div>
            <div class="col-lg-9 col-xl-10">
                <section id="control-center" data-admin-panel="control-center" class="admin-hero rounded-4 overflow-hidden shadow-lg position-relative mb-5">
                    <div class="row g-0 align-items-center">
                        <div class="col-lg-7 p-5 text-white">
                            <span class="badge bg-warning text-dark rounded-pill py-2 px-3 mb-3">Admin Control</span>
                            <h1 class="display-5 fw-bold mb-3">Hello, <?php echo htmlspecialchars($user['fullname']); ?>.</h1>
                            <p class="lead text-white-75 mb-4">Run the family hub with clarity, speed, and a modern admin experience. Manage events, memories, gallery uploads, and users all from one place.</p>
                            <div class="text-white-75 small fw-semibold">Use the sidebar menu to switch between the dashboard sections instantly.</div>
                        </div>
                        <div class="col-lg-5 position-relative">
                            <div class="ratio ratio-4x3">
                                <img src="https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?auto=format&fit=crop&w=900&q=80" alt="Admin workspace" class="img-fluid object-fit-cover h-100 w-100">
                            </div>
                        </div>
                    </div>
                    <div class="admin-hero-glow"></div>
                </section>

                <div id="admin" data-admin-panel="admin" class="row g-3 mb-4 admin-section-panel d-none">
                    <div class="col-sm-6 col-xl-3">
                        <div class="card admin-stat-card border-0 p-4">
                            <div class="stat-label mb-2">Members</div>
                            <div class="stat-value"><?php echo $stats['members']; ?></div>
                            <div class="stat-note">Total family profiles</div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-xl-3">
                        <div class="card admin-stat-card border-0 p-4">
                            <div class="stat-label mb-2">Events</div>
                            <div class="stat-value"><?php echo $stats['events']; ?></div>
                            <div class="stat-note">Scheduled family events</div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-xl-3">
                        <div class="card admin-stat-card border-0 p-4">
                            <div class="stat-label mb-2">Media</div>
                            <div class="stat-value"><?php echo $stats['media_items']; ?></div>
                            <div class="stat-note">Photos, videos, and audio</div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-xl-3">
                        <div class="card admin-stat-card border-0 p-4">
                            <div class="stat-label mb-2">Users</div>
                            <div class="stat-value"><?php echo $stats['users']; ?></div>
                            <div class="stat-note">Registered admin/users</div>
                        </div>
                    </div>
                </div>

                <div class="row g-3 admin-section-grid">
                    <div id="events" data-admin-panel="events" class="col-12 admin-section-panel d-none">
                        <div class="card admin-action-card border-0 h-100 p-4">
                            <div class="d-flex align-items-start justify-content-between mb-4 flex-wrap gap-2">
                                <div>
                                    <h2 class="card-title mb-2">Events</h2>
                                    <p class="text-muted mb-0">Add and review events from the database.</p>
                                </div>
                                <span class="badge bg-primary">Database</span>
                            </div>

                            <?php if ($eventMessage): ?>
                                <div class="alert alert-success rounded-4 mb-3"><?php echo htmlspecialchars($eventMessage); ?></div>
                            <?php endif; ?>

                            <?php if ($eventError): ?>
                                <div class="alert alert-danger rounded-4 mb-3"><?php echo htmlspecialchars($eventError); ?></div>
                            <?php endif; ?>

                            <form method="post" enctype="multipart/form-data" class="row g-3 mb-4">
                                <input type="hidden" name="admin_event_form" value="1">
                                <input type="hidden" name="event_id" value="<?php echo (int)($editingEvent['id'] ?? 0); ?>">
                                <div class="col-md-5">
                                    <label class="form-label" for="event_title">Event Title</label>
                                    <input type="text" class="form-control" id="event_title" name="event_title" value="<?php echo htmlspecialchars($editingEvent['title'] ?? ''); ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="event_date">Event Date</label>
                                    <input type="date" class="form-control" id="event_date" name="event_date" value="<?php echo htmlspecialchars($editingEvent['event_date'] ?? ''); ?>" required>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label" for="event_description">Description</label>
                                    <textarea class="form-control" id="event_description" name="event_description" rows="3" placeholder="Add a description for this event" required><?php echo htmlspecialchars($editingEvent['description'] ?? ''); ?></textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="event_media_type">Media Type</label>
                                    <select class="form-select" id="event_media_type" name="event_media_type" required>
                                        <option value="photo" <?php echo (($editingEvent['media_type'] ?? 'photo') === 'photo') ? 'selected' : ''; ?>>Photo</option>
                                        <option value="video" <?php echo (($editingEvent['media_type'] ?? 'photo') === 'video') ? 'selected' : ''; ?>>Video</option>
                                        <option value="audio" <?php echo (($editingEvent['media_type'] ?? 'photo') === 'audio') ? 'selected' : ''; ?>>Audio</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="event_media">Upload Media</label>
                                    <input type="file" class="form-control" id="event_media" name="event_media" accept="image/*,video/*,audio/*" <?php echo empty($editingEvent) ? 'required' : ''; ?>>
                                    <?php if (!empty($editingEvent['media_filename'])): ?>
                                        <small class="text-muted d-block mt-2">Current file: <?php echo htmlspecialchars($editingEvent['media_filename']); ?></small>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-12 d-flex gap-2 flex-wrap">
                                    <button type="submit" class="btn btn-primary"><?php echo $editingEvent ? 'Update Event' : 'Save Event'; ?></button>
                                    <?php if ($editingEvent): ?>
                                        <button type="submit" class="btn btn-outline-secondary" formaction="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] . '#events'); ?>">Cancel Edit</button>
                                    <?php endif; ?>
                                </div>
                            </form>

                            <div class="border-top pt-3">
                                <h3 class="h6 fw-bold mb-3">Recent Events</h3>
                                <?php if (empty($events)): ?>
                                    <p class="text-muted mb-0">No events found yet.</p>
                                <?php else: ?>
                                    <div class="d-flex flex-column gap-2">
                                        <?php foreach ($events as $event): ?>
                                            <div class="border rounded-4 p-3 bg-light-subtle">
                                                <div class="d-flex align-items-start justify-content-between gap-2 flex-wrap">
                                                    <div>
                                                        <div class="fw-bold mb-1"><?php echo htmlspecialchars($event['title']); ?></div>
                                                        <div class="small text-muted"><?php echo htmlspecialchars($event['event_date']); ?></div>
                                                    </div>
                                                    <span class="badge bg-secondary">ID <?php echo (int)$event['id']; ?></span>
                                                </div>
                                                <?php if (!empty($event['description'])): ?>
                                                    <p class="mb-0 mt-2 text-muted small"><?php echo htmlspecialchars($event['description']); ?></p>
                                                <?php endif; ?>
                                                <?php if (!empty($event['media_filename'])): ?>
                                                    <div class="mt-3">
                                                        <?php if (($event['media_type'] ?? '') === 'video'): ?>
                                                            <video class="img-fluid rounded-3 w-100" controls src="<?php echo htmlspecialchars(rtrim(BASE_URL, '/') . '/assets/uploads/events/' . $event['media_filename']); ?>"></video>
                                                        <?php elseif (($event['media_type'] ?? '') === 'audio'): ?>
                                                            <audio class="w-100" controls src="<?php echo htmlspecialchars(rtrim(BASE_URL, '/') . '/assets/uploads/events/' . $event['media_filename']); ?>"></audio>
                                                        <?php else: ?>
                                                            <img class="img-fluid rounded-3 w-100" src="<?php echo htmlspecialchars(rtrim(BASE_URL, '/') . '/assets/uploads/events/' . $event['media_filename']); ?>" alt="<?php echo htmlspecialchars($event['title']); ?>">
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="mt-3 d-flex gap-2 flex-wrap">
                                                    <form method="post" class="d-inline">
                                                        <input type="hidden" name="edit_event_id" value="<?php echo (int)$event['id']; ?>">
                                                        <button type="submit" class="btn btn-outline-primary btn-sm">Edit</button>
                                                    </form>
                                                    <form method="post" class="d-inline">
                                                        <input type="hidden" name="delete_event_id" value="<?php echo (int)$event['id']; ?>">
                                                        <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('Remove this event?');">Delete</button>
                                                    </form>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div id="gallery" data-admin-panel="gallery" class="col-12 admin-section-panel d-none">
                        <div class="card admin-action-card border-0 h-100 p-4">
                            <div class="d-flex align-items-start justify-content-between mb-4 flex-wrap gap-2">
                                <div>
                                    <h2 class="card-title mb-2">Gallery</h2>
                                    <p class="text-muted mb-0">Latest uploaded gallery items from the database.</p>
                                </div>
                                <span class="badge bg-primary">Database</span>
                            </div>

                            <?php if (empty($galleryItems)): ?>
                                <p class="text-muted mb-0">No gallery items were found in the database.</p>
                            <?php else: ?>
                                <div class="d-flex flex-column gap-2">
                                    <?php foreach ($galleryItems as $item): ?>
                                        <div class="border rounded-4 p-3 bg-warning-subtle">
                                            <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">
                                                <div>
                                                    <div class="fw-bold"><?php echo htmlspecialchars($item['title']); ?></div>
                                                    <div class="small text-muted"><?php echo htmlspecialchars(ucfirst($item['type'])); ?> • <?php echo htmlspecialchars($item['year']); ?></div>
                                                    <div class="small text-muted mt-1">File: <?php echo htmlspecialchars($item['filename']); ?></div>
                                                </div>
                                                <form method="post" class="m-0">
                                                    <input type="hidden" name="delete_media_id" value="<?php echo (int)$item['id']; ?>">
                                                    <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('Remove this media item?');">Delete</button>
                                                </form>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div id="members" data-admin-panel="members" class="col-12 admin-section-panel d-none">
                        <div class="card admin-action-card border-0 h-100 p-4">
                            <div class="d-flex align-items-start justify-content-between mb-4 flex-wrap gap-2">
                                <div>
                                    <h2 class="card-title mb-2">Members</h2>
                                    <p class="text-muted mb-0">Live list of registered family members from the database.</p>
                                </div>
                                <span class="badge bg-primary">Database</span>
                            </div>

                            <?php if (empty($membersList)): ?>
                                <p class="text-muted mb-0">No member profiles found.</p>
                            <?php else: ?>
                                <div class="row g-3">
                                    <?php foreach ($membersList as $member): ?>
                                        <div class="col-md-6">
                                            <div class="border rounded-4 p-3 h-100 bg-light-subtle">
                                                <div class="d-flex align-items-center gap-3 mb-3">
                                                    <img src="<?php echo htmlspecialchars(!empty($member['photo']) ? member_photo_url($member['photo']) : 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?auto=format&fit=crop&w=600&q=80'); ?>"
                                                        alt="<?php echo htmlspecialchars($member['name']); ?>"
                                                        class="rounded-circle border border-2 border-warning"
                                                        style="width: 60px; height: 60px; object-fit: cover;">
                                                    <div>
                                                        <div class="fw-bold"><?php echo htmlspecialchars($member['name']); ?></div>
                                                        <div class="small text-muted"><?php echo htmlspecialchars($member['role'] ?? 'Family Member'); ?></div>
                                                    </div>
                                                </div>
                                                <div class="d-flex justify-content-between align-items-center gap-2">
                                                    <span class="small text-muted"><?php echo htmlspecialchars($member['email']); ?></span>
                                                    <form method="post" class="m-0">
                                                        <input type="hidden" name="delete_member_id" value="<?php echo (int)$member['id']; ?>">
                                                        <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('Remove this member?');">Delete</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div id="memories" data-admin-panel="memories" class="col-12 admin-section-panel d-none">
                        <div class="card admin-action-card border-0 h-100 p-4">
                            <div class="d-flex align-items-start justify-content-between mb-4 flex-wrap gap-2">
                                <div>
                                    <h2 class="card-title mb-2">Memories</h2>
                                    <p class="text-muted mb-0">Latest uploaded family media from the database.</p>
                                </div>
                                <span class="badge bg-primary">Database</span>
                            </div>

                            <?php if (empty($memoriesByYear)): ?>
                                <p class="text-muted mb-0">No memory years were found in the database yet.</p>
                            <?php else: ?>
                                <div class="row g-3">
                                    <?php foreach ($memoriesByYear as $memoryYear): ?>
                                        <div class="col-md-6">
                                            <div class="border rounded-4 p-3 h-100 bg-light-subtle">
                                                <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                                                    <div>
                                                        <div class="fw-bold">Explore <?php echo htmlspecialchars((int)$memoryYear['year']); ?></div>
                                                        <div class="small text-muted"><?php echo (int)$memoryYear['total_items']; ?> memory item(s)</div>
                                                    </div>
                                                    <span class="badge bg-secondary-subtle text-dark">Year Archive</span>
                                                </div>
                                                <div class="small text-muted mb-1">Types: <?php echo htmlspecialchars($memoryYear['types'] ?? 'n/a'); ?></div>
                                                <div class="small text-muted">Sent by: <?php echo htmlspecialchars($memoryYear['uploaders'] ?? 'Unknown'); ?></div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div id="prayers" data-admin-panel="prayers" class="col-12 admin-section-panel d-none">
                        <div class="card admin-action-card border-0 h-100 p-4">
                            <div class="d-flex align-items-start justify-content-between mb-4 flex-wrap gap-2">
                                <div>
                                    <h2 class="card-title mb-2">Prayers</h2>
                                    <p class="text-muted mb-0">Live prayer requests pulled from the database.</p>
                                </div>
                                <span class="badge bg-primary">Database</span>
                            </div>

                            <?php if (empty($prayerRequests)): ?>
                                <p class="text-muted mb-0">No prayer requests found.</p>
                            <?php else: ?>
                                <div class="d-flex flex-column gap-2">
                                    <?php foreach ($prayerRequests as $prayer): ?>
                                        <div class="border rounded-4 p-3 bg-light-subtle">
                                            <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">
                                                <div>
                                                    <div class="fw-bold"><?php echo htmlspecialchars($prayer['name']); ?></div>
                                                    <div class="small text-muted"><?php echo htmlspecialchars($prayer['status']); ?></div>
                                                    <p class="mb-0 mt-2"><?php echo htmlspecialchars($prayer['request']); ?></p>
                                                </div>
                                                <form method="post" class="m-0">
                                                    <input type="hidden" name="delete_prayer_id" value="<?php echo (int)$prayer['id']; ?>">
                                                    <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('Remove this prayer request?');">Delete</button>
                                                </form>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div id="settings" data-admin-panel="settings" class="col-12 admin-section-panel d-none">
                        <div class="card admin-action-card border-0 h-100 p-4">
                            <div class="d-flex align-items-start justify-content-between mb-4 flex-wrap gap-2">
                                <div>
                                    <h2 class="card-title mb-2">Settings</h2>
                                    <p class="text-muted mb-0">Manage the website's key site information from the database.</p>
                                </div>
                                <span class="badge bg-primary">Database</span>
                            </div>

                            <?php if ($settingsMessage): ?>
                                <div class="alert alert-success rounded-4 mb-3"><?php echo htmlspecialchars($settingsMessage); ?></div>
                            <?php endif; ?>

                            <?php if ($settingsError): ?>
                                <div class="alert alert-danger rounded-4 mb-3"><?php echo htmlspecialchars($settingsError); ?></div>
                            <?php endif; ?>

                            <form method="post" enctype="multipart/form-data" class="row g-3">
                                <input type="hidden" name="admin_settings_form" value="1">
                                <div class="col-md-6">
                                    <label class="form-label" for="site_name">Site Name</label>
                                    <input type="text" class="form-control" id="site_name" name="site_name" value="<?php echo htmlspecialchars($settingsMap['site_name'] ?? APP_NAME); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="site_tagline">Tagline</label>
                                    <input type="text" class="form-control" id="site_tagline" name="site_tagline" value="<?php echo htmlspecialchars($settingsMap['site_tagline'] ?? 'United by love, connected by faith.'); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="contact_email">Contact Email</label>
                                    <input type="email" class="form-control" id="contact_email" name="contact_email" value="<?php echo htmlspecialchars($settingsMap['contact_email'] ?? 'family@daebatch.org'); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="contact_phone">Contact Phone</label>
                                    <input type="text" class="form-control" id="contact_phone" name="contact_phone" value="<?php echo htmlspecialchars($settingsMap['contact_phone'] ?? '+1 (555) 123-4567'); ?>" required>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label" for="contact_address">Address</label>
                                    <textarea class="form-control" id="contact_address" name="contact_address" rows="3" required><?php echo htmlspecialchars($settingsMap['contact_address'] ?? '123 Family Lane, Home City'); ?></textarea>
                                </div>
                                <div class="col-12">
                                    <div class="settings-verse-highlight rounded-4 p-4 mb-2">
                                        <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap mb-3">
                                            <div>
                                                <h3 class="h5 mb-1">Daily Verse Spotlight</h3>
                                                <p class="text-muted mb-0">Set an uplifting scripture for the prayer page and family encouragement section.</p>
                                            </div>
                                            <span class="badge bg-warning text-dark rounded-pill px-3 py-2">Prayer Page</span>
                                        </div>
                                        <div class="row g-3 align-items-end">
                                            <div class="col-md-4">
                                                <label class="form-label" for="daily_verse_date">Daily Verse Date</label>
                                                <input type="date" class="form-control" id="daily_verse_date" name="daily_verse_date" value="<?php echo htmlspecialchars(date('Y-m-d')); ?>">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label" for="daily_verse_text">Daily Verse Text</label>
                                                <textarea class="form-control" id="daily_verse_text" name="daily_verse_text" rows="3" placeholder="Enter the verse text" required></textarea>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label" for="daily_verse_reference">Daily Verse Reference</label>
                                                <input type="text" class="form-control" id="daily_verse_reference" name="daily_verse_reference" placeholder="Example: Psalm 23:1" required>
                                            </div>
                                            <div class="col-md-4">
                                                <button type="submit" class="btn btn-settings-accent w-100">Save Daily Verse</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="settings-memorial-highlight rounded-4 p-4 mb-2">
                                        <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap mb-3">
                                            <div>
                                                <h3 class="h5 mb-1">Hanna Memorial Resources</h3>
                                                <p class="text-muted mb-0">Upload or replace the three permanent memorial photos. You can also upload audio, PDF, and PowerPoint files for the tribute section.</p>
                                            </div>
                                            <span class="badge bg-primary rounded-pill px-3 py-2">Admin Managed</span>
                                        </div>
                                        <div class="row g-3 align-items-end">
                                            <div class="col-md-4">
                                                <label class="form-label" for="memorial_photo_1_file">Memorial Photo 1</label>
                                                <input type="file" class="form-control" id="memorial_photo_1_file" name="memorial_photo_1_file" accept=".jpg,.jpeg,.png,.gif,.webp">
                                                <div class="form-check mt-2">
                                                    <input class="form-check-input" type="checkbox" id="delete_memorial_photo_1" name="delete_memorial_photo_1" value="1">
                                                    <label class="form-check-label" for="delete_memorial_photo_1">Remove current photo 1</label>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label" for="memorial_photo_2_file">Memorial Photo 2</label>
                                                <input type="file" class="form-control" id="memorial_photo_2_file" name="memorial_photo_2_file" accept=".jpg,.jpeg,.png,.gif,.webp">
                                                <div class="form-check mt-2">
                                                    <input class="form-check-input" type="checkbox" id="delete_memorial_photo_2" name="delete_memorial_photo_2" value="1">
                                                    <label class="form-check-label" for="delete_memorial_photo_2">Remove current photo 2</label>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label" for="memorial_photo_3_file">Memorial Photo 3</label>
                                                <input type="file" class="form-control" id="memorial_photo_3_file" name="memorial_photo_3_file" accept=".jpg,.jpeg,.png,.gif,.webp">
                                                <div class="form-check mt-2">
                                                    <input class="form-check-input" type="checkbox" id="delete_memorial_photo_3" name="delete_memorial_photo_3" value="1">
                                                    <label class="form-check-label" for="delete_memorial_photo_3">Remove current photo 3</label>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label" for="memorial_audio_file">Memorial Audio</label>
                                                <input type="file" class="form-control" id="memorial_audio_file" name="memorial_audio_file" accept=".mp3,.wav,.ogg,.m4a">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label" for="memorial_pdf_file">Memorial PDF</label>
                                                <input type="file" class="form-control" id="memorial_pdf_file" name="memorial_pdf_file" accept=".pdf">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label" for="memorial_ppt_file">Memorial PowerPoint</label>
                                                <input type="file" class="form-control" id="memorial_ppt_file" name="memorial_ppt_file" accept=".ppt,.pptx">
                                            </div>
                                            <div class="col-12 d-flex justify-content-end">
                                                <button type="submit" class="btn btn-primary" formnovalidate>Upload Memorial Resources</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="about_download_title">About Download Title</label>
                                    <input type="text" class="form-control" id="about_download_title" name="about_download_title" value="<?php echo htmlspecialchars($settingsMap['about_download_title'] ?? 'Dambii Ittin Bulmaataa'); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="about_download_subtitle">About Download Subtitle</label>
                                    <input type="text" class="form-control" id="about_download_subtitle" name="about_download_subtitle" value="<?php echo htmlspecialchars($settingsMap['about_download_subtitle'] ?? 'Group 2014 Batch'); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="about_download_description">About Download Description</label>
                                    <input type="text" class="form-control" id="about_download_description" name="about_download_description" value="<?php echo htmlspecialchars($settingsMap['about_download_description'] ?? 'Dambii fi qajeelcha group keenya PDF, PowerPoint yookaan txt keessatti argachuu dandeessu.'); ?>">
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label" for="about_download_files">Upload one or more download files (PDF, PPT, Word, TXT)</label>
                                    <input type="file" class="form-control" id="about_download_files" name="about_download_files[]" accept=".pdf,.ppt,.pptx,.doc,.docx,.txt" multiple>
                                    <div class="form-text">Each uploaded file will be shown as a separate download option for visitors.</div>
                                </div>
                                <div class="col-md-12">
                                    <button type="submit" class="btn btn-primary">Save Settings</button>
                                </div>
                            </form>

                            <?php if ($downloadMessage): ?>
                                <div class="alert alert-success rounded-4 mt-3 mb-3"><?php echo htmlspecialchars($downloadMessage); ?></div>
                            <?php endif; ?>

                            <?php if (!empty($downloadFiles)): ?>
                                <div class="mt-3">
                                    <h4 class="h6 mb-3">Current downloadable files</h4>
                                    <div class="row g-3">
                                        <?php foreach ($downloadFiles as $downloadFile): ?>
                                            <div class="col-md-6 col-xl-4">
                                                <div class="border rounded-4 p-3 bg-light-subtle h-100">
                                                    <div class="d-flex justify-content-between align-items-start gap-2">
                                                        <div>
                                                            <div class="fw-bold"><?php echo htmlspecialchars($downloadFile['display_name']); ?></div>
                                                            <div class="small text-muted text-uppercase"><?php echo htmlspecialchars($downloadFile['file_type']); ?></div>
                                                        </div>
                                                        <form method="post" class="m-0">
                                                            <input type="hidden" name="delete_download_id" value="<?php echo (int)$downloadFile['id']; ?>">
                                                            <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('Remove this download file?');">Delete</button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <hr class="my-4">

                            <div class="d-flex align-items-start justify-content-between mb-3 flex-wrap gap-2">
                                <div>
                                    <h3 class="h5 mb-1">Get in Touch Leaders</h3>
                                    <p class="text-muted mb-0">Add one or more family leaders to the public contact page.</p>
                                </div>
                            </div>

                            <?php if ($leaderMessage): ?>
                                <div class="alert alert-success rounded-4 mb-3"><?php echo htmlspecialchars($leaderMessage); ?></div>
                            <?php endif; ?>

                            <?php if ($leaderError): ?>
                                <div class="alert alert-danger rounded-4 mb-3"><?php echo htmlspecialchars($leaderError); ?></div>
                            <?php endif; ?>

                            <form method="post" enctype="multipart/form-data" class="row g-3 align-items-end mb-4">
                                <input type="hidden" name="contact_leader_form" value="1">
                                <div class="col-md-3">
                                    <label class="form-label" for="leader_name">Leader Name</label>
                                    <input type="text" class="form-control" id="leader_name" name="leader_name" placeholder="Leader full name" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label" for="leader_role">Role</label>
                                    <input type="text" class="form-control" id="leader_role" name="leader_role" placeholder="Example: Chairperson" required>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label" for="leader_telegram">Telegram</label>
                                    <input type="text" class="form-control" id="leader_telegram" name="leader_telegram" placeholder="@username">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label" for="leader_phone">Phone</label>
                                    <input type="text" class="form-control" id="leader_phone" name="leader_phone" placeholder="+1 ..." required>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label" for="leader_photo">Image</label>
                                    <input type="file" class="form-control" id="leader_photo" name="leader_photo" accept=".jpg,.jpeg,.png,.gif,.webp">
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary">Add Leader</button>
                                </div>
                            </form>

                            <?php if (empty($contactLeaders)): ?>
                                <div class="alert alert-info rounded-4 mb-0">No leaders have been added yet.</div>
                            <?php else: ?>
                                <div class="row g-3">
                                    <?php foreach ($contactLeaders as $leader): ?>
                                        <div class="col-md-6 col-xl-4">
                                            <div class="border rounded-4 p-3 bg-light-subtle h-100">
                                                <div class="d-flex align-items-start justify-content-between gap-2">
                                                    <div>
                                                        <div class="fw-bold"><?php echo htmlspecialchars($leader['name']); ?></div>
                                                        <div class="small text-muted"><?php echo htmlspecialchars($leader['role']); ?></div>
                                                    </div>
                                                    <form method="post" class="m-0">
                                                        <input type="hidden" name="delete_leader_id" value="<?php echo (int)$leader['id']; ?>">
                                                        <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('Remove this leader?');">Delete</button>
                                                    </form>
                                                </div>
                                                <?php if (!empty($leader['photo'])): ?>
                                                    <img src="<?php echo htmlspecialchars(BASE_URL . 'assets/uploads/leaders/' . $leader['photo']); ?>" alt="<?php echo htmlspecialchars($leader['name']); ?>" class="leader-card-image rounded-3 mt-3">
                                                <?php endif; ?>
                                                <?php if (!empty($leader['telegram'])): ?>
                                                    <div class="small mt-3"><strong>Telegram:</strong> <?php echo htmlspecialchars($leader['telegram']); ?></div>
                                                <?php endif; ?>
                                                <?php if (!empty($leader['phone'])): ?>
                                                    <div class="small"><strong>Phone:</strong> <?php echo htmlspecialchars($leader['phone']); ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div id="users" data-admin-panel="users" class="col-lg-12 admin-section-panel d-none">
                        <div class="card admin-action-card border-0 h-100 p-4">
                            <div class="d-flex align-items-start justify-content-between mb-4 flex-wrap gap-3">
                                <div>
                                    <h2 class="card-title mb-2">Users</h2>
                                    <p class="text-muted mb-0">Registered users pulled directly from the database.</p>
                                </div>
                                <div class="d-flex gap-2">
                                    <a href="<?php echo $baseUrl; ?>auth/register.php" class="btn btn-primary">Add New User</a>
                                    <span class="badge bg-primary">Database</span>
                                </div>
                            </div>

                            <?php if ($userMessage): ?>
                                <div class="alert alert-success rounded-4 mb-3"><?php echo htmlspecialchars($userMessage); ?></div>
                            <?php endif; ?>

                            <?php if ($userError): ?>
                                <div class="alert alert-danger rounded-4 mb-3"><?php echo htmlspecialchars($userError); ?></div>
                            <?php endif; ?>

                            <?php if (empty($usersList)): ?>
                                <div class="alert alert-info rounded-4 mb-0">No registered users found in the database.</div>
                            <?php else: ?>
                                <div class="row g-3">
                                    <?php foreach ($usersList as $registeredUser): ?>
                                        <div class="col-md-6 col-xl-4">
                                            <div class="border rounded-4 p-3 h-100 bg-light-subtle">
                                                <div class="d-flex align-items-center gap-3 mb-3">
                                                    <img src="<?php echo htmlspecialchars(!empty($registeredUser['photo']) ? member_photo_url($registeredUser['photo']) : 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?auto=format&fit=crop&w=600&q=80'); ?>"
                                                        alt="<?php echo htmlspecialchars($registeredUser['fullname']); ?>"
                                                        class="rounded-circle border border-2 border-warning"
                                                        style="width: 64px; height: 64px; object-fit: cover;">
                                                    <div>
                                                        <div class="fw-bold"><?php echo htmlspecialchars($registeredUser['fullname']); ?></div>
                                                        <div class="small text-muted"><?php echo htmlspecialchars($registeredUser['email']); ?></div>
                                                    </div>
                                                </div>
                                                <div class="d-flex justify-content-between align-items-center gap-2">
                                                    <span class="badge <?php echo (int)$registeredUser['role_id'] === 1 ? 'bg-primary' : 'bg-secondary'; ?>"><?php echo (int)$registeredUser['role_id'] === 1 ? 'Admin' : 'User'; ?></span>
                                                    <form method="post" class="m-0">
                                                        <input type="hidden" name="delete_user_id" value="<?php echo (int)$registeredUser['id']; ?>">
                                                        <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('Remove this user from the database?');">Delete</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-lg-12">
                        <div class="card admin-activity-card border-0 h-100 p-4">
                            <div class="d-flex align-items-start justify-content-between mb-4 flex-wrap gap-3">
                                <div>
                                    <h2 class="card-title mb-2">Site overview</h2>
                                    <p class="text-muted mb-0">Your current admin snapshot.</p>
                                </div>
                                <span class="badge bg-success rounded-pill px-3 py-2">Healthy</span>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6 col-xl-3">
                                    <div class="overview-item p-3 rounded-4">
                                        <div class="overview-label">Members</div>
                                        <div class="overview-value"><?php echo $stats['members']; ?></div>
                                        <div class="overview-text">family members in the system</div>
                                    </div>
                                </div>
                                <div class="col-md-6 col-xl-3">
                                    <div class="overview-item p-3 rounded-4">
                                        <div class="overview-label">Events</div>
                                        <div class="overview-value"><?php echo $stats['events']; ?></div>
                                        <div class="overview-text">events created</div>
                                    </div>
                                </div>
                                <div class="col-md-6 col-xl-3">
                                    <div class="overview-item p-3 rounded-4">
                                        <div class="overview-label">Media</div>
                                        <div class="overview-value"><?php echo $stats['media_items']; ?></div>
                                        <div class="overview-text">uploaded media items</div>
                                    </div>
                                </div>
                                <div class="col-md-6 col-xl-3">
                                    <div class="overview-item p-3 rounded-4">
                                        <div class="overview-label">Users</div>
                                        <div class="overview-value"><?php echo $stats['users']; ?></div>
                                        <div class="overview-text">registered users</div>
                                    </div>
                                </div>
                            </div>

                            <?php if (!empty($stats['error'])): ?>
                                <div class="alert alert-warning mt-4 mb-0" role="alert">
                                    Unable to read live stats: <?php echo htmlspecialchars($stats['error']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php
require_once __DIR__ . '/../includes/footer.php';
require_once __DIR__ . '/../includes/scripts.php';
?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const navLinks = document.querySelectorAll('.admin-menu-link');
        const panels = document.querySelectorAll('[data-admin-panel]');

        function activatePanel(targetId) {
            navLinks.forEach(function(item) {
                const isActive = item.getAttribute('data-admin-target') === targetId;
                item.classList.toggle('active', isActive);
            });

            panels.forEach(function(panel) {
                const shouldShow = panel.getAttribute('data-admin-panel') === targetId;
                panel.classList.toggle('d-none', !shouldShow);
            });

            const targetPanel = document.getElementById(targetId);
            if (targetPanel) {
                targetPanel.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }

            if (targetId) {
                history.replaceState(null, '', '#' + targetId);
            }
        }

        const initialTarget = window.location.hash.replace('#', '') || 'control-center';
        activatePanel(initialTarget);

        navLinks.forEach(function(link) {
            link.addEventListener('click', function(event) {
                event.preventDefault();

                const targetId = this.getAttribute('data-admin-target');
                if (!targetId) {
                    return;
                }

                activatePanel(targetId);
            });
        });
    });
</script>