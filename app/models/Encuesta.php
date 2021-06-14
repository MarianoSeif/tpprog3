<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Encuesta extends Model
{
    use SoftDeletes;
    
    public $timestamps = true;

    protected $fillable = [
        'codigo_mesa', 'codigo_pedido', 'puntos_mesa', 'puntos_restaurant', 'puntos_cocinero', 'puntos_mozo', 'descripcion'
    ];

}