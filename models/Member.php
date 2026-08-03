<?php
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/../helpers/functions.php';

class Member
{
    public static function create(array $data): bool
    {
        $db = Database::connect();
        $stmt = $db->prepare(
            'INSERT INTO members (user_id, slug, name, email, role, photo, university, department, age, year, family_members, social_links, bio, created_at) VALUES (:user_id, :slug, :name, :email, :role, :photo, :university, :department, :age, :year, :family_members, :social_links, :bio, NOW())'
        );

        return $stmt->execute([
            'user_id' => $data['user_id'] ?? null,
            'slug' => $data['slug'],
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'] ?? null,
            'photo' => $data['photo'] ?? null,
            'university' => $data['university'] ?? null,
            'department' => $data['department'] ?? null,
            'age' => $data['age'] !== '' ? (int)$data['age'] : null,
            'year' => $data['year'] ?? null,
            'family_members' => $data['family_members'] ?? null,
            'social_links' => $data['social_links'] ?? null,
            'bio' => $data['bio'] ?? null,
        ]);
    }

    public static function getAll(): array
    {
        try {
            $db = Database::connect();
            $stmt = $db->query('SELECT * FROM members ORDER BY created_at DESC');
            $members = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($members)) {
                return array_map(self::hydrateMember(...), $members);
            }
        } catch (PDOException $e) {
            // Fall back to sample members if the database is unavailable.
        }

        return self::getSampleMembers();
    }

    public static function findBySlug(string $slug): ?array
    {
        try {
            $db = Database::connect();
            $stmt = $db->prepare('SELECT * FROM members WHERE slug = :slug LIMIT 1');
            $stmt->execute(['slug' => $slug]);
            $member = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($member) {
                return self::hydrateMember($member);
            }
        } catch (PDOException $e) {
            // If the database is unavailable, fall back to sample members.
        }

        foreach (self::getSampleMembers() as $member) {
            if ($member['slug'] === $slug) {
                return $member;
            }
        }

        return null;
    }

    private static function hydrateMember(array $member): array
    {
        $member['socials'] = [];
        if (!empty($member['social_links'])) {
            $decoded = json_decode($member['social_links'], true);
            if (is_array($decoded)) {
                $member['socials'] = $decoded;
            }
        }

        $member['bio'] = $member['bio'] ?? 'This member is a proud part of our family community.';
        $member['image'] = $member['photo'] ? member_photo_url($member['photo']) : 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?auto=format&fit=crop&w=600&q=80';
        $member['role'] = $member['role'] ?? 'Family Member';

        return $member;
    }

    public static function generateSlug(string $name): string
    {
        $slug = preg_replace('/[^a-z0-9]+/i', '-', trim($name));
        $slug = strtolower(trim($slug, '-'));
        if ($slug === '') {
            $slug = 'member-' . uniqid();
        }
        return $slug . '-' . substr(uniqid('', true), -8);
    }

    public static function getSampleMembers(): array
    {
        return [
            [
                'slug' => 'sarah-dae',
                'name' => 'Sarah Dae',
                'role' => 'Family Matriarch',
                'image' => 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?auto=format&fit=crop&w=600&q=80',
                'university' => 'Addis Ababa University',
                'department' => 'Home & Family Leadership',
                'year' => 'Alumni',
                'age' => 46,
                'email' => 'sarah@daebatchfamily.org',
                'family_members' => 'Father, Mother, Two children',
                'bio' => 'Sarah leads the family with wisdom and warmth.',
                'socials' => [
                    ['label' => 'Instagram', 'url' => 'https://instagram.com/sarah'],
                    ['label' => 'Facebook', 'url' => 'https://facebook.com/sarah'],
                ],
            ],
            [
                'slug' => 'joseph-dae',
                'name' => 'Joseph Dae',
                'role' => 'Family Planner',
                'image' => 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=600&q=80',
                'university' => 'Jimma University',
                'department' => 'Business Administration',
                'year' => '4th Year',
                'age' => 29,
                'email' => 'joseph@daebatchfamily.org',
                'family_members' => 'Wife, One child',
                'bio' => 'Joseph supports family events and planning with care.',
                'socials' => [
                    ['label' => 'LinkedIn', 'url' => 'https://linkedin.com/in/joseph'],
                    ['label' => 'Facebook', 'url' => 'https://facebook.com/joseph'],
                ],
            ],
        ];
    }
}
