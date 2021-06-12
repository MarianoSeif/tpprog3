<?php

require_once './extras/fpdf/fpdf.php';

class CreatePdfService
{

    public function createPdf()
    {
        $pdf = new FPDF();
        $pdf->AddPage();
        $pdf->SetFont('Courier', '', 12);
        $pdf->Cell(0,0,'texto');
        $pdf->Output('F','./files/archivo.pdf');

        return true;
    }
}