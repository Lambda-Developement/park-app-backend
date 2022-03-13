<?php
require 'Exceptions.php';
require 'Logger.php';
require 'Database.php';
require 'Package.php';
require 'Keys.php';

if (!isset($_POST['request'])) die(http_response_code(403));

try {
    $request = json_decode($_POST['request'], flags: JSON_THROW_ON_ERROR);
} catch (JsonException $e) {
    Logger::log($e->getMessage());
    die("JsonException[{$e->getCode()}]: {$e->getMessage()}");
}

try {
    $pack = new Package($request);
} catch (UnexpectedValueException $e) {
    die(http_response_code(418));
}

try {
    $db = new Database();
} catch (DatabaseException) {
    Logger::log("Unable to connect to the database!");
    die(http_response_code(504));
}

switch ($pack->action) {
    case Action::LOGIN:
        if (is_null($pack->data)) die(http_response_code(406));
        elseif (!isset($pack->data->login) || !isset($pack->data->pass)) die(http_response_code(400));
        $login = $pack->data->login;
        try {
            $udata = $db->getUserByLogin($login);
        } catch (DatabaseException) {
            die(http_response_code(417));
        }
        $hash = $udata['hash'];
        $passwd = $pack->data->pass;
        if (!password_verify($passwd, $hash)) {
            die(http_response_code(401));
        }
        $key = Keys::assign($udata['id']);
        die($key);
    case Action::VALIDATE_KEY:
        if (is_null($pack->data)) die(http_response_code(406));
        elseif (!isset($pack->data->key)) die(http_response_code(400));
        $key = $pack->data->key;
        try {
            $user = new User($key);
        } catch (DatabaseException) {
            die(http_response_code(417));
        }
        exit;
    default: die(http_response_code(405));
}
