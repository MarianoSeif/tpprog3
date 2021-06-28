<?php

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Illuminate\Database\Capsule\Manager as Capsule;

require_once './models/Loggers/LoginLogger.php';

class InformeController
{   
    public function empleadosLogin(Request $request, Response $response)
    {
        $queryParams = $request->getQueryParams();
        if(isset($queryParams['id']) && $queryParams['id'] != ''){
            $ingresos = LoginLogger::where('empleado_id', '=', $queryParams['id'])->get();
        }else{
            $ingresos = LoginLogger::all();
        }
        $response->getBody()->write(json_encode(['informe_ingresos'=>$ingresos]));
        return $response;
    }

    public function empleadosOperacionesPorSector(Request $request, Response $response)
    {
        $fechas = $this->validarYAcomodarFechas($request->getQueryParams());
        $resultados = Capsule::select(
            'SELECT 
                CASE 
                    WHEN u.rol = "bartender" THEN "barra"
                    WHEN u.rol = "cervecero" THEN "chopera"
                    WHEN u.rol = "cocinero" THEN "cocina"
                    WHEN u.rol = "mozo" THEN "mozos"
                END as Sector,
                COUNT(*) as "Cantidad de operaciones"
            FROM log_pedidoitems
            JOIN usuarios u ON empleado_id = u.id
            WHERE (log_pedidoitems.created_at BETWEEN ? AND ?)
            GROUP BY u.rol
            ORDER BY u.rol
            '
        , [$fechas['fechaInicio'], $fechas['fechaFin']]);
        $response->getBody()->write(json_encode(['informe_operaciones'=>$resultados]));
        return $response;
    }
    public function empleadosOperacionesPorSectorPorEmpleado(Request $request, Response $response)
    {
        $fechas = $this->validarYAcomodarFechas($request->getQueryParams());
        $resultados = Capsule::select(
            'SELECT
                u.id as "Id Usuario",
                u.usuario as "Usuario",
                CASE 
                    WHEN u.rol = "bartender" THEN "barra"
                    WHEN u.rol = "cervecero" THEN "chopera"
                    WHEN u.rol = "cocinero" THEN "cocina"
                    WHEN u.rol = "mozo" THEN "mozos"
                END as "Sector",
                COUNT(*) as "Cantidad de operaciones"
            FROM log_pedidoitems
            JOIN usuarios u ON empleado_id = u.id
            WHERE (log_pedidoitems.created_at BETWEEN ? AND ?)
            GROUP BY u.rol, u.usuario, u.id
            ORDER BY u.rol, u.usuario
            '
        , [$fechas['fechaInicio'], $fechas['fechaFin']]);
        $response->getBody()->write(json_encode(['informe_operaciones'=>$resultados]));
        return $response;
    }
    public function empleadosOperacionesPorEmpleado(Request $request, Response $response)
    {
        $queryParams = $request->getQueryParams();
        $fechas = $this->validarYAcomodarFechas($queryParams);

        if(isset($queryParams['id']) && $queryParams['id'] != ''){
            $resultados = Capsule::select(
                'SELECT
                    u.id as "Id Usuario",
                    u.usuario as "Usuario",
                    CASE 
                        WHEN u.rol = "bartender" THEN "barra"
                        WHEN u.rol = "cervecero" THEN "chopera"
                        WHEN u.rol = "cocinero" THEN "cocina"
                        WHEN u.rol = "mozo" THEN "mozos"
                    END as "Sector",
                    COUNT(*) as "Cantidad de operaciones"
                FROM log_pedidoitems
                JOIN usuarios u ON empleado_id = u.id
                WHERE empleado_id = ?
                AND (log_pedidoitems.created_at BETWEEN ? AND ?)
                GROUP BY u.rol, u.usuario, u.id
                ORDER BY u.rol, u.usuario
                '
            , [$queryParams['id'], $fechas['fechaInicio'], $fechas['fechaFin']]);
        }else{
            $resultados = Capsule::select(
                'SELECT
                    u.id as "Id Usuario",
                    u.usuario as "Usuario",
                    COUNT(*) as "Cantidad de operaciones"
                FROM log_pedidoitems
                JOIN usuarios u ON empleado_id = u.id
                WHERE (log_pedidoitems.created_at BETWEEN ? AND ?)
                GROUP BY u.usuario, u.id
                ORDER BY u.usuario
                '
            , [$fechas['fechaInicio'], $fechas['fechaFin']]);
        }
        $response->getBody()->write(json_encode(['informe_operaciones'=>$resultados]));
        return $response;
    }

