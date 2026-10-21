<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Equipment;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EquipmentController extends Controller
{
    public function index(Request $request): View
    {
        $top = $request->boolean('top');

        $sort = in_array($request->query('tri'), ['recents', 'mieux_notes', 'prix_asc', 'prix_desc'], true)
            ? $request->query('tri') : 'recents';

        $query = Equipment::available();

        if ($top) {
            $minAvg = (float) config('solarshare.reviews.top_rated_min_avg', 4.5);
            $minCount = (int) config('solarshare.reviews.top_rated_min_count', 10);
            $query->where('reviews_count', '>=', $minCount)
                ->where('reviews_avg', '>=', $minAvg);
        }

        if ($sort === 'mieux_notes') {
            $query->orderByDesc('bayesian_score')->orderByDesc('id');
        } elseif ($sort === 'prix_asc') {
            $query->orderByAsc('price_per_day')->orderByDesc('id');
        } elseif ($sort === 'prix_desc') {
            $query->orderByDesc('price_per_day')->orderByDesc('id');
        } else {
            $query->latest();
        }

        $equipments = $query->paginate(9)->withQueryString();

        return view('front.equipements.index', [
            'equipments' => $equipments,
            'top' => $top,
            'tri' => $sort,
        ]);
    }

    public function show(Equipment $equipment): View
    {
        $equipment->load(['category', 'owner',
            'reviews' => function ($query) {
                $query->where('is_visible', true)
                    ->whereNull('deleted_at')
                    ->latest();
            },
            'reviews.user',
        ]);

        return view('front.equipements.show', [
            'equipment' => $equipment,
        ]);
    }
}
