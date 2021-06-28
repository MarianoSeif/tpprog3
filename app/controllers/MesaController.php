<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

require_once './models/Mesa.php';
require_once './models/Pedido.php';
require_once './models/PedidoItem.php';
require_once './models/Producto.php';
require_once './models/Encuesta.php';
require_once './services/CuentaPdfService.php';
require_once './interfaces/IApiUsable.php';
require_once './services/LoggerService.php';
class MesaController implements IApiUsable
{
    public function CargarUno($request, $response, $args)
    {
        $data = AutentificadorJWT::ObtenerData($request->getAttribute('token'));
        $parametros = $request->getParsedBody();

        $codigo = $parametros['codigo'];
        $numero = $parametros['numero'];
        $nombreCliente = $parametros['nombreCliente'];
        
        $mesa = new Mesa();
        $mesa->codigo = $codigo;
        $mesa->numero = $numero;
        $mesa->estado = 'con cliente esperando pedido';
        $mesa->nombre_cliente = $nombreCliente;

        try {
            $mesa->save();
        } catch (\Throwable $th) {
            $response
                ->getBody()
                ->write(json_encode(["mensaje" => "Ocurrió un error al tratar de guardar la mesa en la base de datos"]));
            return $response
                ->withStatus(500);
        }

        $logger = new LoggerService();
        $logger->logMesa($mesa->id, $data->id, null, $mesa->estado);
        
        $response
            ->getBody()
            ->write(json_encode(["mensaje" => "Mesa creada con exito"]));
        return $response;
    }

    public function TraerUno($request, $response, $args)
    {
        $mesa = Mesa::where('codigo', '=', $args['codigo'])->first();
        if(!$mesa){
            $response->getBody()->write(json_encode(["mensaje"=>"La mesa no existe"]));
            return $response->withStatus(400);
        }else{
            $response->getBody()->write(json_encode(["mesa"=>$mesa]));
            return $response;
        }
    }

    public function TraerTodos($request, $response, $args)
    {
        $mesas = Mesa::all();
        $response->getBody()->write(json_encode(["listaMesas" => $mesas]));
        return $response;
    }

    public function ModificarUno($request, $response, $args)
    {
        return $response;
    }

    public function BorrarUno($request, $response, $args)
    {
        $data = AutentificadorJWT::ObtenerData($request->getAttribute('token'));
        $mesa = Mesa::find($args['id']);
        $estadoAnterior = $mesa->estado;
        $mesa->estado = 'eliminada';
        
        try {
            $mesa->save();
            if(Mesa::destroy($args['id'])){
                $response->getBody()->write(json_encode(["mensaje"=>"La mesa fue eliminada con éxito"]));
            }else{
                $response->getBody()->write(json_encode(["mensaje"=>"No se encontró la mesa"]));
            }
        } catch (\Throwable $th) {
            $response->getBody()->write(json_encode(["mensaje" => "Ocurrió un error al tratar de acceder a la base de datos"]));
            return $response->withStatus(500);
        }
        
        $logger = new LoggerService();
        $logger->logMesa($mesa->id, $data->id, $estadoAnterior, $mesa->estado);

        return $response;
    }

    public function traerLaCuenta(Request $request, Response $response, array $args)
    {
        try {
            $mesa = Mesa::where('codigo', '=', $args['codigo'])->first();
            if($mesa->estado == 'con cliente pagando'){
                $response->getBody()->write(json_encode(['mensaje'=>'La cuenta ya fue generada']));
                return $response->withStatus(400);
            }

            $data = AutentificadorJWT::ObtenerData($request->getAttribute('token'));
            $contenidoCuenta = [];
            $total = 0.00;
            $estadoAnterior = $mesa->estado;

            array_push($contenidoCuenta, 'Fecha: '.date('d-m-Y H:i:s'));
            array_push($contenidoCuenta, 'Cliente: '.$mesa->nombre_cliente);
            array_push($contenidoCuenta, 'Cant      Descr                   Precio');

            $pedido = Pedido::where('mesa', '=', $args['codigo'])->first();
            $pedidoItems = PedidoItem::where('pedido_id', '=', $pedido->id)->get();
            foreach ($pedidoItems as $item) {
                if($item->estado = 'servido'){
                    $producto = Producto::where('codigo', '=', $item->codigo)->first();
                    $total += $item->cantidad * $producto->precio;
                    array_push($contenidoCuenta, $item->cantidad.'      '.$producto->nombre.'       '.$producto->precio);
                }
            }
            
            try {
                $pedido->total = $total;
                $pedido->save();
            } catch (\Throwable $th) {
                var_dump($th);
            }

            array_push($contenidoCuenta, 'Total: $'.strval($total));
            
            $cuentaService = new CuentaPdfService();
            $nombreArchivo = 'Ticket_'.$mesa->codigo.'_'.$pedido->codigo.'_'.date('d-m-Y');
            
            $mesa->estado = 'con cliente pagando';
            $mesa->save();
            $response->getBody()->write(json_encode(['mensaje'=>'Se envia la cuenta']));
            
            $logger = new LoggerService();
            $logger->logMesa($mesa->id, $data->id, $estadoAnterior, $mesa->estado);
            
            $cuentaService->createPdf('Restaurant La comanda', $contenidoCuenta, $nombreArchivo);
            return $response;
        } catch (\Throwable $th) {
            var_dump($th);
            $response->getBody()->write(json_encode(['mensaje'=>'Error generando la cuenta']));
            return $response->withStatus(500);
        }   
    }

