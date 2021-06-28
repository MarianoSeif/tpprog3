<?php

require_once './models/Loggers/PedidoLogger.php';
require_once './models/Loggers/PedidoItemLogger.php';
require_once './models/Loggers/MesaLogger.php';
require_once './models/Loggers/UsuarioLogger.php';

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
            //Falló el registro en el log. Loguear en archivo
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
            //Falló el registro en el log. Loguear en archivo
        }
    }

    public function logMesa($mesaId, $empleadoId, $estadoAnterior, $estadoActual)
    {
        $mesaLogger = new MesaLogger();
        $mesaLogger->mesa_id = $mesaId;
        $mesaLogger->empleado_id = $empleadoId;
        $mesaLogger->estado_anterior = $estadoAnterior;
        $mesaLogger->estado_actual = $estadoActual;
        
        try {
            $mesaLogger->save();
        } catch (\Throwable $th) {
            var_dump($th);
            //Falló el registro en el log. Loguear en archivo
        }
    }

    public function logUsuario($empleadoId, $estadoAnterior, $estadoActual)
    {
        $usuarioLogger = new UsuarioLogger();
        $usuarioLogger->empleado_id = $empleadoId;
        $usuarioLogger->estado_anterior = $estadoAnterior;
        $usuarioLogger->estado_actual = $estadoActual;
        
        try {
            $usuarioLogger->save();
        } catch (\Throwable $th) {
            var_dump($th);
            //Falló el registro en el log. Loguear en archivo
        }
    }
}