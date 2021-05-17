<?php

class Socio extends Usuario
{
    public function __construct($id, $usuario, $clave)
    {
        parent::__construct($id, $usuario, $clave, 'socio');
    }
}