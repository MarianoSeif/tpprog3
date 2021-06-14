<?php

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

require_once './models/Loggers/LoginLogger.php';

class InformeController
{   
    public function empleadosLogin(Request $request, Response $response)
    {
        $ingresos = LoginLogger::all();
        $response->getBody()->write(json_encode(['informe_ingresos'=>$ingresos]));
        return $response;
    }

    public function empleadosOperacionesPorSector(Request $request, Response $response)
    {
        
    }
    public function empleadosOperacionesPorSectorPorEmpleado(Request $request, Response $response)
    {

    }
    public function empleadosOperacionesPorEmpleado(Request $request, Response $response)
    {

    }

    public function pedidosLoMasVendido(Request $request, Response $response)
    {
        
    }
    public function pedidosLoMenosVendido(Request $request, Response $response)
    {

    }
    public function pedidosFueraDeTiempo(Request $request, Response $response)
    {

    }
    public function pedidosCancelados(Request $request, Response $response)
    {

    }
    
    public function TraerUno(Request $request, Response $response, array $args)
    {
        // Buscamos usuario por nombre
        $user = Usuario::where('usuario', '=', $args['usuario'])->first();
        if(!$user){
            $response->getBody()->write(json_encode(["mensaje"=>"El usuario no existe"]));
            return $response->withStatus(400);
        }else{
            $response->getBody()->write(json_encode(["usuario"=>$user]));
            return $response;
        }
    }

    public function TraerTodos(Request $request, Response $response, array $args)
    {
        $usuarios = Usuario::all();
        $response->getBody()->write(json_encode(["listaUsuarios" => $usuarios]));
        return $response;
    }

    public function TraerPorRol(Request $request, Response $response, array $args)
    {
        if(!in_array($args['rol'], Usuario::ROLES)){
            $response->getBody()->write(json_encode(["mensaje"=>"Ese rol no existe"]));
            return $response->withStatus(400);
        }
        
        $usuarios = Usuario::where('rol', '=', ($args['rol']))->get();
        $response->getBody()->write(json_encode($usuarios));
        return $response;
    }
    
    public function ModificarUno(Request $request, Response $response)
    {
        //Recibe los parámetros pasados en el body por x-www-form-urlencoded
        $parametros = $request->getParsedBody();

        if(!isset($parametros['id'])){
            $response->getBody()->write(json_encode(["mensaje"=>"ingrese el id del usuario a modificar"]));
            return $response;
        }else{
            $usr = Usuario::find($parametros['id']);
        }

        if($usr){
            if(isset($parametros['clave'])){
                if(!$this->validarPass($parametros['clave'], $response)){
                    return $response->withStatus(400);
                }
                $usr->clave = password_hash($parametros['clave'], PASSWORD_DEFAULT);
            }
            if(isset($parametros['rol'])){
                if(!$this->validarRol($parametros['rol'], $response)){
                    return $response->withStatus(400);
                }
                $usr->rol = $parametros['rol'];
            }
            if(isset($parametros['estado'])){
                if(!$this->validarEstado($parametros['estado'], $response)){
                    return $response->withStatus(400);
                }
                $usr->estado = $parametros['estado'];
            }

            try {
                $usr->save();
            } catch (\Throwable $th) {
                $response
                    ->getBody()
                    ->write(json_encode(["mensaje" => "Ocurrió un error al tratar de guardar el usuario en la base de datos"]));
                return $response
                    ->withStatus(500);
            }
            
            $response
                ->getBody()
                ->write(json_encode(["mensaje" => "Usuario modificado con exito"]));
            return $response;
        }

        $response
                ->getBody()
                ->write(json_encode(["mensaje" => "Usuario no encontrado"]));
            return $response;
    }

    public function BorrarUno(Response $response, array $args)
    {
        if(Usuario::destroy($args['id'])){
            $response->getBody()->write(json_encode(["mensaje"=>"El usuario fue eliminado con éxito"]));
        }else{
            $response->getBody()->write(json_encode(["mensaje"=>"No se encontró el usuario"]));
        }
        return $response;
    }

    private function validarPass(string $clave, Response $response)
    {
        if(strlen($clave) < 8){
            $response
                ->getBody()
                ->write(json_encode(["mensaje" => "El password debe tener por lo menos 8 caracteres"]));
            return false;
        }
        return true;
    }

    private function validarRol(string $rol, Response $response)
    {
        if(!in_array($rol, Usuario::ROLES)){
            $response
                ->getBody()
                ->write(json_encode(["mensaje" => "Opcion invalida. Los usuarios a crear son: 'cliente', 'mozo', 'bartender', 'cervecero', 'cocinero' y 'socio'"]));    
            return false;
        }
        return true;
    }
    
    private function validarEstado(string $estado, Response $response)
    {
        if(!in_array($estado, Usuario::ESTADOS)){
            $response
                ->getBody()
                ->write(json_encode(["mensaje" => "Opcion invalida. Los estados son: 'activo', 'licencia', 'suspendido', 'despedido'"]));    
            return false;
        }
        return true;
    }

    public function test(Request $request, Response $response, array $args)
    {
        
        return $response;
    }

}
