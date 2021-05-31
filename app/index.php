<?php

error_reporting(-1);
ini_set('display_errors', 1);

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Factory\AppFactory;
use Slim\Routing\RouteCollectorProxy;
use Slim\Routing\RouteContext;

require __DIR__ . '/../vendor/autoload.php';

require_once './db/AccesoDatos.php';
require_once './middlewares/AuthenticationMiddleware.php';
require_once './middlewares/AuthorizationMiddleware.php';

require_once './controllers/UsuarioController.php';
require_once './controllers/ProductoController.php';
require_once './controllers/MesaController.php';
require_once './controllers/PedidoController.php';
require_once './controllers/LoginController.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

// Instantiate App
$app = AppFactory::create();

$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();
// Add error middleware
$app->addErrorMiddleware(true, true, true);

//Timezone
date_default_timezone_set('America/Argentina/Buenos_Aires');

// Routes
$app->post('[/login]', \LoginController::class . ':login');

$app->group('/usuarios', function (RouteCollectorProxy $group) {
    $group->get('[/]', \UsuarioController::class . ':TraerTodos');
    $group->get('/{usuario}', \UsuarioController::class . ':TraerUno');
    $group->get('/rol/{rol}', \UsuarioController::class . ':TraerPorRol');
    $group->post('[/]', \UsuarioController::class . ':CargarUno');
})->add(AuthorizationMiddleware::class . ':process')->add(AuthenticationMiddleware::class . ':process');

$app->group('/productos', function (RouteCollectorProxy $group) {
    $group->get('[/]', \ProductoController::class . ':TraerTodos');
    $group->get('/{codigo}', \ProductoController::class . ':TraerUno');
    $group->post('[/]', \ProductoController::class . ':CargarUno');
})->add(AuthenticationMiddleware::class . ':process');

$app->group('/mesas', function (RouteCollectorProxy $group) {
    $group->get('[/]', \MesaController::class . ':TraerTodos');
    $group->get('/{codigo}', \MesaController::class . ':TraerUno');
    $group->post('[/]', \MesaController::class . ':CargarUno');
})->add(AuthenticationMiddleware::class . ':process');

$app->group('/pedidos', function (RouteCollectorProxy $group) {
    $group->get('[/]', \PedidoController::class . ':TraerTodos');
    $group->get('/{codigo}', \PedidoController::class . ':TraerUno');
    $group->post('[/]', \PedidoController::class . ':CargarUno');
    $group->post('/cambiarestado', \PedidoController::class . ':cambiarEstado');
})->add(AuthorizationMiddleware::class . ':process')->add(AuthenticationMiddleware::class . ':process');

$app->get('[/]', function (Request $request, Response $response) {
    $response->getBody()->write("Slim Framework 4 PHP");
    return $response;
});

$app->run();

/* 
//Ejercicio 1 10-5-21
$app->group('/credenciales', function (RouteCollectorProxy $group) {
 
  $group->get('/', function (Request $request, Response $response) {
    $response->getBody()->write('API => GET');
    return $response;
  });

  $group->post('/', function (Request $request, Response $response) {
    $response->getBody()->write('API => POST');
    return $response;
  });
     
})->add(MW_ejercicio1::class . ':verificarVerboYCredenciales');

//Ejercicio 2 10-5-21
$app->group('/json', function (RouteCollectorProxy $group) {
 
  $group->get('/', function (Request $request, Response $response) {
    $data = ['mensaje' => 'API => GET'];
    $payload = json_encode($data);
    $response->getBody()->write($payload);
    return $response->withHeader('Content-Type', 'application/json');
  });

  $group->post('/', function (Request $request, Response $response) {
    $data = ["mensaje" => "API => POST"];
    $payload = json_encode($data);
    $response->getBody()->write($payload);
    return $response->withHeader('Content-Type', 'application/json');
  });
     
})->add(MW_ejercicio1::class . ':verificarVerboYCredencialesJson');
 */