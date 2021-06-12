<?php

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

require_once './models/Usuario.php';
require_once './services/CreatePdfService.php';

class UsuarioController
{   
    public function crear(Request $request, Response $response)
    {
        $parametros = $request->getParsedBody();

        $usuario = $parametros['usuario'];
        $clave = $parametros['clave'];
        $rol = $parametros['rol'];

        //Validar Rol
        $this->validarRol($rol, $response);

        //Buscar nombre de ususario en la db
        $users = Usuario::where('usuario', '=', $usuario)->get();

        if(count($users) > 0){
            $response
                ->getBody()
                ->write(json_encode(["mensaje" => "El nombre de usuario ya existe"]));
            return $response
                ->withStatus(400);
        }
        
        //validar pass
        $this->validarPass($clave, $response);

        // Creamos el usuario
        $usr = new Usuario();
        $usr->usuario = $usuario;
        $claveHash = password_hash($clave, PASSWORD_DEFAULT);
        $usr->clave = $claveHash;
        $usr->rol = $rol;

        try {
            $usr->save();
        } catch (\Throwable $th) {
            $response
                ->getBody()
                ->write(json_encode(["mensaje" => "Ocurrió un error al tratar de guardar el usuario en la base de datos"]));
            return $response
                ->withStatus(400);
        }
        
        $response
            ->getBody()
            ->write(json_encode(["mensaje" => "Usuario creado con exito"]));
        return $response
            ->withStatus(200);
    }
    
    public function TraerUno(Response $response, array $args)
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

    public function TraerTodos(Response $response, array $args)
    {
        $usuarios = Usuario::all();
        $response->getBody()->write(json_encode(["listaUsuarios" => $usuarios]));
        return $response;
    }

    public function TraerPorRol(Response $response, array $args)
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
            return $response
                ->withStatus(200);
        }

        $response
                ->getBody()
                ->write(json_encode(["mensaje" => "Usuario no encontrado"]));
            return $response
                ->withStatus(200);
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
