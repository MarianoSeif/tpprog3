<?php

require_once './models/Usuario.php';
require_once './models/Mozo.php';
require_once './models/Cervecero.php';
require_once './models/Bartender.php';
require_once './models/Socio.php';
require_once './models/Cocinero.php';
require_once './interfaces/IApiUsable.php';

class UsuarioController implements IApiUsable
{
    public function CargarUno($request, $response, $args)
    {
        $parametros = $request->getParsedBody();

        $usuario = $parametros['usuario'];
        $clave = $parametros['clave'];
        $rol = $parametros['rol'];

        switch ($rol) {
          case 'mozo':
            $clase = 'Mozo';
            break;
          case 'bartender':
            $clase = 'Bartender';
            break;
          case 'cervecero':
            $clase = 'Cervecero';
            break;
          case 'cocinero':
            $clase = 'Cocinero';
            break;
          case 'socio':
            $clase = 'Socio';
            break;
          case 'cliente':
            $clase = 'Cliente';
            break;

          default:
            $payload = json_encode(array("mensaje" => "Opcion invalida. Los usuarios a crear son: 'cliente', 'mozo', 'bartender', 'cervecero' y 'socio'"));

            $response->getBody()->write($payload);
            return $response
              ->withHeader('Content-Type', 'application/json');
            break;
        }

        // Creamos el usuario
        $usr = new $clase();
        $usr->usuario = $usuario;
        $usr->clave = $clave;
        $usr->crearUsuario();

        $payload = json_encode(array("mensaje" => "Usuario creado con exito"));

        $response->getBody()->write($payload);
        return $response
          ->withHeader('Content-Type', 'application/json');
    }

    public function TraerUno($request, $response, $args)
    {
        // Buscamos usuario por nombre
        $usr = $args['usuario'];
        $usuario = Usuario::obtenerUsuario($usr);
        $payload = json_encode($usuario);

        $response->getBody()->write($payload);
        return $response
          ->withHeader('Content-Type', 'application/json');
    }

    public function TraerTodos($request, $response, $args)
    {
        $lista = Usuario::obtenerTodos();
        $payload = json_encode(array("listaUsuario" => $lista));

        $response->getBody()->write($payload);
        return $response
          ->withHeader('Content-Type', 'application/json');
    }

    public function TraerPorRol($request, $response, $args)
    {        
        $lista = Usuario::obtenerPorRol($args['rol']);

        $payload = json_encode(array("listaUsuario" => $lista));

        $response->getBody()->write($payload);
        return $response
          ->withHeader('Content-Type', 'application/json');
    }
    
    public function ModificarUno($request, $response, $args)
    {
        $parametros = $request->getParsedBody();

        $nombre = $parametros['nombre'];
        Usuario::modificarUsuario($nombre);

        $payload = json_encode(array("mensaje" => "Usuario modificado con exito"));

        $response->getBody()->write($payload);
        return $response
          ->withHeader('Content-Type', 'application/json');
    }

    public function BorrarUno($request, $response, $args)
    {
        $parametros = $request->getParsedBody();

        $usuarioId = $parametros['usuarioId'];
        Usuario::borrarUsuario($usuarioId);

        $payload = json_encode(array("mensaje" => "Usuario borrado con exito"));

        $response->getBody()->write($payload);
        return $response
          ->withHeader('Content-Type', 'application/json');
    }
}
