<?php
require 'Exceptions.php';
require 'Logger.php';
require 'Database.php';
require 'Package.php';
require 'Keys.php';
require 'MailSender.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: X-Requested-With");

if (!isset($_POST['request'])) die(http_response_code(403));

try {
    $request = json_decode($_POST['request'], flags: JSON_THROW_ON_ERROR);
} catch (JsonException $e) {
    Logger::log($e->getMessage());
    die(http_response_code(510));
}

try {
    $pack = new Package($request);
} catch (UnexpectedValueException $e) {
    die(http_response_code(418));
} catch (DatabaseException $e) {
    die(http_response_code(417));
}

$db = new Database();

switch ($pack->request) {
    case Request::LOGIN:
        if (!isset($pack->data)) die(http_response_code(406));
        elseif (!isset($pack->data->login) || !isset($pack->data->pass)) die(http_response_code(400));
        $login = $pack->data->login;
        try {
            $udata = $db->getUserByLogin($login);
        } catch (DatabaseException) {
            die(http_response_code(417));
        }
        if ($udata['confirmed'] == 0) die(http_response_code(402));
        $hash = $udata['hash'];
        $passwd = $pack->data->pass;
        if (!password_verify($passwd, $hash)) {
            die(http_response_code(401));
        }
        try {
            $key = Keys::assignLoginKey($udata['id']);
        } catch (KeyException $e) {
            Logger::log($e->getMessage());
            die(http_response_code(503));
        }
        die($key);
    case Request::CHECK_KEY:
        if (!isset($pack->data)) die(http_response_code(406));
        elseif (!isset($pack->data->key)) die(http_response_code(400));
        $key = $pack->data->key;
        try {
            $user = new User($key);
        } catch (DatabaseException) {
            die(http_response_code(417));
        }
        exit;
    case Request::REGISTRATION:
        if (!isset($pack->data)) die(http_response_code(406));
        elseif (!isset($pack->data->name) || !isset($pack->data->mail) || !isset($pack->data->pass)) die(http_response_code(400));
        // TODO: Отправка подтверждения регистрации
        $data = $pack->data;
        $name = $data->name;
        $mail = $data->mail;
        $pass = $data->pass;
        $hash = password_hash($pass, PASSWORD_BCRYPT);
        try {
            $db->getUserByLogin($mail);
            die(http_response_code(409));
        } catch (DatabaseException) { }
        try {
            $key = Keys::generateKey();
            (new MailSender())->addAddress($mail)
                ->setSubject("Подтверждение регистрации в Park App")
                ->setBody("http://io.cordova.parkapp/mail/{$key}")
                ->send();
            $db->insertUser($mail, $name, $hash, $key);
        } catch (AlreadyRegisteredException) {
            die(http_response_code(409));
        } catch (MailException) {
            die(http_response_code(523));
        }
        exit;
    case Request::REGISTRATION_CONFIRMATION:
        if (!isset($pack->data)) die(http_response_code(406));
        elseif (!isset($pack->data->conf)) die(http_response_code(400));
        $key = $pack->data->conf;
        if ($db->getConfirmationKeyUsage($key) != 1) die(http_response_code(417));
        try {
            $u = $db->getUserByConfKey($key);
        } catch (DatabaseException) {
            die(http_response_code(417));
        }
        $db->activateUser($u['id']);
        exit;
    case Request::DATA_REQUEST:
        try {
            $data = json_encode($db->getData(), flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            die(http_response_code(510));
        }
        exit($data);
    case Request::INSERT_ERROR:
        if (!isset($pack->data)) die(http_response_code(406));
        elseif (!$pack->invoker instanceof User) die(http_response_code(424));
        elseif (!isset($pack->data->text)) die(http_response_code(400));
        $text = $pack->data->text;
        $db->insertErrorMessage($text, $pack->invoker);
        exit;
    case Request::REMIND_PASSWORD:
        if (!isset($pack->data)) die(http_response_code(406));
        elseif (!isset($pack->data->mail)) die(http_response_code(400));
        $mail = $pack->data->mail;
        try {
            // TODO: Отправка сообщения о восстановлении
            $d = $db->getUserByLogin($mail);
            $key = Keys::assignRemindKey($d['id']);
            (new MailSender())->addAddress($mail)
                ->setBody("http://io.cordova.parkapp/password/{$key}")
                ->setSubject("Восстановление пароля в Park App")
                ->send();
        } catch (DatabaseException|MailException $e) {
            exit;
        }
        exit;
    case Request::REMIND_PASSWORD_CONFIRMATION:
        if (!isset($pack->data)) die(http_response_code(406));
        elseif (!isset($pack->data->conf) || !isset($pack->data->pass)) die(http_response_code(400));
        $key = $pack->data->conf;
        if ($db->getRestoreKeyUsage($key) != 1) die(http_response_code(417));
        try {
            $u = $db->getUserByRestoreKey($key);
        } catch (DatabaseException) {
            die(http_response_code(417));
        }
        $hash = password_hash($pack->data->pass, PASSWORD_BCRYPT);
        try {
            $db->updatePassword($u['login'], $hash);
        } catch (NotFoundException $e) {
            die(http_response_code(417));
        }
        exit;
    case Request::GET_PROFILE_DATA:
        if (!$pack->invoker instanceof User) die(http_response_code(424));
        try {
            $je = json_encode($pack->invoker->getData(), flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            die(http_response_code(510));
        }
        exit($je);
    case Request::EDIT_PROFILE_DATA:
        if (!isset($pack->data)) die(http_response_code(406));
        elseif (!$pack->invoker instanceof User) die(http_response_code(424));
        $data = (array) $pack->data;
        $allowed_edits = ['name', 'gender', 'dob', 'phone', 'pass'];
        foreach ($data as $key => $elem) {
            if (!in_array($key, $allowed_edits)) die(http_response_code(400));
            if ($key != 'pass' && $pack->invoker->$key == $elem) unset($data[$key]);
            elseif ($key == 'pass') {
                $hash = password_hash($elem, PASSWORD_BCRYPT);
                unset($data[$key]);
                $data['hash'] = $hash;
            } elseif ($key == 'dob') {
                $data['dob'] = date('Y-m-d', $elem);
            }
        }
        $pack->invoker->updateUsingArray($data);
        exit;
    case Request::UPLOAD_PROFILE_PIC:
        if (!isset($pack->image)) die(http_response_code(406));
        elseif (!$pack->invoker instanceof User) die(http_response_code(424));
        if ($pack->image['size'] == 0) die(http_response_code(400));
        elseif ($pack->image['error'] != UPLOAD_ERR_OK) die(http_response_code(400));
        if (is_uploaded_file($pack->image['tmp_name'])) {
            $ext = ".".pathinfo($pack->image['name'], PATHINFO_EXTENSION);
            if ($ext != '.png' && $ext != '.jpg' && $ext != '.jpeg') die(http_response_code(400));
            if ($pack->invoker->avatar_loc != NULL) unlink($pack->invoker->avatar_loc);
            $target = 'avatars/'.$pack->invoker->id."_".bin2hex(random_bytes(5)).$ext;
            move_uploaded_file($pack->image['tmp_name'], $target);
            $db->updateAvatarPosition($pack->invoker->id, $target);
        } else die(http_response_code(400));
        exit;
    case Request::SET_REVIEW:
        if (!isset($pack->data)) die(http_response_code(406));
        elseif (!isset($pack->data->id) || !isset($pack->data->mark)) die(http_response_code(400));
        elseif (!$pack->invoker instanceof User) die(http_response_code(424));
        $id = $pack->data->id;
        $mark = $pack->data->mark;
        if ($mark < 1 || $mark > 5) die(http_response_code(400));
        $review = $pack->data->review ?? NULL;
        try {
            if ($pack->data->type == 0) $db->insertReview($pack->invoker, $id, $mark, $review);
        } catch (ENotFoundException $e) {
            die(http_response_code(417));
        }
        exit;
    case Request::GET_REVIEWS:
        if (!isset($pack->data)) die(http_response_code(406));
        elseif (!isset($pack->data->id)) die(http_response_code(400));
        $id = $pack->data->id;
        try {
            $r = $db->getReviews($id);
        } catch (ENotFoundException $e) {
            die(http_response_code(417));
        }
        $tot = 0;
        $c = 0;
        foreach ($r as &$rev) {
            $id = $rev[0];
            try {
                $u = $db->getUserByID($id);
                $rev[0] = [$u['name'], $u['avatarloc']];
            } catch (DatabaseException $e) {
                continue;
            }
            $tot += $rev[1];
            $c++;
        }
        $x[1] = $r;
        $x[0] = ($c != 0) ? round($tot / $c, 2) : 0;
        try {
            $je = json_encode($x, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            die(http_response_code(510));
        }
        exit($je);
}
