<?php
    // error_reporting(E_ALL ^ E_WARNING); // TODO: enable in production

    // TODO: add all missing libraries and files (marked with *)

    try{
        // Libraries
        require_once __DIR__ . '/lib/PAF/PAF.php';          // *
        require_once __DIR__ . '/lib/PHP-JWT/JWT.php';      // *

        //Config
        require_once __DIR__ . '/config/Config.php';
        Config::load(__DIR__ . '/config/config.json');

        // Includes
        require_once __DIR__ . '/include/functions.php';    // *
        require_once __DIR__ . '/include/pagination.php';

        // Models
        // TODO: include models

        // Constants
        define('VERSION', Config::get('version'));
        define('ROOT_URL', Config::get('root_url'));

        // Router
        Router::setHeaders([
            "Access-Control-Allow-Headers" => "Content-Type, Authorization"
        ]);
        Router::init(ROOT_URL, TRUE);

        // Auth
        require_once __DIR__ . '/include/auth.php';         // *

        // Routes

        try{
            Router::addRoutes()->get('/', function(){
                return "Test-API v" . VERSION;
            });
    
            // TODO: include routes
    
            if(!Router::execute()){
                throw new Exception('Method not found');
            }
        }catch(Exception $e){
            Router::output(Response::badRequest($e->getMessage()));
        }
    }catch(Exception $e){
        @header("Content-Type: application/json");
        @http_response_code(500);

        echo json_encode($e->getMessage());
    }
?>