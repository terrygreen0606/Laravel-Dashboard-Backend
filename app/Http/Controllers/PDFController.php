<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PDFController extends Controller
{
    public function getFooter(Request $request)
    {
        $query = $request->all();
        return view('documents.pdf-templates.partials.footer-1', compact('query'));
    }
}
