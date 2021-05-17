<?php

class Mozo extends Usuario
{
    public function __construct($usuario = null, $clave = null)
    {
        parent::__construct('mozo', $usuario, $clave);
    }
}