    public function cerrarMesa(Request $request, Response $response)
    {
        $data = AutentificadorJWT::ObtenerData($request->getAttribute('token'));
        
        $parametros = $request->getParsedBody();
        $mesa = Mesa::where('codigo', '=', $parametros['codigo'])->first();
        $estadoAnterior = $mesa->estado;

        if($data->rol != 'socio' || $mesa->estado != 'con cliente pagando'){
            $response->getBody()->write(json_encode(['mensaje' => 'Solo pueden cerrar la mesa los socios una vez que el cliente haya pagado']));
            return $response;
        }

        $mesa->estado = 'cerrada';
        
        try {
            $mesa->save();
        } catch (\Throwable $th) {
            $response->getBody()->write(json_encode(["mensaje" => "Ocurrió un error al tratar de cerrar la mesa"]));
            return $response->withStatus(500);
        }

        $logger = new LoggerService();
        $logger->logMesa($mesa->id, $data->id, $estadoAnterior, $mesa->estado);

        $response->getBody()->write(json_encode(["mensaje" => "Mesa cerrada"]));
        return $response;
    }

    public function encuesta(Request $request, Response $response)
    {
        $parametros = $request->getParsedBody();
        //Validar que no exista encuesta para esos códigos
        if($this->chequearExistenciaEncuesta($parametros)){
            $response->getBody()->write(json_encode(['mensaje' => 'La encuesta ya ha sido cargada']));
            return $response;
        }
        if(!$this->validarParametrosEncuesta($parametros)){
            $response->getBody()->write(json_encode(['mensaje' => 'Por favor ingrese puntajes del 1 al 10 y una reseña de no más de 66 caracteres']));
            return $response;
        }
        $encuesta = new Encuesta();
        $encuesta->codigo_mesa = $parametros['codigoMesa'];
        $encuesta->codigo_pedido = $parametros['codigoPedido'];
        $encuesta->puntos_mesa = $parametros['puntosMesa'];
        $encuesta->puntos_restaurant = $parametros['puntosRestaurante'];
        $encuesta->puntos_cocinero = $parametros['puntosCocinero'];
        $encuesta->puntos_mozo = $parametros['puntosMozo'];
        if(strlen($parametros['descripcion']) > 66){
            $encuesta->descripcion = substr($parametros['descripcion'],0,66);
        }else{
            $encuesta->descripcion = $parametros['descripcion'];
        }

        try {
            $encuesta->save();
        } catch (\Throwable $th) {
            $response->getBody()->write(json_encode(["mensaje" => "Ocurrió un error al tratar de guardar la encuesta"]));
            return $response->withStatus(500);
        }

        $response->getBody()->write(json_encode(["mensaje" => "Encuesta finalizada, muchas gracias!"]));
        return $response;
    }

    private function chequearExistenciaEncuesta($parametros){
        $encuesta = Encuesta::where('codigo_mesa', '=', $parametros['codigoMesa'])
            ->where('codigo_pedido', '=', $parametros['codigoPedido'])->first();
        if($encuesta){
            return true;
        }else{
            return false;
        }
    }

    private function validarParametrosEncuesta($parametros){

        return true;
    }
}
