<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Http\Requests\EnergyAdvisorRequest;
use App\Services\Ai\EnergyAdvisor;
use Illuminate\View\View;

/** MODULE 2 — Assistant IA de dimensionnement énergétique. */
class EnergyAdvisorController extends Controller
{
    public const PRESETS = [
        ['name' => 'Smartphone (charge)', 'watts' => 10, 'hours' => 3],
        ['name' => 'Ordinateur portable', 'watts' => 60, 'hours' => 6],
        ['name' => 'Éclairage LED', 'watts' => 15, 'hours' => 5],
        ['name' => 'Réfrigérateur', 'watts' => 120, 'hours' => 8],
        ['name' => 'Ventilateur', 'watts' => 50, 'hours' => 6],
        ['name' => 'Télévision', 'watts' => 80, 'hours' => 4],
        ['name' => 'Routeur Wi-Fi', 'watts' => 12, 'hours' => 24],
    ];

    public function index(): View
    {
        return view('front.advisor.index', ['presets' => self::PRESETS, 'result' => null, 'old' => null]);
    }

    public function analyze(EnergyAdvisorRequest $request, EnergyAdvisor $advisor): View
    {
        $appliances = collect($request->validated('appliances'))
            ->map(fn ($a) => ['name' => $a['name'], 'watts' => (int) $a['watts'], 'hours' => (float) $a['hours']])
            ->values()->all();

        return view('front.advisor.index', [
            'presets' => self::PRESETS,
            'result' => $advisor->analyze($appliances, (int) $request->days, $request->context),
            'old' => $appliances,
        ]);
    }
}
