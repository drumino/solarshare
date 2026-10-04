<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Equipment;
use App\Models\Incident;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\Review;
use App\Models\ReviewReport;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('admin.dashboard', [
            'kpis' => [
                'users' => User::count(),
                'equipment' => Equipment::count(),
                'pending_equipment' => Equipment::where('status', 'pending')->count(),
                'reservations' => Reservation::count(),
                'revenue' => Payment::where('status', 'paid')->sum('amount'),
                'open_incidents' => Incident::where('status', '!=', 'resolved')->count(),
                'open_reports' => ReviewReport::where('status', 'open')->count(),
                'avg_rating' => round((float) Review::where('is_visible', true)->avg('rating'), 1),
            ],
            'byType' => Equipment::selectRaw('type, count(*) as total')->groupBy('type')->pluck('total', 'type'),
            'byStatus' => Reservation::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
            'bySentiment' => Review::selectRaw('sentiment, count(*) as total')->groupBy('sentiment')->pluck('total', 'sentiment'),
            'bySeverity' => Incident::selectRaw('severity, count(*) as total')->groupBy('severity')->pluck('total', 'severity'),
            'latestReservations' => Reservation::with(['equipment', 'user'])->latest()->take(6)->get(),
        ]);
    }
}
