<?php

namespace App\Http\Controllers\Patron;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Package;

class HomeController extends Controller
{
    public function index()
    {
        $packages = Package::all();
        return view('patron.home', compact('packages'));
    }
}
