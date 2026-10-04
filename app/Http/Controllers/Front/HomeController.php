<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Equipment;
use App\Models\Reservation;
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
                'rentals' => Reservation::where('status', 'completed')->count(),
                'cities' => Equipment::available()->distinct('city')->count('city'),
            ],
        ]);
    }
}
