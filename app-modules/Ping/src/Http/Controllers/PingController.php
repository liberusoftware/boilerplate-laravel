<?php

namespace Modules\Ping\Http\Controllers;

use Illuminate\Routing\Controller;

class PingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('ping::index');
    }
}
