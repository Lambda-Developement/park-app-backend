<?php
require_once 'DatabaseInterface.php';
require_once 'Config.php';
require_once 'Exceptions.php';

class Database extends mysqli implements DatabaseInterface {
    function __construct() {
        parent::__construct(Config::DB_URL, Config::DB_USER, Config::DB_PASS, Config::DB)
            or throw new DatabaseException("Unable to connect!");
    }
    private function fastPrepare(string $query, string $types, &...$vars): mysqli_result|false {
        if (count($vars) < 1) throw new InvalidArgumentException("Incorrect variable number!");
        elseif (strlen($types) != count($vars)) throw new UnexpectedValueException("Types and vars count mismatch!");
        $prep = self::prepare($query);
        $prep->bind_param($types, ...$vars);
        $prep->execute();
        $r = $prep->get_result();
        if ($prep->errno != 0) throw new DatabaseException($prep->error);
        $prep->close();
        return $r;
    }
    public function getUserByLogin(string $login): array {
        $f = self::fastPrepare("SELECT * FROM users WHERE login = ?", 's', $login);
        if ($f->num_rows != 1) throw new DatabaseException("User does not exists!");
        return $f->fetch_assoc();
    }
    public function getUserByKey(string $key): array {
        $f = self::fastPrepare("SELECT * FROM users WHERE loginkey = ?", 's', $key);
        if ($f->num_rows != 1) throw new DatabaseException("User does not exists!");
        return $f->fetch_assoc();
    }
    public function getUserByID(int $id): array {
        $f = self::fastPrepare("SELECT * FROM users WHERE id = ?", 'i', $id);
        if ($f->num_rows != 1) throw new DatabaseException("User does not exists");
        return $f->fetch_assoc();
    }
    public function getUserByConfKey(string $key): array {
        $f = self::fastPrepare("SELECT * FROM users WHERE confkey = ?", 's', $key);
        if ($f->num_rows != 1) throw new DatabaseException("User does not exists!");
        return $f->fetch_assoc();
    }
    public function getUserByRemindKey(string $key): array {
        $f = self::fastPrepare("SELECT * FROM users WHERE remindkey = ?", 's', $key);
        if ($f->num_rows != 1) throw new DatabaseException("User does not exists!");
        return $f->fetch_assoc();
    }
    public function getLoginKeyUsage(string $key): int {
        return self::fastPrepare("SELECT id FROM users WHERE loginkey = ?", 's', $key)->num_rows;
    }
    public function getConfirmationKeyUsage(string $key): int {
        return self::fastPrepare("SELECT id FROM users WHERE confkey = ?", 's', $key)->num_rows;
    }
    public function getRemindKeyUsage(string $key): int {
        return self::fastPrepare("SELECT id FROM users WHERE remindkey = ?", 's', $key)->num_rows;
    }
    public function assignKeyToUserID(string $key, int $user_id): void {
        self::fastPrepare("UPDATE users SET loginkey = ? WHERE id = ?", 'si', $key, $user_id);
    }
    public function assignRemindKeyToUserID(string $key, int $user_id): void {
        self::fastPrepare("UPDATE users SET remindkey = ? WHERE id = ?", 'si', $key, $user_id);
    }
    public function insertUser(string $email, string $name, string $hash, string $mail_conf): void {
        try {
            $this->getUserByLogin($email);
            throw new UserAlreadyRegisteredException();
        } catch (DatabaseException $e) {
            self::fastPrepare("INSERT INTO users(login, name, hash, confkey) VALUES (?, ?, ?, ?)", 'ssss', $email, $name, $hash, $mail_conf);
        }
    }
    public function arrayUpdateUser(int $user_id, array $array): void {
        foreach ($array as $key => $value) {
            self::fastPrepare("UPDATE users SET `$key` = ? WHERE id = ?", 'si', $value, $user_id);
        }
    }
    public function updateAvatarLocation(int $user_id, string $new_location): void {
        self::fastPrepare("UPDATE users SET avatarloc = ? WHERE id = ?", 'si', $new_location, $user_id);
    }
    public function setUserPassword(string $email, string $hash): void {
        try {
            $this->getUserByLogin($email);
        } catch (DatabaseException $e) {
            throw new UserNotFoundException(previous: $e);
        }
        self::fastPrepare("UPDATE users SET hash = ?, remindkey = NULL WHERE login = ?", 'ss', $hash, $email);
    }
    public function getData(): array {
        $q = self::query("SELECT lat, lon, occupied, addr FROM parkings");
        return $q->fetch_all();
    }
    public function insertErrorMessage(string $message, User $sender): void {
        $senderid = $sender->id;
        self::fastPrepare("INSERT INTO error_messages(senderid, text) VALUES (?, ?)", 'is', $senderid, $message);
    }
    public function insertReview(User $author, int $park_id, int $mark, ?string $review): void {
        $pnr = self::fastPrepare("SELECT id FROM parkings WHERE id = ?", 'i', $park_id)->num_rows;
        if ($pnr != 1) throw new ElementNotFoundException();
        $author_id = $author->id;
        self::fastPrepare("INSERT INTO reviews(author, park_id, mark, review) VALUES (?, ?, ?, ?)", 'iiis', $author_id, $park_id, $mark, $review);
    }
    public function getReviews(int $park_id): array {
        $pnr = self::fastPrepare("SELECT id FROM parkings WHERE id = ?", 'i', $park_id)->num_rows;
        if ($pnr != 1) throw new ElementNotFoundException();
        return self::fastPrepare("SELECT author, mark, review FROM reviews WHERE park_id = ?", 'i', $park_id)->fetch_all();
    }
    public function activateUser(int $user_id): void {
        self::fastPrepare("UPDATE users SET confkey = NULL, confirmed = 1 WHERE id = ?", 'i', $user_id);
    }
    function __destruct() {
        self::close();
    }
}