<?php

use PAF\Router\Router;

require_once __DIR__ . '/lib/PAF/autoload.php'; // path to autoloader

define('JWT_SECRET', 'mySuperSecureSecret');

require_once 'JWT.php'; // using JWT tokens; Using the https://github.com/m-thalmann/PHP-JWT library
require_once 'auth.php';

Router::init();

Router::addRoutes()
    ->get('/unprotected', function ($req) {
        return 'Unprotected route';
    })
    ->get('/protected', $auth, function ($req) {
        return 'Protected route; User: ' . json_encode($req['user']); // $req['user'] from $auth()
    })
    ->get('/', $auth); // output authorization info

Router::execute();
