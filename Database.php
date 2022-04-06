<?php
require_once 'DatabaseInterface.php';
require_once 'Config.php';
require_once 'Exceptions.php';

class Database extends mysqli implements DatabaseInterface {
    function __construct() {
        parent::__construct(Config::DB_URL, Config::DB_USER, Config::DB_PASS, Config::DB)
            or throw new DatabaseException("Unable to connect!");
    }
    public function getUserByLogin(string $login): array {
        $prep = self::prepare("SELECT * FROM users WHERE login = ?");
        $prep->bind_param('s', $login);
        $prep->execute();
        $f = $prep->get_result();
        $prep->close();
        if ($f->num_rows != 1) throw new DatabaseException("User does not exists!");
        return $f->fetch_assoc();
    }
    public function getUserByKey(string $key): array {
        $prep = self::prepare("SELECT * FROM users WHERE loginkey = ?");
        $prep->bind_param('s', $key);
        $prep->execute();
        $f = $prep->get_result();
        if ($f->num_rows != 1) throw new DatabaseException("User does not exists!");
        return $f->fetch_assoc();
    }
    public function getLoginKeyUsage(string $key): int {
        $prep = self::prepare("SELECT id FROM users WHERE loginkey = ?");
        $prep->bind_param('s', $key);
        $prep->execute();
        return $prep->get_result()->num_rows;
    }
    public function assignKeyToUserID(string $key, int $user_id): void {
        $prep = self::prepare("UPDATE users SET loginkey = ? WHERE id = ?");
        $prep->bind_param('ss', $key, $user_id);
        $prep->execute();
    }
    public function insertUser(string $email, string $name, string $hash): void {
        try {
            $this->getUserByLogin($email);
            throw new UserAlreadyRegisteredException();
        } catch (DatabaseException $e) {
            $prep = self::prepare("INSERT INTO users(login, name, hash) VALUES (?, ?, ?)");
            $prep->bind_param('sss', $email, $name, $hash);
            $prep->execute() or die($prep->error);
            $prep->close();
        }
    }
    public function setUserPassword(string $email, string $hash): void {
        try {
            $this->getUserByLogin($email);
        } catch (DatabaseException $e) {
            throw new UserNotFoundException(previous: $e);
        }
        $prep = self::prepare("UPDATE users SET hash = ? WHERE login = ?");
        $prep->bind_param('ss', $hash, $email);
        $prep->execute();
        $prep->close();
    }
    public function getData(): array {
        $q = self::query("SELECT lat, lon, occupied, addr FROM parkings");
        return $q->fetch_all();
    }
    public function insertErrorMessage(string $message, User $sender): void {
        $senderid = $sender->id;
        $prep = self::prepare("INSERT INTO error_messages(senderid, text) VALUES (?, ?)");
        $prep->bind_param('is', $senderid, $message);
        $prep->execute();
        $prep->close();
    }
    function __destruct() {
        self::close();
    }
}