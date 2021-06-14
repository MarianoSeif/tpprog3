<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Pedido extends Model
{
    use SoftDeletes;
    
    public $timestamps = true;

    protected $fillable = [
        'codigo', 'estado', 'mesa'
    ];

    const ESTADOS = ['recibido'];
}