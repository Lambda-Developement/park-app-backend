<?php
var_dump(json_encode(array(
    'request' => array(
        'action' => 'login',
        'data' => array(
            'login' => 'l',
            'pass' => 'l'
        )
    )
)));
var_dump(password_hash('l', PASSWORD_BCRYPT));