<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\App;

class PdfController extends Controller
{
    public function index() {
        $pdf = PDF::loadView('index.pdf')->setPaper('a4', 'landscape')->setWarnings(false);
        return $pdf->download('invoice.pdf');
    }
    public function pdf() {
        $pdf = App::make('dompdf.wrapper');
        $pdf->loadView('index.pdf');
        return $pdf->stream();
    }

    public function view() {
        return view('textDoc');
    }
}
