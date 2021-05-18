<?php

class Cliente extends Usuario
{
    public function __construct($usuario = null, $clave = null)
    {
        parent::__construct('cliente', $usuario, $clave);
    }
}