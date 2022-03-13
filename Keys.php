<?php
require_once 'Exceptions.php';
require_once 'Database.php';

class Keys {
    /**
     * @throws KeyGeneratorException If unable to use random_bytes function
     */
    private static function generate(): string {
        $db = new Database();
        do {
            try {
                $g = bin2hex(random_bytes(25));
            } catch (Exception) {
                throw new KeyGeneratorException("Unable to generate random bytes!");
            }
            $r = $db->getLoginKeyUsage($g);
        } while ($r != 0);
        return $g;
    }
    /**
     * @throws KeyGeneratorException If unable to use random_bytes function
     */
    public static function assign(User|int $user): string {
        $db = new Database();
        if ($user instanceof User) $user = $user->id;
        try {
            $key = self::generate();
        } catch (KeyGeneratorException $e) {
            throw new KeyGeneratorException($e->getMessage(), $e->getCode(), $e);
        }
        $db->assignKeyToUserID($key, $user);
        return $key;
    }
}