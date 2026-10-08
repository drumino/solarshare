<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        return view('front.home', [
            'stats' => [
                'members' => User::where('role', 'user')->count(),
                'cities' => User::whereNotNull('city')->distinct('city')->count('city'),
            ],
        ]);
    }
}
