<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class SupportController extends Controller
{
    public function support(): View
    {
        return view('frontend.support');
    }

    public function about(): View
    {
        return view('frontend.about');
    }
}
