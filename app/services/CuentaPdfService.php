<?php

require_once './extras/fpdf/fpdf.php';

class CuentaPdfService
{
    private $documento;

    public function __construct()
    {
        $this->documento = new FPDF();
        $this->documento->AddPage();
        $this->documento->SetFont('Courier', '', 12);
    }

    public function createPdf($titulo, $contenido, $nombreArchivo)
    {
        $this->documento->Write(5, $titulo);
        $this->documento->Ln();

        foreach ($contenido as $linea) {
            $this->documento->Write(5, $linea);
            $this->documento->Ln();
        }        
        $this->close($nombreArchivo);
    }

    private function close($nombreArchivo)
    {
        //$this->documento->Output('F','./files/'.$nombreArchivo.'.pdf');
        $this->documento->Output('D','./files/'.$nombreArchivo.'.pdf');
    }

}