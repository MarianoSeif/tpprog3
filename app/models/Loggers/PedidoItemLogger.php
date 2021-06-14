<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PedidoItemLogger extends Model
{
    use SoftDeletes;
    
    public $timestamps = true;
    
    protected $table = 'log_pedidoitems';

    protected $fillable = [
        'pedidoitem_id', 'empleado_id', 'estado_anterior', 'estado_actual'
    ];
}