<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class PedidoItem extends Model
{
    use SoftDeletes;
    
    public $timestamps = true;

    protected $fillable = [
        'codigo', 'cantidad', 'tipo', 'empleado_id', 'hora_de_salida', 'estado'
    ];

    const ESTADOS = ['pendiente', 'en preparacion', 'listo', 'servido'];
}