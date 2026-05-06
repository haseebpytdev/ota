<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class RequestDemoController extends Controller
{
    public function __invoke(): View
    {
        return view('frontend.request-demo', [
            'client' => config('demo-client', []),
            'modules' => config('demo-request-demo.modules', []),
        ]);
    }
}
