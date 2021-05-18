<?php

class Socio extends Usuario
{
    public function __construct($usuario = null, $clave = null)
    {
        parent::__construct('socio', $usuario, $clave);
    }
}