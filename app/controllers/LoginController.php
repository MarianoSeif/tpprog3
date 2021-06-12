<?php

require_once './class/AutentificadorJWT.php';

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Psr7\Factory\ResponseFactory;

class LoginController
{
    public function login(Request $request, Response $response): Response
    {
        $responseFactory = new ResponseFactory();
        $reponse = $responseFactory->createResponse();

        $params = $request->getParsedBody();
        $usuario = $params['usuario'];
        $pass = $params['pass'];

        $user = Usuario::where('usuario', '=', $usuario)->first();

        if(!$user){
            $reponse->getBody()->write(json_encode(["mensaje" => "Usuario no encontrado"]));
            $response = $response->withStatus(400);
        }else{
            if(password_verify ($pass, $user->clave)){
                $datos = ['usuario' => $usuario, 'rol' => $user->rol];
                $token = AutentificadorJWT::CrearToken($datos);
                $reponse->getBody()->write(json_encode(["token" => $token]));
                $response->withStatus(200);
            }else{
                $reponse->getBody()->write(json_encode(["mensaje" => "Acceso Denegado"]));
                $response = $response->withStatus(400);
            }
        }
        return $reponse;
    }
}