<?php

namespace PAF;

spl_autoload_register(function ($class) {
    if (strpos($class, __NAMESPACE__) !== 0) {
        return;
    }

    $path = __DIR__ . '/' . substr($class, strlen(__NAMESPACE__) + 1) . '.php';

    require_once $path;
});
