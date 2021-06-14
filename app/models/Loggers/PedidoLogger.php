<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PedidoLogger extends Model
{
    use SoftDeletes;
    
    public $timestamps = true;
    
    protected $table = 'log_pedidos';

    protected $fillable = [
        'pedido_id', 'empleado_id', 'estado_anterior', 'estado_actual'
    ];
}