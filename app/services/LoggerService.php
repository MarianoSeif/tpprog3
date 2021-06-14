<?php

require_once './models/Loggers/PedidoLogger.php';
require_once './models/Loggers/PedidoItemLogger.php';

class LoggerService
{
    public function logPedido($pedidoId, $empleadoId, $estadoAnterior, $estadoActual)
    {
        $pedidoLogger = new PedidoLogger();
        $pedidoLogger->pedido_id = $pedidoId;
        $pedidoLogger->empleado_id = $empleadoId;
        $pedidoLogger->estado_anterior = $estadoAnterior;
        $pedidoLogger->estado_actual = $estadoActual;
        
        try {
            $pedidoLogger->save();
        } catch (\Throwable $th) {
            var_dump($th);
            //Falló el registro en el log. Loguear en otro log.
        }
    }

    public function logPedidoItem($pedidoItemId, $empleadoId, $estadoAnterior, $estadoActual)
    {
        $pedidoLogger = new PedidoItemLogger();
        $pedidoLogger->pedidoitem_id = $pedidoItemId;
        $pedidoLogger->empleado_id = $empleadoId;
        $pedidoLogger->estado_anterior = $estadoAnterior;
        $pedidoLogger->estado_actual = $estadoActual;
        
        try {
            $pedidoLogger->save();
        } catch (\Throwable $th) {
            var_dump($th);
            //Falló el registro en el log. Loguear en otro log.
        }
    }
}