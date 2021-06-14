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
class MesaController implements IApiUsable
{
    public function CargarUno($request, $response, $args)
    {
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
                ->withStatus(400);
        }
        
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
        if(Mesa::destroy($args['id'])){
            $response->getBody()->write(json_encode(["mensaje"=>"La mesa fue eliminada con éxito"]));
        }else{
            $response->getBody()->write(json_encode(["mensaje"=>"No se encontró la mesa"]));
        }
        return $response;
    }

    public function traerLaCuenta(Request $request, Response $response, array $args)
    {
        try {
            $contenidoCuenta = [];
            $total = 0.00;
            $mesa = Mesa::where('codigo', '=', $args['codigo'])->first();

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
            
            array_push($contenidoCuenta, 'Total: $'.strval($total));
            
            $cuentaService = new CuentaPdfService();
            $nombreArchivo = 'Ticket_'.$mesa->codigo.'_'.$pedido->codigo.'_'.date('d-m-Y');
            $cuentaService->createPdf('Restaurant La comanda', $contenidoCuenta, $nombreArchivo);

            $mesa->estado = 'con cliente pagando';
            $mesa->save();
            $response->getBody()->write(json_encode(['mensaje'=>'Se enviala cuenta']));
            return $response;
        } catch (\Throwable $th) {
            $response->getBody()->write(json_encode(['mensaje'=>'Error generando la cuenta']));
            return $response->withStatus(500);
        }   
    }

    public function cerrarMesa(Request $request, Response $response)
    {
        $data = AutentificadorJWT::ObtenerData($request->getAttribute('token'));
        
        $parametros = $request->getParsedBody();
        $mesa = Mesa::where('codigo', '=', $parametros['codigo'])->first();
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

        $response->getBody()->write(json_encode(["mensaje" => "Mesa cerrada"]));
        return $response;
    }

    public function encuesta(Request $request, Response $response)
    {
        $parametros = $request->getParsedBody();
        $encuesta = new Encuesta();
        //Validar que no exista encuesta para esos códigos
        if(!$this->validarParametrosEncuesta($parametros)){
            $response->getBody()->write(json_encode(['mensaje' => 'Por favor ingrese puntajes del 1 al 10 y una reseña de no más de 66 caracteres']));
            return $response;
        }
        $encuesta->codigo_mesa = $parametros['codigoMesa'];
        $encuesta->codigo_pedido = $parametros['codigoPedido'];
        $encuesta->puntos_mesa = $parametros['puntosMesa'];
        $encuesta->puntos_restaurant = $parametros['puntosRestaurante'];
        $encuesta->puntos_cocinero = $parametros['puntosCocinero'];
        $encuesta->puntos_mozo = $parametros['puntosMozo'];
        $encuesta->descripcion = $parametros['descripcion'];

        try {
            $encuesta->save();
        } catch (\Throwable $th) {
            $response->getBody()->write(json_encode(["mensaje" => "Ocurrió un error al tratar de guardar la encuesta"]));
            return $response->withStatus(500);
        }

        $response->getBody()->write(json_encode(["mensaje" => "Encuesta finalizada, muchas gracias!"]));
        return $response;
    }

    private function validarParametrosEncuesta($parametros){
        return true;
    }
}
