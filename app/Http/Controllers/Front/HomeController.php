<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        return view('front.home', [
            'stats' => [
                'equipment' => \App\Models\Equipment::available()->count(),
                'rentals' => \App\Models\Reservation::where('status', 'completed')->count(),
                'cities' => \App\Models\Equipment::available()->distinct('city')->count('city'),
            ],
            'latestReviews' => Review::where('is_visible', true)
                ->with(['equipment', 'user'])->latest()->take(6)->get(),
        ]);
    }
}