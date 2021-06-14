<?php

error_reporting(-1);
ini_set('display_errors', 1);

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Factory\AppFactory;
use Slim\Routing\RouteCollectorProxy;
use Illuminate\Database\Capsule\Manager as Capsule;

require __DIR__ . '/../vendor/autoload.php';

require_once './middlewares/AuthenticationMiddleware.php';
require_once './middlewares/AuthorizationMiddleware.php';
require_once './middlewares/JsonMiddleware.php';

require_once './controllers/UsuarioController.php';
require_once './controllers/ProductoController.php';
require_once './controllers/MesaController.php';
require_once './controllers/PedidoController.php';
require_once './controllers/LoginController.php';
require_once './controllers/InformeController.php';

//Load .env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

// Instantiate App
$app = AppFactory::create();
$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();

// Add error middleware
$app->addErrorMiddleware(true, true, true);

// Eloquent
$container=$app->getContainer();

$capsule = new Capsule;
$capsule->addConnection([
    'driver'    => 'mysql',
    'host'      => $_ENV['MYSQL_HOST'],
    'database'  => $_ENV['MYSQL_DB'],
    'username'  => $_ENV['MYSQL_USER'],
    'password'  => $_ENV['MYSQL_PASS'],
    'charset'   => 'utf8',
    'collation' => 'utf8_unicode_ci',
    'prefix'    => '',
]);

$capsule->setAsGlobal();
$capsule->bootEloquent();

//Timezone
date_default_timezone_set('America/Argentina/Buenos_Aires');

// Routes
$app->post('/login', \LoginController::class . ':login')->add(JsonMiddleware::class . ':process');
$app->get('/test', \UsuarioController::class . ':test');
$app->get('/lacuenta/{codigo}', \MesaController::class . ':traerLaCuenta');
$app->post('/hacer/encuesta', \MesaController::class . ':encuesta');
$app->post('/cuantofalta', \PedidoController::class . ':cuantoFalta');

$app->group('/usuarios', function (RouteCollectorProxy $group) {
    $group->get('[/]', \UsuarioController::class . ':TraerTodos');
    $group->get('/{usuario}', \UsuarioController::class . ':TraerUno');
    $group->get('/rol/{rol}', \UsuarioController::class . ':TraerPorRol');
    $group->post('[/]', \UsuarioController::class . ':CargarUno');
    $group->put('[/{id}]', \UsuarioController::class . ':ModificarUno');
    $group->delete('[/{id}]', \UsuarioController::class . ':BorrarUno');
})->add(AuthorizationMiddleware::class . ':process')->add(AuthenticationMiddleware::class . ':process')->add(JsonMiddleware::class . ':process');

$app->group('/productos', function (RouteCollectorProxy $group) {
    $group->get('[/]', \ProductoController::class . ':TraerTodos');
    $group->get('/{codigo}', \ProductoController::class . ':TraerUno');
    $group->get('/tipo/{tipo}', \ProductoController::class . ':TraerPorTipo');
    $group->post('[/]', \ProductoController::class . ':CargarUno');
    $group->delete('[/{id}]', \ProductoController::class . ':BorrarUno');
    $group->post('/file', \ProductoController::class . ':CargarArchivo');
})->add(AuthorizationMiddleware::class . ':process')->add(AuthenticationMiddleware::class . ':process')->add(JsonMiddleware::class . ':process');

$app->group('/mesas', function (RouteCollectorProxy $group) {
    $group->get('[/]', \MesaController::class . ':TraerTodos');
    $group->get('/lacuenta/{codigo}', \MesaController::class . ':traerLaCuenta');
    $group->post('/cerrar/mesa', \MesaController::class . ':cerrarMesa');
    $group->get('/{codigo}', \MesaController::class . ':TraerUno');
    $group->post('[/]', \MesaController::class . ':CargarUno');
    $group->delete('[/{id}]', \MesaController::class . ':BorrarUno');
})->add(JsonMiddleware::class . ':process')->add(AuthenticationMiddleware::class . ':process');

$app->group('/pedidos', function (RouteCollectorProxy $group) {
    $group->get('[/]', \PedidoController::class . ':TraerTodos');
    $group->get('/pendientes', \PedidoController::class . ':pendientes');
    $group->get('/{codigo}', \PedidoController::class . ':TraerUno');
    $group->post('[/]', \PedidoController::class . ':CargarUno');
    $group->post('/cambiarestado', \PedidoController::class . ':cambiarEstado');
    $group->post('/item/cambiarestado', \PedidoController::class . ':cambiarEstadoItem');
    $group->post('/item/tomar', \PedidoController::class . ':tomarPedidoItem');
    $group->post('/item/servir', \PedidoController::class . ':servirPedidoItem');
})->add(AuthorizationMiddleware::class . ':process')->add(AuthenticationMiddleware::class . ':process')->add(JsonMiddleware::class . ':process');

$app->group('/informes', function (RouteCollectorProxy $group) {
    $group->get('/empleados/login', \InformeController::class . ':empleadosLogin');
    
})->add(AuthorizationMiddleware::class . ':process')->add(AuthenticationMiddleware::class . ':process')->add(JsonMiddleware::class . ':process');

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