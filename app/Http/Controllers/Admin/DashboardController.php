<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\ReviewReport;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $openReports = ReviewReport::where('status', 'pending')->count();

        return view('admin.dashboard', [
            'kpis' => [
                'users' => User::count(),
                'reviews' => Review::count(),
                'visible_reviews' => Review::where('is_visible', true)->count(),
                'hidden_reviews' => Review::where('is_visible', false)->count(),
                'open_reports' => $openReports,
                'avg_rating' => round((float) Review::where('is_visible', true)->avg('rating'), 1),
            ],
            'bySentiment' => Review::selectRaw('sentiment, count(*) as total')->groupBy('sentiment')->pluck('total', 'sentiment'),
            'byReportStatus' => ReviewReport::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
            'latestReviews' => Review::with(['equipment', 'user'])->latest()->take(6)->get(),
        ]);
    }
}
