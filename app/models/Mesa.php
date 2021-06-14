<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Mesa extends Model
{
    use SoftDeletes;
    
    public $timestamps = true;

    protected $fillable = [
        'codigo', 'numero', 'estado'
    ];

    const ESTADOS = ['con cliente esperando pedido', 'con cliente comiendo', 'con cliente pagando', 'cerrada'];
}