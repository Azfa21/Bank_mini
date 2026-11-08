<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class StudentLifecycleController extends Controller
{
    public function index(
        Request $request,
        ClassPromotionController $promotions,
        GraduationController $graduations,
    ) {
        if ($request->query('section') === 'graduations') {
            return $graduations->index();
        }

        return $promotions->index($request);
    }
}
