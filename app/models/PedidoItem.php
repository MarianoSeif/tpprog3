<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class PedidoItem extends Model
{
    use SoftDeletes;
    
    public $timestamps = true;

    protected $fillable = [
        'codigo', 'estado', 'mesa'
    ];
}