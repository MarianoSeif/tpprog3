<?php

require_once './class/AutentificadorJWT.php';
require_once './models/Loggers/LoginLogger.php';

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class LoginController
{
    public function login(Request $request, Response $response): Response
    {
        $params = $request->getParsedBody();
        $usuario = $params['usuario'];
        $pass = $params['pass'];

        $user = Usuario::where('usuario', '=', $usuario)->first();

        if(!$user){
            $response->getBody()->write(json_encode(["mensaje" => "Usuario no encontrado"]));
            return $response->withStatus(400);
        }else{
            if(password_verify ($pass, $user->clave)){
                $datos = ['id' => $user->id, 'usuario' => $usuario, 'rol' => $user->rol];
                $token = AutentificadorJWT::CrearToken($datos);
                $response->getBody()->write(json_encode(["token" => $token]));
                //Logear ingreso
                $loginLog = new LoginLogger();
                $loginLog->empleado_id = $user->id;
                
                try {
                    $loginLog->save();
                } catch (\Throwable $th) {
                    //Falló el registro en el log. Logear error en otro log
                }
            }else{
                $response->getBody()->write(json_encode(["mensaje" => "Acceso Denegado"]));
                $response = $response->withStatus(400);
            }
        }
        return $response;
    }
}