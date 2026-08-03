<?php
require_once __DIR__ . '/Database.php';

class Media
{
    private static function ensureSchema(): void
    {
        try {
            $db = Database::connect();
            $columnCheck = $db->query("SHOW COLUMNS FROM media_items LIKE 'orientation'");
            if ($columnCheck->rowCount() === 0) {
                $db->exec("ALTER TABLE media_items ADD COLUMN orientation ENUM('landscape', 'portrait') NOT NULL DEFAULT 'landscape' AFTER type");
            }
        } catch (PDOException $e) {
            // Ignore schema migration errors and continue with the existing table.
        }
    }

    public static function create(array $data): bool
    {
        self::ensureSchema();

        $db = Database::connect();
        $stmt = $db->prepare(
            'INSERT INTO media_items (title, year, type, orientation, filename, user_id, created_at) VALUES (:title, :year, :type, :orientation, :filename, :user_id, NOW())'
        );

        return $stmt->execute([
            'title' => $data['title'],
            'year' => $data['year'],
            'type' => $data['type'],
            'orientation' => in_array($data['orientation'] ?? 'landscape', ['landscape', 'portrait'], true) ? $data['orientation'] : 'landscape',
            'filename' => $data['filename'],
            'user_id' => $data['user_id'] ?? null,
        ]);
    }

    private static function createMediaSignature(array $item): string
    {
        $title = strtolower(trim((string) ($item['title'] ?? '')));
        $year = (int) ($item['year'] ?? 0);
        $type = strtolower(trim((string) ($item['type'] ?? '')));
        $orientation = strtolower(trim((string) ($item['orientation'] ?? 'landscape')));
        $filename = (string) ($item['filename'] ?? '');
        $size = 0;

        if ($filename !== '' && !filter_var($filename, FILTER_VALIDATE_URL)) {
            $folder = match ($type) {
                'photo' => 'photos',
                'video' => 'videos',
                'audio' => 'audio',
                default => '',
            };
            $filePath = __DIR__ . '/../assets/uploads/memories/' . $folder . '/' . $filename;
            if (is_file($filePath)) {
                $size = filesize($filePath);
            }
        }

        return implode('|', [$title, $year, $type, $orientation, $size]);
    }

    private static function dedupeItems(array $items): array
    {
        $seen = [];
        $uniqueItems = [];

        foreach ($items as $item) {
            $signature = self::createMediaSignature($item);
            if (!isset($seen[$signature])) {
                $seen[$signature] = true;
                $uniqueItems[] = $item;
            }
        }

        return $uniqueItems;
    }

    public static function getSampleItems(): array
    {
        return [
            [
                'title' => 'Family Reunion Picnic',
                'year' => 2023,
                'type' => 'photo',
                'filename' => 'https://images.unsplash.com/photo-1529156069898-49953e39b3ac?auto=format&fit=crop&w=1200&q=80',
                'uploader_name' => 'System',
            ],
            [
                'title' => 'Family Celebration Clip',
                'year' => 2022,
                'type' => 'video',
                'filename' => 'https://www.w3schools.com/html/mov_bbb.mp4',
                'uploader_name' => 'System',
            ],
            [
                'title' => 'Grandma Voice Note',
                'year' => 2021,
                'type' => 'audio',
                'filename' => 'https://www.w3schools.com/html/horse.mp3',
                'uploader_name' => 'System',
            ],
        ];
    }

    public static function getAll(): array
    {
        self::ensureSchema();

        try {
            $db = Database::connect();
            $stmt = $db->query(
                'SELECT media_items.*, users.fullname AS uploader_name
                 FROM media_items
                 LEFT JOIN users ON users.id = media_items.user_id
                 ORDER BY year DESC, created_at DESC'
            );
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($items)) {
                return self::dedupeItems($items);
            }
        } catch (PDOException $e) {
            // Fall back to sample content when the database is empty or unavailable.
        }

        return self::getSampleItems();
    }

    public static function getByUserId(int $userId): array
    {
        self::ensureSchema();

        try {
            $db = Database::connect();
            $stmt = $db->prepare(
                'SELECT media_items.*, users.fullname AS uploader_name
                 FROM media_items
                 LEFT JOIN users ON users.id = media_items.user_id
                 WHERE media_items.user_id = :user_id
                 ORDER BY year DESC, created_at DESC'
            );
            $stmt->execute(['user_id' => $userId]);
            return self::dedupeItems($stmt->fetchAll(PDO::FETCH_ASSOC));
        } catch (PDOException $e) {
            return [];
        }
    }

    public static function getByType(string $type): array
    {
        try {
            $db = Database::connect();
            $stmt = $db->prepare(
                'SELECT media_items.*, users.fullname AS uploader_name
                 FROM media_items
                 LEFT JOIN users ON users.id = media_items.user_id
                 WHERE type = :type
                 ORDER BY year DESC, created_at DESC'
            );
            $stmt->execute(['type' => $type]);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($items)) {
                return self::dedupeItems($items);
            }
        } catch (PDOException $e) {
            // Fall back to sample content when the database is empty or unavailable.
        }

        return array_values(array_filter(self::getSampleItems(), static fn($item): bool => $item['type'] === $type));
    }

    public static function getByYear(int $year): array
    {
        try {
            $db = Database::connect();
            $stmt = $db->prepare(
                'SELECT media_items.*, users.fullname AS uploader_name
                 FROM media_items
                 LEFT JOIN users ON users.id = media_items.user_id
                 WHERE year = :year
                 ORDER BY created_at DESC'
            );
            $stmt->execute(['year' => $year]);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($items)) {
                return self::dedupeItems($items);
            }
        } catch (PDOException $e) {
            // Fall back to sample content when the database is empty or unavailable.
        }

        return array_values(array_filter(self::getSampleItems(), static fn($item): bool => (int) $item['year'] === $year));
    }
}
