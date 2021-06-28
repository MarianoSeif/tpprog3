<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UsuarioLogger extends Model
{
    use SoftDeletes;
    
    public $timestamps = true;
    
    protected $table = 'log_usuarios';

    protected $fillable = [
        'empleado_id', 'estado_anterior', 'estado_atual'
    ];

}