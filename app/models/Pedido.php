<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Pedido extends Model
{
    use SoftDeletes;
    
    public $timestamps = true;

    protected $fillable = [
        'codigo', 'estado', 'mesa', 'total', 'imagen'
    ];

    const ESTADOS = ['recibido', 'en preparacion', 'servido parcial', 'servido'];
}