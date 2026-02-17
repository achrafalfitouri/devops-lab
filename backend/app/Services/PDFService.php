<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;

class PDFService
{
    public function generatePDF(string $view, array $data): \Barryvdh\DomPDF\PDF
    {
        return Pdf::loadView($view, $data);
    }
}
