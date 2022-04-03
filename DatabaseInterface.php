<?php
interface DatabaseInterface {
    function __construct();
    public function getUserByLogin(string $login): array;
    public function getUserByKey(string $key): array;
    public function getLoginKeyUsage(string $key): int;
    public function assignKeyToUserID(string $key, int $user_id): void;
    public function insertUser(string $email, string $name, string $hash): void;
    public function setUserPassword(string $email, string $hash): void;
    public function getData(): array;
    public function insertErrorMessage(string $message, User $sender): void;
    function __destruct();
}