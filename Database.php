<?php
require_once 'Config.php';
require_once 'Exceptions.php';

class Database extends mysqli {
    function __construct() {
        parent::__construct(Config::DB_URL, Config::DB_USER, Config::DB_PASS, Config::DB)
            or throw new DatabaseException("Unable to connect!");
    }
    public function getUserByLogin(string $login): array {
        $f = self::query("SELECT * FROM users WHERE login = '$login'");
        if ($f->num_rows != 1) throw new DatabaseException("User does not exists!");
        return $f->fetch_assoc();
    }
    public function getUserByKey(string $key): array {
        $f = self::query("SELECT * FROM users WHERE loginkey = '$key'");
        if ($f->num_rows != 1) throw new DatabaseException("User does not exists!");
        return $f->fetch_assoc();
    }
    public function getLoginKeyUsage(string $key): int {
        return self::query("SELECT id FROM users WHERE loginkey = '$key'")->num_rows;
    }
    public function assignKeyToUserID(string $key, int $user_id): void {
        self::query("UPDATE users SET loginkey = '$key' WHERE id = '$user_id'");
    }
}