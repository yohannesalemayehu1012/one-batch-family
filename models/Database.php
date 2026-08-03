<?php
class Database
{
    public static function connect(): PDO
    {
        $config = require __DIR__ . '/../config/database.php';

        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', $config['host'], $config['database'], $config['charset']);
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            $pdo = new PDO($dsn, $config['username'], $config['password'], $options);
        } catch (PDOException $e) {
            if ($e->getCode() === '1049') {
                $pdo = new PDO(
                    sprintf('mysql:host=%s;charset=%s', $config['host'], $config['charset']),
                    $config['username'],
                    $config['password'],
                    $options
                );
                $pdo->exec(sprintf(
                    'CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET %s COLLATE %s',
                    $config['database'],
                    $config['charset'],
                    'utf8mb4_unicode_ci'
                ));
                $pdo = new PDO($dsn, $config['username'], $config['password'], $options);
            } else {
                throw $e;
            }
        }

        self::initializeSchema($pdo);
        return $pdo;
    }

    private static function initializeSchema(PDO $pdo): void
    {
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                fullname VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                role_id INT DEFAULT 2,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );

        self::seedAdmin($pdo);

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS members (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NULL,
                slug VARCHAR(255) NOT NULL,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL,
                role VARCHAR(255),
                photo VARCHAR(255),
                university VARCHAR(255) NULL,
                department VARCHAR(255) NULL,
                age INT NULL,
                year VARCHAR(100) NULL,
                family_members TEXT NULL,
                social_links TEXT NULL,
                bio TEXT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );

        foreach (
            [
                'user_id' => 'INT NULL',
                'slug' => 'VARCHAR(255) NOT NULL',
                'email' => 'VARCHAR(255) NOT NULL',
                'university' => 'VARCHAR(255) NULL',
                'department' => 'VARCHAR(255) NULL',
                'age' => 'INT NULL',
                'year' => 'VARCHAR(100) NULL',
                'family_members' => 'TEXT NULL',
                'social_links' => 'TEXT NULL',
                'bio' => 'TEXT NULL',
            ] as $column => $definition
        ) {
            if (!self::columnExists($pdo, 'members', $column)) {
                $pdo->exec(sprintf('ALTER TABLE members ADD COLUMN %s %s', $column, $definition));
            }
        }

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS events (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                description TEXT,
                event_date DATE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS media_items (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                year INT NOT NULL,
                type ENUM("photo","video","audio") NOT NULL,
                filename VARCHAR(255) NOT NULL,
                user_id INT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );

        if (!self::columnExists($pdo, 'media_items', 'user_id')) {
            $pdo->exec('ALTER TABLE media_items ADD COLUMN user_id INT NULL');
        }

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS prayer_requests (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                request TEXT NOT NULL,
                status VARCHAR(50) NOT NULL DEFAULT "pending",
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS daily_verses (
                id INT AUTO_INCREMENT PRIMARY KEY,
                verse_text TEXT NOT NULL,
                reference VARCHAR(255) NOT NULL,
                verse_date DATE NOT NULL UNIQUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS contact_messages (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL,
                message TEXT NOT NULL,
                status VARCHAR(50) NOT NULL DEFAULT "new",
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS site_settings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                setting_key VARCHAR(100) NOT NULL UNIQUE,
                setting_value TEXT NULL,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS contact_leaders (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                role VARCHAR(255) NOT NULL,
                telegram VARCHAR(255) NULL,
                phone VARCHAR(255) NULL,
                photo VARCHAR(255) NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS about_download_files (
                id INT AUTO_INCREMENT PRIMARY KEY,
                display_name VARCHAR(255) NOT NULL,
                file_type ENUM("pdf", "ppt", "word", "txt") NOT NULL,
                stored_filename VARCHAR(255) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );

        self::seedSiteSettings($pdo);
    }

    private static function columnExists(PDO $pdo, string $table, string $column): bool
    {
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND COLUMN_NAME = :column'
        );
        $stmt->execute([
            'table' => $table,
            'column' => $column,
        ]);

        return (int)$stmt->fetchColumn() > 0;
    }

    private static function seedAdmin(PDO $pdo): void
    {
        $email = 'maamiyee1210@gmail.com';
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = :email');
        $stmt->execute(['email' => $email]);

        if ((int)$stmt->fetchColumn() === 0) {
            $hash = password_hash('Kenna1012', PASSWORD_DEFAULT);
            $insert = $pdo->prepare('INSERT INTO users (fullname, email, password, role_id, created_at) VALUES (:fullname, :email, :password, 1, NOW())');
            $insert->execute([
                'fullname' => 'Yohannes Alemayehu',
                'email' => $email,
                'password' => $hash,
            ]);
        }
    }

    private static function seedSiteSettings(PDO $pdo): void
    {
        $defaults = [
            'site_name' => 'One Batch Family',
            'site_tagline' => 'United by love, connected by faith.',
            'contact_email' => 'family@daebatch.org',
            'contact_phone' => '+1 (555) 123-4567',
            'contact_address' => '123 Family Lane, Home City',
        ];

        foreach ($defaults as $key => $value) {
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM site_settings WHERE setting_key = :key');
            $stmt->execute(['key' => $key]);

            if ((int)$stmt->fetchColumn() === 0) {
                $insert = $pdo->prepare('INSERT INTO site_settings (setting_key, setting_value) VALUES (:key, :value)');
                $insert->execute(['key' => $key, 'value' => $value]);
            }
        }
    }
}
