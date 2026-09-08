<?php

namespace App\Http\Controllers\Persediaan;

use App\Http\Controllers\Controller;

class PersediaanController extends Controller
{
    public function index()
    {
        return view('persediaan.index');
    }
}
