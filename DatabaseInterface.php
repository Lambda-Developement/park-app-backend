<?php
interface DatabaseInterface {
    function __construct();
    public function getUserByLogin(string $login): array;
    public function getUserByKey(string $key): array;
    public function getLoginKeyUsage(string $key): int;
    public function assignKeyToUserID(string $key, int $user_id): void;
    function __destruct();
}