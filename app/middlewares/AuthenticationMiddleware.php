<?php

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Factory\ResponseFactory;

require_once './class/AutentificadorJWT.php';

class AuthenticationMiddleware implements MiddlewareInterface
{
    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        $authHeaderString = $request->getHeader('Authorization');
        foreach ($authHeaderString as $header) {
            if(str_contains($header, 'Bearer')){
                $token = explode(' ', $header)[1];
                break;
            }
        }
        try {
            AutentificadorJWT::VerificarToken($token);
            $request = $request->withAttribute('token', $token);
            $response = $handler->handle($request);
        } catch (\Throwable $th) {
            $responseFactory = new ResponseFactory();
            $response = $responseFactory->createResponse(400, 'Acceso Denegado!: '.$th->getMessage());
            return $response;
        }
        return $response;
    }
    
    /* public function verificarVerboYCredencialesJson(Request $request, RequestHandlerInterface $handler): Response
    {
        $response = new Response();
        if($request->getMethod() === 'GET'){
            $apiResponse = $handler->handle($request);
            $response->getBody()->write((string)$apiResponse->getBody());
        }
        else if($request->getMethod() === 'POST'){
            $data = $request->getParsedBody();
            $obj = json_decode($data['obj_json']);
            
            if($obj->perfil === 'admin'){
                $apiResponse = $handler->handle($request);
                $response->getBody()->write((string)$apiResponse->getBody());
            }else{
                $data = ["mensaje" => "ERROR. {$obj->nombre} sin permisos"];
                $payload = json_encode($data);
                $response->getBody()->write($payload);
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(403);
            }
        }
        return $response->withHeader('Content-Type', 'application/json');
	} */
}