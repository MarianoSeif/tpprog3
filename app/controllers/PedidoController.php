<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

require_once './models/Pedido.php';
require_once './models/PedidoItem.php';
require_once './interfaces/IApiUsable.php';
require_once './services/LoggerService.php';

class PedidoController implements IApiUsable
{
    public function cambiarEstado(Request $request, Response $response)
    {
        $data = AutentificadorJWT::ObtenerData($request->getAttribute('token'));

        if(!($data->rol == 'socio' || $data->rol == 'mozo')){
            $response->getBody()->write(json_encode(['mensaje' => 'Sólo los mozos o los socios pueden cambiar el estado de los pedidos']));
            return $response;
        }

        $parametros = $request->getParsedBody();
        $pedido = new Pedido();
        try {
            /** @var Pedido $pedido */
            $pedido = $pedido->where('codigo', $parametros['codigo'])->first();
        } catch (\Throwable $th) {
            $response->getBody()->write(json_encode(['mensaje'=>'Ocurrió un problema al intentar acceder a la base de datos']));
            return $response->withStatus(500);
        }
                
        if(!$pedido){
            $response->getBody()->write(json_encode(['mensaje'=>'No existe un pedido con ese código']));
            $response = $response->withStatus(404);
        }else{
            $estadoAnterior = $pedido->estado;
            switch ($parametros['estado']) {
                case 'recibido':
                    $pedido->estado ='recibido';
                    break;
                case 'en preparacion':  //Podria no estar. Sucede cuando un empleado toma el pedido: 'pedidos/item/tomar'
                    $pedido->estado = 'en preparacion';
                    break;
                case 'listo para servir': //Podria no estar. Sucede cuando un empleado finaliza un pedido: 'pedido/item/cambiarestado'
                    $pedido->estado = 'listo para servir';
                    break;
                case 'servido':
                    $pedido->estado = 'servido';
                    break;
                case 'cancelado':
                    $pedido->estado = 'cancelado';
                    //Cancelo los items del pedido
                    $pedidoItems = PedidoItem::where('pedido_id', '=', $pedido->id)->get();
                    foreach ($pedidoItems as $item) {
                        $item->estado = 'cancelado';
                        try {
                            $item->save();
                        } catch (\Throwable $th) {
                            $response->getBody()->write(json_encode(['mensaje'=>'Ocurrió un problema al intentar acceder a la base de datos']));
                            return $response->withStatus(500);
                        }
                    }
                    break;
                default:
                    $response->getBody()->write(json_encode(['mensaje'=>'Los estados permitidos son: 1-recibido, 2-en preparacion, 3-listo para servir, 4-servido', '5-cancelado']));
                    return $response->withStatus(400);
                    break;
            }
            try {
                $pedido->save();
            } catch (\Throwable $th) {
                $response->getBody()->write(json_encode(['mensaje'=>'Ocurrió un problema al intentar acceder a la base de datos']));
                return $response->withStatus(500);
            }
            $response->getBody()->write(json_encode(['mensaje'=>'El pedido cambió de estado']));
            $logger = new LoggerService();
            $logger->logPedido($pedido->id, $data->id, $estadoAnterior, $pedido->estado);
        }
        return $response;
    }
    
