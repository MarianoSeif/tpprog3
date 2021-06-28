<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MesaLogger extends Model
{
    use SoftDeletes;
    
    public $timestamps = true;
    
    protected $table = 'log_mesas';

    protected $fillable = [
        'mesa_id', 'empleado_id', 'estado_anterior', 'estado_actual'
    ];
}