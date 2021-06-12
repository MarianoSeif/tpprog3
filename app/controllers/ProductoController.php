<?php

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Psr7\UploadedFile;

require_once './models/Producto.php';

class ProductoController
{
    public function CargarUno($request, $response, $args)
    {
        $parametros = $request->getParsedBody();

        $nombre = $parametros['nombre'];
        $codigo = $parametros['codigo'];
        $tipo = $parametros['tipo'];
        $precio = $parametros['precio'];

        if(!in_array($tipo, Producto::TIPOS)){
            $response
                ->getBody()
                ->write(json_encode(["mensaje" => "Opcion invalida. Los productos a crear son: 'bebida' y 'comida'"]));
            return $response
                ->withStatus(400);
        }
        
        //Buscar producto en la db
        $productos = Producto::where('nombre', '=', $nombre)->get();

        if(count($productos) > 0){
            $response
                ->getBody()
                ->write(json_encode(["mensaje" => "El producto ya existe"]));
            return $response
                ->withStatus(400);
        }

        $producto = new Producto();
        $producto->nombre = $nombre;
        $producto->codigo = $codigo;
        $producto->tipo = $tipo;
        $producto->precio = $precio;
        try {
            $producto->save();
        } catch (\Throwable $th) {
            $response
                ->getBody()
                ->write(json_encode(["mensaje" => "Ocurrió un error al tratar de guardar el producto en la base de datos"]));
            return $response
                ->withStatus(400);
        }

        $response
            ->getBody()
            ->write(json_encode(["mensaje" => "Producto creado con exito"]));
        return $response
            ->withStatus(200);
    }

    public function TraerUno($request, $response, $args)
    {
        // Buscamos producto por codigo
        $producto = Producto::where('codigo', '=', $args['codigo'])->first();
        if(!$producto){
            $response->getBody()->write(json_encode(["mensaje"=>"El producto no existe"]));
            return $response->withStatus(400);
        }else{
            $response->getBody()->write(json_encode(["producto"=>$producto]));
            return $response;
        }
    }

    public function TraerTodos($request, $response, $args)
    {
        $productos = Producto::all();
        $response->getBody()->write(json_encode(["listaProductos" => $productos]));
        return $response;
    }

    public function TraerPorTipo($request, $response, $args)
    {
        if(!in_array($args['tipo'], Producto::TIPOS)){
            $response->getBody()->write(json_encode(["mensaje"=>"Ese tipo de producto no existe"]));
            return $response->withStatus(400);
        }
        
        $productos = Producto::where('tipo', '=', ($args['tipo']))->get();
        $response->getBody()->write(json_encode($productos));
        return $response;
    }

    public function ModificarUno($request, $response, $args)
    {
        $parametros = $request->getParsedBody();

        $nombre = $parametros['nombre'];
        Producto::modificarProducto($nombre);

        $payload = json_encode(array("mensaje" => "Producto modificado con exito"));

        $response->getBody()->write($payload);
        return $response
          ->withHeader('Content-Type', 'application/json');
    }

    public function BorrarUno($request, $response, $args)
    {
        if(Producto::destroy($args['id'])){
            $response->getBody()->write(json_encode(["mensaje"=>"El producto fue eliminado con éxito"]));
        }else{
            $response->getBody()->write(json_encode(["mensaje"=>"No se encontró el producto"]));
        }
        return $response;
    }

    public function CargarArchivo(Request $request, Response $response)
    {
        $files = $request->getUploadedFiles();
        if(isset($files['archivo'])){
            $uploadedFile = $files['archivo'];
            
            $filas = str_getcsv($uploadedFile->getStream(), "\n");

            foreach ($filas as $fila) {
                $arr_csv = str_getcsv($fila);
                $producto = new Producto();
                $producto->nombre = $arr_csv[0];
                $producto->codigo = $arr_csv[1];
                $producto->tipo = $arr_csv[2];
                $producto->precio = $arr_csv[3];
                $producto->save();
            }
            $response->getBody()->write(json_encode(["mensaje"=>"Se cargaron los productos"]));
        }
        
        return $response;
    }
}
