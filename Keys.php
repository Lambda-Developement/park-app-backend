<?php
require_once 'Exceptions.php';
require_once 'Database.php';

class Keys {
    /**
     * @throws KeyException If unable to use random_bytes function
     */
    private static function generate(): string {
        $db = new Database();
        do {
            try {
                $g = bin2hex(random_bytes(25));
            } catch (Exception) {
                throw new KeyException("Unable to generate random bytes!");
            }
            $r = $db->getLoginKeyUsage($g);
            $x = $db->getConfirmationKeyUsage($g);
            $y = $db->getRestoreKeyUsage($g);
            $t = $r + $x + $y;
        } while ($t != 0);
        return $g;
    }
    /**
     * @throws KeyException If unable to use random_bytes function
     */
    public static function generateKey(): string {
        try {
            $key = self::generate();
        } catch (KeyException $e) {
            throw new KeyException($e->getMessage(), $e->getCode(), $e);
        }
        return $key;
    }
    /**
     * @throws KeyException If unable to use random_bytes function
     */
    public static function assignLoginKey(User|int $user): string {
        $key = self::generateKey();
        if ($user instanceof User) $user = $user->id;
        (new Database())->assignKeyToUser($key, $user);
        return $key;
    }
    /**
     * @throws KeyException If unable to use random_bytes function
     */
    public static function assignRemindKey(User|int $user): string {
        $key = self::generateKey();
        if ($user instanceof User) $user = $user->id;
        (new Database())->assignRestoreKeyToUser($key, $user);
        return $key;
    }
}