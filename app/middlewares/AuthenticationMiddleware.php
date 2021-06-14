<?php

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Factory\ResponseFactory;

require_once './class/AutentificadorJWT.php';

class AuthenticationMiddleware implements MiddlewareInterface
{
    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        $token = '';
        $authHeaderString = $request->getHeader('Authorization');
        foreach ($authHeaderString as $header) {
            if(str_contains($header, 'Bearer')){
                $token = explode(' ', $header)[1];
                break;
            }
        }
        if($token != ''){
            try {
                AutentificadorJWT::VerificarToken($token);
                $request = $request->withAttribute('token', $token);
                $response = $handler->handle($request);
            } catch (\Throwable $th) {
                $responseFactory = new ResponseFactory();
                $response = $responseFactory->createResponse(400, 'AE: Acceso Denegado');
                $response->getBody()->write(json_encode(['mensaje' => $th->getMessage()]));
                return $response;
            }
        }else{
            $responseFactory = new ResponseFactory();
            $response = $responseFactory->createResponse(400, 'Acceso Denegado');
            $response->getBody()->write(json_encode(['mensaje' => 'Token Vacío']));
        }
        return $response;
    }
}