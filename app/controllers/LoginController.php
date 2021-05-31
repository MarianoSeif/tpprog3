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

        $objUsuario = Usuario::obtenerUsuario($usuario);
        
        if(password_verify ($pass, $objUsuario->clave)){
            $datos = ['usuario' => $usuario, 'rol' => $objUsuario->rol];
            $token = AutentificadorJWT::CrearToken($datos);
            $reponse->getBody()->write(json_encode(["token" => $token]));
            $response->withStatus(200);
        }else{
            $reponse->getBody()->write(json_encode(["mensaje" => "Acceso Denegado"]));
            $response->withStatus(400);
        }
        
        return $reponse
            ->withHeader('Content-Type', 'application/json');
    }
}