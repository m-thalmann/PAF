<?php
    require_once 'PAF.php';

    $routerv1 = new PAF('/api/v1');
    $routerv2 = new PAF('/api/v2');

    $routerv1->map('GET', '/*', function($req){
        return $req;
    });
    $routerv2->map('GET', '/aaron', function($req){
        return "lul";
    });

    if(!$routerv1->execute()){
        $routerv2->execute();
    }
?>