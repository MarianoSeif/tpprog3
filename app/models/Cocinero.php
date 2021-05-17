<?php

class Cocinero extends Usuario
{
    public function __construct($usuario = null, $clave = null)
    {
        parent::__construct($usuario, $clave, 'cocinero');
    }
}