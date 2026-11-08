<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MemberDataController extends Controller
{
    public function index(Request $request, MemberController $members, SaverController $savers)
    {
        return $request->query('type') === 'savers'
            ? $savers->index()
            : $members->index();
    }
}
