<?php
require_once 'Exceptions.php';
require_once 'Database.php';

class Keys {
    private static function generate(): string {
        $db = new Database();
        do {
            $g = bin2hex(random_bytes(25));
            $r = $db->query("SELECT id FROM users WHERE loginkey = '$g'")->num_rows;
        } while ($r != 0);
        return $g;
    }
    public static function assign(User|int $user): string {
        $db = new Database();
        if ($user instanceof User) $user = $user->id;
        $key = self::generate();
        $db->query("UPDATE users SET loginkey = '$key' WHERE id = '$user'");
        return $key;
    }
}