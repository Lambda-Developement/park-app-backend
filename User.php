<?php
require_once 'Database.php';

class User {
    public readonly int $id;
    public readonly ?string $avatar_loc;
    public readonly string $name;
    public readonly int $gender;
    public readonly ?int $dob;
    public readonly string $login;
    public readonly ?int $phone;
    /*private ?string $hash;
    private ?string $key;*/

    function __construct(string $key) {
        try {
            $data = (new Database())->getUserByKey($key);
        } catch (DatabaseException $e) {
            throw new DatabaseException($e->getMessage(), $e->getCode(), $e);
        }
        $this->id = $data['id'];
        $this->avatar_loc = $data['avatarloc'];
        $this->name = $data['name'];
        $this->gender = $data['gender'];
        $this->dob = (!is_null($data['dob'])) ? strtotime($data['dob']) : NULL;
        $this->login = $data['login'];
        $this->phone = $data['phone'];
        /*$this->hash = $data['hash'];
        $this->key = $key;*/
    }
    public function getData(): array {
        return [$this->id, $this->avatar_loc, $this->name, $this->gender, $this->dob, $this->login, $this->phone];
    }
    public function updateUsingArray(array $changes): void {
        (new Database())->multipleUserUpdate($this->id, $changes);
    }
}
