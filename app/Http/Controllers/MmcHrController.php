<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class MmcHrController extends Controller
{
    public function index(): View
    {
        return view('hr.index');
    }
}
