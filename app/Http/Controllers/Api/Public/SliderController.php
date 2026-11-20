<?php

namespace App\Http\Controllers\Api\Public;

use App\Models\Slider;
use App\Http\Controllers\Controller;

class SliderController extends Controller
{
    public function index()
    {
        $sliders = Slider::latest()->get();

        return response()->json([
            'success' => true,
            'message' => 'List Slider',
            'sliders' => $sliders,
            'data'    => $sliders
        ]);
    }
}