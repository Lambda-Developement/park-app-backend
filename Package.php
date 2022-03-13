<?php

require_once 'User.php';

enum Action: string {
    case LOGIN = 'login';
    case VALIDATE_KEY = 'kval';
}

class Package {

    readonly public ?User $invoker;
    readonly public Action $action;
    readonly public ?object $data;

    /**
     * @param object $json_object JSON object received over HTTPS
     * @return void
     * @throws UnexpectedValueException 0xF - No action is specified, 0xFF - Action is unknown
     */
    function __construct(object $json_object) {
        if (!isset($json_object->action)) throw new UnexpectedValueException("No action specified!", 0xF);
        $this->invoker = (isset($json_object->user_key)) ? new User($json_object->user_key) : NULL;
        try {
            $this->action = Action::from($json_object->action);
        } catch (ValueError) {
            throw new UnexpectedValueException("Action is unknown!", 0xFF);
        }
        $this->data = $json_object->data ?? NULL;
    }
}