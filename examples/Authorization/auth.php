<?php
    $auth = function($request, $next = NULL){
        $response = Response::unauthorized('Unauthorized');

        if($request['authorization'] === NULL){
            // also possible to check $_GET['auth'] (for example) for a token

            return $response;
        }

        $token_parts = explode(' ', $request['authorization']); // token form: '<type> <token>'

        if(count($token_parts) != 2){
            return $response;
        }

        list($token_type, $token) = $token_parts;

        // accept only bearer tokens
        if($token_type != 'Bearer'){
            return $response;
        }

        $user = NULL;

        // example token:
        // eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpYXQiOjE1MTYyMzkwMjIsInVzZXIiOnsidXNlcm5hbWUiOiJKb2huIn19.YT8-k-evgg6JXfOM2E37-pz7rF84R5yegMjynpmlyHM

        try{
            $data = JWT::decode(JWT_SECRET, $token); // constant secret

            $user = $data['user']; // also check with database, if user ok

            $request['user'] = $user;
        }catch(Exception $e){
            return $response;
        }

        if($next !== NULL){
            return $next($request);
        }else{
            return Response::ok([
                "user" => $user,
                "info" => "Authorized"
            ]);
        }
    };
?>