<?php
require_once 'User.php';
require_once 'Exceptions.php';

enum Request: string {
    case LOGIN = 'login';
    case CHECK_KEY = 'checkkey';
    case REGISTRATION = 'register';
    case REGISTRATION_CONFIRMATION = 'confirmregistration';
    case DATA_REQUEST = 'getdata';
    case REMIND_PASSWORD = 'remindrequest';
    case REMIND_PASSWORD_CONFIRMATION = 'remindconfirmation';
    case INSERT_ERROR = 'errornotification';
    case GET_PROFILE_DATA = 'getprofiledata';
    case EDIT_PROFILE_DATA = 'profiledataupdate';
    case UPLOAD_PROFILE_PIC = 'profilepictureupdate';
    case SET_REVIEW = 'setreview';
    case GET_REVIEWS = 'getrevewlist';
}

class Package {

    readonly public ?User $invoker;
    readonly public Request $request;
    readonly public ?object $data;
    readonly public ?array $image;

    /**
     * @param object $json_object JSON object received over HTTPS
     * @return void
     * @throws UnexpectedValueException 0xF - No action is specified, 0xFF - Action is unknown
     * @throws DatabaseException If user does not exist
     */
    function __construct(object $json_object) {
        if (!isset($json_object->action)) throw new UnexpectedValueException("No action specified!", 0xF);
        try {
            $this->invoker = (isset($json_object->user_key)) ? new User($json_object->user_key) : NULL;
            $this->image = (isset($_FILES['new_avatar_img'])) ? $_FILES['new_avatar_img'] : NULL;
            $this->request = Request::from($json_object->action);
        } catch (ValueError) {
            throw new UnexpectedValueException("Action is unknown!", 0xFF);
        } catch (DatabaseException $e) {
            throw new DatabaseException($e->getMessage(), $e->getCode(), $e);
        }
        $this->data = $json_object->data ?? NULL;
    }
}