    public function pedidosLoMasVendido(Request $request, Response $response)
    {
        $fechas = $this->validarYAcomodarFechas($request->getQueryParams());
        $resultados = Capsule::select(
            'SELECT
                i.codigo,
                p.nombre,
                SUM(cantidad) as Ventas
            FROM pedido_items i
            JOIN productos p ON i.codigo = p.codigo
            WHERE (i.created_at BETWEEN ? AND ?)
            GROUP BY i.codigo, p.nombre
            HAVING SUM(cantidad) = (SELECT
                                        SUM(cantidad) as TOTAL
                                    FROM pedido_items i
                                    WHERE (i.created_at BETWEEN ? AND ?)
                                    GROUP BY i.codigo
                                    ORDER BY TOTAL DESC
                                    LIMIT 1)
            '
        , [$fechas['fechaInicio'], $fechas['fechaFin'],$fechas['fechaInicio'], $fechas['fechaFin']]);
        $response->getBody()->write(json_encode(['informe pedidos'=>$resultados]));
        return $response;
    }
    public function pedidosLoMenosVendido(Request $request, Response $response)
    {
        $resultados = Capsule::select(
            'SELECT
                i.codigo,
                p.nombre,
                SUM(cantidad) as Ventas
            FROM pedido_items i
            JOIN productos p ON i.codigo = p.codigo
            GROUP BY i.codigo, p.nombre
            HAVING SUM(cantidad) = (SELECT
                                        SUM(cantidad) as TOTAL
                                    FROM pedido_items i
                                    GROUP BY i.codigo
                                    ORDER BY TOTAL ASC
                                    LIMIT 1)
            '
        );
        $response->getBody()->write(json_encode(['informe pedidos'=>$resultados]));
        return $response;
    }
    public function pedidosFueraDeTiempo(Request $request, Response $response)
    {
        $resultados = Capsule::select(
            'SELECT
                i.id,
                u.usuario as "Usuario",
                i.empleado_id as "Id Empleado",
                i.hora_de_salida as "Hora Estimada de Salida",
                l.created_at as "Hora Real de Salida"
            FROM pedido_items i
            JOIN log_pedidoitems l ON i.id = l.pedidoitem_id
            JOIN usuarios u ON i.empleado_id = u.id
            WHERE i.hora_de_salida < l.created_at
            AND l.estado_actual = "listo para servir"
            '
        );
        $response->getBody()->write(json_encode(['informe pedidos'=>$resultados]));
        return $response;
    }
    public function pedidosCancelados(Request $request, Response $response)
    {
        $pedidosCancelados = Pedido::where('estado', '=', 'cancelado')->get();
        $itemsCancelados = PedidoItem::where('estado', '=', 'cancelado')->get();
        
        $response->getBody()->write(json_encode([
            'Pedidos Cancelados'=>$pedidosCancelados,
            'Items cancelados'=>$itemsCancelados
        ]));
        return $response;
    }

