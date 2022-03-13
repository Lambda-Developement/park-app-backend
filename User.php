<?php

class User {
    public readonly int $id;
    function __construct(string $key) {
        $db = new Database();
        try {
            $data = $db->getUserByKey($key);
        } catch (DatabaseException $e) {
            throw new DatabaseException($e->getMessage(), $e->getCode(), $e);
        }
        $this->id = $data['id'];
    }
}
