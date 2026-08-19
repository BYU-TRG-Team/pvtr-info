<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class ComplaintController extends Controller
{
    public function create(): View
    {
        return view('complaints.create');
    }
}
