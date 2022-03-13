<?php
interface DatabaseInterface {
    public function getUserByLogin(string $login);
    public function getUserByKey(string $key);
    public function getLoginKeyUsage(string $key);
    public function assignKeyToUserID(string $key, int $user_id);
}