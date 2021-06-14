<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LoginLogger extends Model
{
    use SoftDeletes;
    
    public $timestamps = true;
    
    protected $table = 'log_login';

    protected $fillable = [
        'empleado_id'
    ];

}