<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\View\View;

class HomeController extends Controller
{
public function index(\Illuminate\Http\Request $request): View
{
    $sort = in_array($request->query('tri'), ['utiles', 'mieux_notes', 'recents'], true)
        ? $request->query('tri') : 'recents';

    $query = Review::where('is_visible', true)
        ->with(['equipment', 'user', 'replyBy', 'photos'])
        ->withExists(['helpfulVoters as voted_by_me' => function ($q) {
            $q->where('user_id', auth()->id());
        }]);

    if ($sort === 'utiles') {
        $query->orderByDesc('helpful_count')->orderByDesc('created_at');
    } elseif ($sort === 'mieux_notes') {
        $query->orderByDesc('rating')->orderByDesc('created_at');
    } else {
        $query->latest();
    }

    $latestReviews = $query->paginate(6)->withQueryString();

    return view('front.home', [
        'stats' => [
            'equipment' => \App\Models\Equipment::available()->count(),
            'rentals' => \App\Models\Reservation::where('status', 'completed')->count(),
            'cities' => \App\Models\Equipment::available()->distinct('city')->count('city'),
        ],
        'latestReviews' => $latestReviews,
        'tri' => $sort,
    ]);
}
}