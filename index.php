<?php
require 'Exceptions.php';
require 'Logger.php';
require 'Database.php';
require 'Package.php';
require 'Keys.php';
require 'MailSender.php';

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
} catch (DatabaseException $e) {
    die(http_response_code(417));
}

try {
    $db = new Database();
} catch (DatabaseException) {
    Logger::log("Unable to connect to the database!");
    die(http_response_code(504));
}

switch ($pack->action) {
    case Action::LOGIN:
        if (!isset($pack->data)) die(http_response_code(406));
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
        try {
            $key = Keys::assign($udata['id']);
        } catch (KeyGeneratorException $e) {
            Logger::log($e->getMessage());
            die(http_response_code(503));
        }
        die($key);
    case Action::VALIDATE_KEY:
        if (!isset($pack->data)) die(http_response_code(406));
        elseif (!isset($pack->data->key)) die(http_response_code(400));
        $key = $pack->data->key;
        try {
            $user = new User($key);
        } catch (DatabaseException) {
            die(http_response_code(417));
        }
        exit;
    case Action::REGISTER:
        if (!isset($pack->data)) die(http_response_code(406));
        elseif (!isset($pack->data->name) || !isset($pack->data->mail) || !isset($pack->data->pass)) die(http_response_code(400));
        // TODO: Отправка подтверждения регистрации
        //$mail = new MailSender();
        $data = $pack->data;
        $name = $data->name;
        $mail = $data->mail;
        $pass = $data->pass;
        $hash = password_hash($pass, PASSWORD_BCRYPT);
        try {
            $db->insertUser($mail, $name, $hash);
        } catch (UserAlreadyRegisteredException) {
            die(http_response_code(409));
        }
        exit;
    case Action::DATA_REQUEST:
        try {
            $data = json_encode($db->getData(), flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            die(http_response_code(510));
        }
        exit($data);
    case Action::ERROR_MSG:
        if (!isset($pack->data)) die(http_response_code(406));
        elseif (!$pack->invoker instanceof User) die(http_response_code(424));
        elseif (!isset($pack->data->text)) die(http_response_code(400));
        $text = $pack->data->text;
        $db->insertErrorMessage($text, $pack->invoker);
        exit;
    case Action::REMIND_PASS:
        if (!isset($pack->data)) die(http_response_code(406));
        elseif (!isset($pack->data->mail)) die(http_response_code(400));
        $mail = $pack->data->mail;
        try {
            $m = $db->getUserByLogin($mail);
            // TODO: Отправка сообщения о восстановлении
            // DEBUG: Пока не реализованы подтверждения
            $db->setUserPassword($mail, 12345);
        } catch (DatabaseException $e) {
            // do nothing -> follow for finally
        } finally {
            exit;
        }
    default: die(http_response_code(405));
}