    public function mesaMasUsada(Request $request, Response $response)
    {
        $resultados = Capsule::select(
            'SELECT
                numero as Mesa,
                COUNT(*) as Veces
            FROM mesas
            GROUP BY numero
            HAVING COUNT(*) = (SELECT
                                    COUNT(*) as Veces
                                    FROM mesas
                                    GROUP BY numero
                                    ORDER BY Veces DESC
                                    LIMIT 1)
            '
        );
        $response->getBody()->write(json_encode(['Mesa mas usada'=>$resultados]));
        return $response;
    }
    public function mesaMenosUsada(Request $request, Response $response)
    {
        $resultados = Capsule::select(
            'SELECT
                numero as Mesa,
                COUNT(*) as Veces
            FROM mesas
            GROUP BY numero
            HAVING COUNT(*) = (SELECT
                                    COUNT(*) as Veces
                                    FROM mesas
                                    GROUP BY numero
                                    ORDER BY Veces ASC
                                    LIMIT 1)
            '
        );
        $response->getBody()->write(json_encode(['Mesa menos usada'=>$resultados]));
        return $response;
    }
    public function mesaMasFacturo(Request $request, Response $response)
    {
        $resultados = Capsule::select(
            'SELECT
                m.numero as Mesa,
                SUM(p.total) as "Total Facturado"
            FROM pedidos p
            JOIN mesas m ON p.mesa = m.codigo
            WHERE p.total IS NOT NULL
            GROUP BY m.numero
            ORDER BY SUM(p.total) DESC
            LIMIT 1
            '
        );
        $response->getBody()->write(json_encode(['Mesa que mas facturó'=>$resultados]));
        return $response;
    }
    public function mesaMenosFacturo(Request $request, Response $response)
    {
        $resultados = Capsule::select(
            'SELECT
                m.numero as Mesa,
                SUM(p.total) as "Total Facturado"
            FROM pedidos p
            JOIN mesas m ON p.mesa = m.codigo
            WHERE p.total IS NOT NULL
            GROUP BY m.numero
            ORDER BY SUM(p.total) ASC
            LIMIT 1
            '
        );
        $response->getBody()->write(json_encode(['Mesa que menos facturó'=>$resultados]));
        return $response;
    }
    public function mesaFacturaMayorImporte(Request $request, Response $response)
    {
        $resultados = Capsule::select(
            'SELECT
                m.numero as Mesa,
                p.total as "Factura Mayor Importe"
            FROM pedidos p
            JOIN mesas m ON p.mesa = m.codigo
            WHERE p.total IS NOT NULL
            ORDER BY p.total DESC
            LIMIT 1
            '
        );
        $response->getBody()->write(json_encode(['Mesa Factura mayor'=>$resultados]));
        return $response;
    }
    public function mesaFacturaMenorImporte(Request $request, Response $response)
    {
        $resultados = Capsule::select(
            'SELECT
                m.numero as Mesa,
                p.total as "Factura Mayor Importe"
            FROM pedidos p
            JOIN mesas m ON p.mesa = m.codigo
            WHERE p.total IS NOT NULL
            ORDER BY p.total ASC
            LIMIT 1
            '
        );
        $response->getBody()->write(json_encode(['Mesa factura menor'=>$resultados]));
        return $response;
    }
    public function mesaFacturacionEntreFechas(Request $request, Response $response)
    {
        $queryParams = $request->getQueryParams();
        if(isset($queryParams['mesaNumero']) && $queryParams['mesaNumero'] != ''
            && isset($queryParams['fechaInicio']) && $queryParams['fechaInicio'] != ''
            && isset($queryParams['fechaFin']) && $queryParams['fechaFin'] != ''
        ){
            $mesa = $queryParams['mesaNumero'];
            $fechaInicio = $queryParams['fechaInicio'];
            $fechaFin = $queryParams['fechaFin'];
        
            $resultados = Capsule::select(
                'SELECT
                    m.numero as Mesa,
                    SUM(p.total) as "Total Facturado"
                FROM pedidos p
                JOIN mesas m ON p.mesa = m.codigo
                WHERE p.total IS NOT NULL
                AND m.numero = ?
                AND (p.created_at BETWEEN ? AND ?)
                GROUP BY m.numero
                '
            , [$mesa, $fechaInicio, $fechaFin]);
            $response->getBody()->write(json_encode(['Datos facturación'=>$resultados]));
        }else{
            $response->getBody()->write(json_encode(['Mensaje'=>'Faltan datos']));
            $response = $response->withStatus(400);
        }
        return $response;
    }

    public function mesaMejoresComentarios(Request $request, Response $response)
    {
        $resultados = Capsule::select(
            'SELECT
                codigo_mesa as Mesa,
                descripcion as Comentario
            FROM encuestas
            WHERE puntos_mesa >= 7
            ORDER BY puntos_mesa DESC
            '
        );
        $response->getBody()->write(json_encode(['Mejores Comentarios'=>$resultados]));
        return $response;
    }
    public function mesaPeoresComentarios(Request $request, Response $response)
    {
        $resultados = Capsule::select(
            'SELECT
                codigo_mesa as Mesa,
                descripcion as Comentario
            FROM encuestas
            WHERE puntos_mesa <= 4
            ORDER BY puntos_mesa DESC
            '
        );
        $response->getBody()->write(json_encode(['Peores Comentarios'=>$resultados]));
        return $response;
    }

    private function validarYAcomodarFechas($queryParams)
    {
        $fechas = [];
        if(isset($queryParams['fechaInicio']) && $queryParams['fechaInicio'] != ''){
            $fechas['fechaInicio'] = $queryParams['fechaInicio'].' 00:00:00';
        }else{
            $fechas['fechaInicio'] = '1900-01-01 00:00:00';
        }
        
        if(isset($queryParams['fechaFin']) && $queryParams['fechaFin'] != ''){
            $fechas['fechaFin'] = $queryParams['fechaFin'].' 23:59:59';
        }else{
            $fechas['fechaFin'] = date('Y-m-d').' 23:59:59';
        }
        return $fechas;
    }
    
}
