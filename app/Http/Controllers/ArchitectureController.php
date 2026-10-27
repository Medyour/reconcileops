<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class ArchitectureController extends Controller
{
    public function __invoke(): View
    {
        return view('architecture');
    }
}