    public function CargarUno($request, $response, $args)
    {
        $data = AutentificadorJWT::ObtenerData($request->getAttribute('token'));
        if(!($data->rol == 'socio' || $data->rol == 'mozo')){
            $response->getBody()->write(json_encode(['mensaje' => 'Sólo los mozos o los socios pueden cargar pedidos']));
            return $response;
        }

        $parametros = $request->getParsedBody();
        $codigo = $parametros['codigo'];
        $mesa = $parametros['mesa'];
        
        $nombreImagen = null;
        if(isset($_FILES['imagen']) || !empty($_FILES['imagen'])){
            $nombreImagen = 'Pedido'.'_'.$codigo.'_'.$_FILES['imagen']['name'];
            move_uploaded_file($_FILES['imagen']['tmp_name'], './imagenes/pedidos/'.$nombreImagen);
        }

        $pedido = new Pedido();
        $pedido->codigo = $codigo;
        $pedido->mesa = $mesa;
        $pedido->estado = 'recibido';
        if(!is_null($nombreImagen)){
            $pedido->imagen = $nombreImagen;
        }
        
        try {
            $pedido->save();
        } catch (\Throwable $th) {
            var_dump($th);
            $response
                ->getBody()
                ->write(json_encode(["mensaje" => "Ocurrió un error al tratar de guardar el pedido en la base de datos"]));
            return $response
                ->withStatus(500);
        }

        $logger = new LoggerService();
        $logger->logPedido($pedido->id, $data->id, null, $pedido->estado);

        //Guardo los items del pedido
        unset($parametros['codigo']);
        unset($parametros['mesa']);
        
        $faltantes = [];
        foreach ($parametros as $key => $value) {
            $item = new PedidoItem();
            $item->pedido_id = $pedido->id;
            $producto = Producto::where('codigo', '=', $key)->first();
            if($producto){
                $item->codigo = $producto->codigo;
                $item->cantidad = $value;
                $item->tipo = $producto->tipo;
                $item->estado = 'pendiente';
                try {
                    $item->save();
                } catch (\Throwable $th) {
                    $response
                        ->getBody()
                        ->write(json_encode(["mensaje" => "Ocurrió un error al tratar de guardar el pedido en la base de datos"]));
                    return $response
                        ->withStatus(500);
                }
                $logger->logPedidoItem($item->id, $data->id, null, $item->estado);
            }else{
                $faltantes[] = $key;
            }
        }

        if(count($faltantes)>0){
            $response->getBody()->write(json_encode([
                "mensaje" => "Se creó el pedido pero hay faltantes",
                "faltantes" => $faltantes
            ]));
        }else{
            $response->getBody()->write(json_encode(["mensaje" => "Pedido creado con exito"]));
        }
        return $response;
    }

    public function TraerUno($request, $response, $args)
    {
        $pedido = Pedido::where('codigo', '=', $args['codigo'])->first();
        if(!$pedido){
            $response->getBody()->write(json_encode(["mensaje"=>"El pedido no existe"]));
            return $response->withStatus(400);
        }else{
            $items = PedidoItem::where('pedido_id', '=', $pedido->id)->get();
            $response->getBody()->write(json_encode([
                "Pedido"=>$pedido,
                "Items"=>$items
            ]));
            return $response;
        }
    }

