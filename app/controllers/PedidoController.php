<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

require_once './models/Pedido.php';
require_once './interfaces/IApiUsable.php';

class PedidoController implements IApiUsable
{
    public function cambiarEstado(Request $request, Response $response)
    {
        $data = $request->getParsedBody();
        $pedido = Pedido::obtenerPedido($data['codigo']);
                
        if(!$pedido){
            $response = $response->withStatus(404, 'No existe un pedido con ese código');
        }else{
            switch ($data['estado']) {
                case 'recibido':
                    var_dump('recibido');
                    break;
                case 'en preparacion':
                    var_dump('en prep');
                    break;
                case 'listo para servir':
                    var_dump('listo');
                    break;
                case 'servido':
                    var_dump('servido');
                    break;
                
                default:
                    $response->withStatus(400, 'Los estados permitidos son: 
                                                        1-recibido,
                                                        2-en preparacion,
                                                        3-listo para servir,
                                                        4-servido');
                    return $response;
                    break;
            }
            $response = $response->withStatus(200, 'El pedido cambió de estado');
        }
        return $response;
    }
    
    public function CargarUno($request, $response, $args)
    {
        $parametros = $request->getParsedBody();

        $codigo = $parametros['codigo'];
        $mesa = $parametros['mesa'];
        
        $pedido = new Pedido();
        $pedido->codigo = $codigo;
        $pedido->mesa = $mesa;
        $pedido->estado = 'recibido';
        $pedido->creado = date('Y-m-d H:i:s');
        $pedido->crearPedido();

        $payload = json_encode(array("mensaje" => "Pedido creado con exito"));

        $response->getBody()->write($payload);
        return $response
          ->withHeader('Content-Type', 'application/json');
    }

    public function TraerUno($request, $response, $args)
    {
        $codigo = $args['codigo'];
        $pedido = Pedido::obtenerPedido($codigo);
        $payload = json_encode($pedido);

        $response->getBody()->write($payload);
        return $response
          ->withHeader('Content-Type', 'application/json');
    }

    public function TraerTodos($request, $response, $args)
    {
        $lista = Pedido::obtenerTodos();
        $payload = json_encode(array("listaPedidos" => $lista));

        $response->getBody()->write($payload);
        return $response
          ->withHeader('Content-Type', 'application/json');
    }

    public function ModificarUno($request, $response, $args)
    {
        $parametros = $request->getParsedBody();

        $nombre = $parametros['nombre'];
        Pedido::modificarPedido($nombre);

        $payload = json_encode(array("mensaje" => "Pedido modificado con exito"));

        $response->getBody()->write($payload);
        return $response
          ->withHeader('Content-Type', 'application/json');
    }

    public function BorrarUno($request, $response, $args)
    {
        $parametros = $request->getParsedBody();

        $usuarioId = $parametros['usuarioId'];
        Pedido::borrarPedido($usuarioId);

        $payload = json_encode(array("mensaje" => "Pedido borrada con exito"));

        $response->getBody()->write($payload);
        return $response
          ->withHeader('Content-Type', 'application/json');
    }
}
