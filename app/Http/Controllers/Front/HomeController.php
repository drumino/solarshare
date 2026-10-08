<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Equipment;
use App\Models\User;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        return view('front.home', [
            'categories' => Category::withCount(['equipment' => fn ($q) => $q->available()])->get(),
            'featured' => Equipment::available()->with('category')->latest()->take(6)->get(),
            'stats' => [
                'equipment' => Equipment::available()->count(),
                'members' => User::where('role', 'user')->count(),
                'cities' => Equipment::available()->distinct('city')->count('city'),
            ],
        ]);
    }
}
