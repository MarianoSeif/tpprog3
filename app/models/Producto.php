<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Producto extends Model
{
    use SoftDeletes;
    
    public $timestamps = true;

    protected $fillable = [
        'nombre', 'codigo', 'tipo', 'precio'
    ];

    const TIPOS = ['barra', 'chopera', 'comida', 'candy'];

    public static function getTipo($codigo)
    {
        $producto = Producto::where('codigo', '=', $codigo)->first();
        return $producto->tipo;
    }
}