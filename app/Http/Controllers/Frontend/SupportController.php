<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class SupportController extends Controller
{
    public function show(): View
    {
        return view('frontend.support');
    }
}