    public function TraerTodos($request, $response, $args)
    {
        $pedidos = Pedido::all();
        $response->getBody()->write(json_encode(["Pedidos" => $pedidos]));
        return $response;
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

    public function pendientes(Request $request, Response $response, array $args)
    {
        $data = AutentificadorJWT::ObtenerData($request->getAttribute('token'));
        switch ($data->rol) {
            case 'bartender':
                $pendientes = PedidoItem::where('estado', '=', 'pendiente')->where('tipo', '=', 'barra')->get();
                break;
            case 'cervecero':
                $pendientes = PedidoItem::where('estado', '=', 'pendiente')->where('tipo', '=', 'chopera')->get();
                break;
            case 'cocinero':
                $pendientes = PedidoItem::where('estado', '=', 'pendiente')
                    ->where(function($query){
                        $query->where('tipo', '=', 'comida')
                                ->orWhere('tipo', '=', 'candy');
                    })
                    ->get();
                break;
            case 'socio':
                $pendientes = PedidoItem::where('estado', '=', 'pendiente')->get();
                break;
            case 'mozo':
                $pendientes = PedidoItem::where('estado', '=', 'listo para servir')->get();
                break;
        }
        $response->getBody()->write(json_encode(['listaPendientes' => $pendientes]));
        return $response;
    }

    public function tomarPedidoItem(Request $request, Response $response, array $args)
    {
        $data = AutentificadorJWT::ObtenerData($request->getAttribute('token'));
        $parametros = $request->getParsedBody();
        $pedidoItem = PedidoItem::find($parametros['pedidoItemId']);
        $estadoAnterior = $pedidoItem->estado;
        $minutos = $parametros['minutos'];

        if(!in_array($pedidoItem->tipo, Usuario::ROLES_PEDIDOS[$data->rol]) || $pedidoItem->estado == 'en preparacion' ){
            $response->getBody()->write(json_encode(['mensaje' => 'El pedido no corresponde a tu área o ya fue tomado']));
            return $response;
        }
        
        $pedidoItem->empleado_id = $data->id;
        $pedidoItem->hora_de_salida = date('Y-m-d H:i:s', strtotime('+'.$minutos.' minutes'));
        $pedidoItem->estado = 'en preparacion';

        try {
            $pedidoItem->save();
        } catch (\Throwable $th) {
            $response->getBody()->write(json_encode(["mensaje" => "Ocurrió un error al tratar de guardar el item en la base de datos"]));
            return $response->withStatus(500);
        }

        $logger = new LoggerService();
        $logger->logPedidoItem($pedidoItem->id, $data->id, $estadoAnterior, $pedidoItem->estado);

        //Actualizo el estado del pedido
        try {
            $pedido = Pedido::find($pedidoItem->pedido_id);
            $pedido->estado = 'en preparacion';
            $pedido->save();
        } catch (\Throwable $th) {
            $response->getBody()->write(json_encode(["mensaje" => "Ocurrió un error al tratar de actualizar el estado del pedido en la base de datos"]));
            return $response;
        }

        $response->getBody()->write(json_encode(["mensaje" => "Acabas de tomar el pedido para preparación. A trabajar!"]));
        return $response;
    }

    /*
     * El empleado que tomó el pedido avisa que el mismo está listo para ser servido
     */
    public function cambiarEstadoItem(Request $request, Response $response)
    {
        $data = AutentificadorJWT::ObtenerData($request->getAttribute('token'));
        
        $parametros = $request->getParsedBody();
        $pedidoItem = PedidoItem::find($parametros['pedidoItemId']);
        $estadoAnterior = $pedidoItem->estado;
        if($pedidoItem->empleado_id != $data->id || $pedidoItem->estado == 'pendiente' || $pedidoItem->estado == 'listo para servir'){
            $response->getBody()->write(json_encode(['mensaje' => 'El pedido no te corresponde, aún no lo tomaste o ya está listo para servir']));
            return $response;
        }

        $pedidoItem->estado = 'listo para servir';
        
        try {
            $pedidoItem->save();
        } catch (\Throwable $th) {
            $response->getBody()->write(json_encode(["mensaje" => "Ocurrió un error al tratar de guardar el pedido en la base de datos"]));
            return $response->withStatus(500);
        }

        $logger = new LoggerService();
        $logger->logPedidoItem($pedidoItem->id, $data->id, $estadoAnterior, $pedidoItem->estado);

        $response->getBody()->write(json_encode(["mensaje" => "Pedido listo para servir"]));
        return $response;
    }

    public function servirPedidoItem(Request $request, Response $response)
    {
        $logger = new LoggerService();
        $data = AutentificadorJWT::ObtenerData($request->getAttribute('token'));
        if(!($data->rol == 'socio' || $data->rol == 'mozo')){
            $response->getBody()->write(json_encode(['mensaje' => 'Sólo los mozos o los socios pueden servir pedidos']));
            return $response;
        }

        $parametros = $request->getParsedBody();
        $ids = explode(',', $parametros['pedidoItemId']);

        $respuesta = [];
        
        foreach ($ids as $id) {
            if($id !=''){
                $pedidoItem = PedidoItem::find(trim($id));
                $estadoAnterior = $pedidoItem->estado;

                if($pedidoItem->estado != 'listo para servir'){
                    $respuesta[] = 'Alguno de los items no está listo para servir o ya fue servido';
                }

                $pedidoItem->estado = 'servido';
                
                try {
                    $pedidoItem->save();
                } catch (\Throwable $th) {
                    $response->getBody()->write(json_encode(["mensaje" => "Ocurrió un error al tratar de guardar el item en la base de datos"]));
                    return $response->withStatus(500);
                }
                
                $logger->logPedidoItem($pedidoItem->id, $data->id, $estadoAnterior, $pedidoItem->estado);

                if(!$this->actualizarPedidoYMesa($pedidoItem->pedido_id, $logger, $data->id)){
                    $respuesta[] = "El item fue servido pero ocurrió un error al tratar de actualizar el estado del pedido y la mesa en la base de datos";
                }
            }
        }
        $respuesta["mensaje"] = "Pedido servido";
        $response->getBody()->write(json_encode($respuesta));
        return $response;
    }

    private function actualizarPedidoYMesa($pedido_id, $logger, $empleado_id)
    {
        $pedidoCompletado = true;
        //Reviso el estado de todos los items del pedido
        $pedidoItems = PedidoItem::where('pedido_id', '=', $pedido_id)->get();
        foreach ($pedidoItems as $item) {
            if($item->estado != 'servido' && $item->estado != 'cancelado'){
                $pedidoCompletado = false;
            }
        }

        //Actualizo estado pedido
        $pedido = Pedido::find($pedido_id);
        $estadoAnterior = $pedido->estado;
        if($pedidoCompletado){
            $pedido->estado = 'servido';
        }else{
            $pedido->estado = 'servido parcial';
        }

        try {
            $pedido->save();
            $logger->logPedido($pedido_id, $empleado_id, $estadoAnterior, $pedido->estado);
        } catch (\Throwable $th) {
            return false;
        }

        //Actualizo estado Mesa
        $mesa = Mesa::where('codigo', '=', $pedido->mesa)->first();
        if($mesa->estado == 'con cliente esperando pedido'){
            $mesaEstadoAnterior = $mesa->estado;
            $mesa->estado = 'con cliente comiendo';
    
            try {
                $mesa->save();
                $logger->logMesa($mesa->id, $empleado_id, $mesaEstadoAnterior, $mesa->estado);
            } catch (\Throwable $th) {
                return false;
            }            
        }
        return true;
    }

    public function cuantoFalta(Request $request, Response $response)
    {
        $fecha = date('Y-m-d H:i:s');
        $parametros = $request->getParsedBody();
        $pedido = Pedido::where('codigo', '=', $parametros['codigoPedido'])->first();
        if($pedido->mesa != $parametros['codigoMesa']){
            $response->getBody()->write(json_encode(["mensaje" => "Alguno de los códigos ingresados es incorrecto"]));
            return $response->withStatus(400);
        }
        
        $pedidoItems = PedidoItem::where('pedido_id', '=', $pedido->id)->get();
        foreach ($pedidoItems as $item) {
            if($item->hora_de_salida > $fecha ){
                $fecha = $item->hora_de_salida;
            }
        }
        
        $response->getBody()->write(json_encode(["mensaje" => "Hora estimada de entrega del pedido: ".$fecha]));
        return $response;
    }

    public function cancelarPedidoItem(Request $request, Response $response)
    {
        $data = AutentificadorJWT::ObtenerData($request->getAttribute('token'));
        
        $parametros = $request->getParsedBody();
        $pedidoItem = PedidoItem::find($parametros['pedidoItemId']);
        $estadoAnterior = $pedidoItem->estado;

        if($data->rol != 'mozo' || $pedidoItem->estado == 'servido'){
            $response->getBody()->write(json_encode(['mensaje' => 'No podés cancelar pedidos o el pedido ya fue servido']));
            return $response;
        }

        $pedidoItem->estado = 'cancelado';
        
        try {
            $pedidoItem->save();    
        } catch (\Throwable $th) {
            $response->getBody()->write(json_encode(["mensaje" => "Ocurrió un error al tratar de guardar el pedido en la base de datos"]));
            return $response->withStatus(500);
        }

        $logger = new LoggerService();
        $logger->logPedidoItem($pedidoItem->id, $data->id, $estadoAnterior, $pedidoItem->estado);

        if($this->actualizarPedidoYMesa($pedidoItem->pedido_id, $logger, $data->id)){
            $response->getBody()->write(json_encode(["mensaje" => "Pedido cancelado"]));
            return $response;            
        }else{
            $response->getBody()->write(json_encode(["mensaje" => "El pedido fue cancelado pero ocurrió un error al tratar de actualizar el estado del pedido y la mesa"]));
            return $response->withStatus(500);
        }

        $response->getBody()->write(json_encode(["mensaje" => "Item cancelado"]));
        return $response;
    }
}
