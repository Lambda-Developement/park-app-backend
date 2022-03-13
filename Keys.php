<?php
require_once 'Exceptions.php';
require_once 'Database.php';

class Keys {
    private static function generate(): string {
        $db = new Database();
        do {
            $g = bin2hex(random_bytes(25));
            $r = $db->getLoginKeyUsage($g);
        } while ($r != 0);
        return $g;
    }
    public static function assign(User|int $user): string {
        $db = new Database();
        if ($user instanceof User) $user = $user->id;
        $key = self::generate();
        $db->assignKeyToUserID($key, $user);
        return $key;
    }
}