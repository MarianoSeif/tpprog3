<?php

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Factory\ResponseFactory;

require_once './class/AutentificadorJWT.php';

class AuthorizationMiddleware implements MiddlewareInterface
{
    private $rolesUsuarios = ['socio'];
    private $rolesProductos = ['socio'];
    private $rolesMesas = ['socio', 'mozo'];
    private $rolesPedidos = ['socio', 'mozo', 'bartender', 'cervecero', 'cocinero'];
    private $rolesInformes = ['socio'];
    
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        //Obtengo la seccion desde la ruta del uri 
        $path = $request->getUri()->getPath();
        $seccion = explode('/', $path)[1];

        //Recupero el token desde el atributo del request y luego los datos del usuario
        $data = AutentificadorJWT::ObtenerData($request->getAttribute('token'));

        if(in_array($data->rol, $this->{'roles' . ucfirst($seccion)})){
            $response = $handler->handle($request);
        }else{
            $responseFactory = new ResponseFactory();
            $response = $responseFactory->createResponse(400, 'Access Denied');
            $response->getBody()->write(json_encode(["mensaje"=>"No tenés los permisos necesarios"]));
            return $response;
        }
        return $response;
    }
}