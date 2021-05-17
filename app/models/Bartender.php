<?php

class Bartender extends Usuario
{
    public function __construct($usuario = null, $clave = null)
    {
        parent::__construct($usuario, $clave, 'bartender');
    }
}