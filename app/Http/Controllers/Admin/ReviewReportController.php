<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReviewReportRequest;
use App\Models\Review;
use App\Models\ReviewReport;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** MODULE 3 — CRUD Signalements d'avis (jointure Review + User). */
class ReviewReportController extends Controller
{
    public function index(Request $request): View
    {
        $reports = ReviewReport::query()
            ->join('reviews', 'reviews.id', '=', 'review_reports.review_id')
            ->join('users', 'users.id', '=', 'review_reports.user_id')
            ->select('review_reports.*', 'reviews.comment as review_comment', 'reviews.is_visible as review_visible', 'users.name as reporter_name')
            ->when($request->status, fn ($q, $v) => $q->where('review_reports.status', $v))
            ->when($request->reason, fn ($q, $v) => $q->where('review_reports.reason', $v))
            ->orderByRaw("CASE review_reports.severity WHEN 'high' THEN 1 WHEN 'medium' THEN 2 WHEN 'low' THEN 3 ELSE 4 END")
            ->latest('review_reports.created_at')
            ->paginate(10)->withQueryString();

        return view('admin.review-reports.index', compact('reports'));
    }

    public function create(): View
    {
        return view('admin.review-reports.form', $this->formData(new ReviewReport(['status' => 'pending'])));
    }

    public function store(ReviewReportRequest $request): RedirectResponse
    {
        $report = ReviewReport::create($request->validated());
        $this->applyDecision($request, $report);

        return redirect()->route('admin.review-reports.index')->with('success', 'Signalement créé.');
    }

    public function edit(ReviewReport $reviewReport): View
    {
        return view('admin.review-reports.form', $this->formData($reviewReport));
    }

    public function update(ReviewReportRequest $request, ReviewReport $reviewReport): RedirectResponse
    {
        $reviewReport->update($request->validated());
        $this->applyDecision($request, $reviewReport);

        return redirect()->route('admin.review-reports.index')->with('success', 'Signalement mis à jour.');
    }

    public function show(ReviewReport $reviewReport): View
    {
        $reviewReport->load(['review', 'review.user', 'reporter', 'reviewer']);

        return view('admin.review-reports.show', ['report' => $reviewReport]);
    }

    public function traiter(Request $request, ReviewReport $reviewReport): RedirectResponse
    {
        $reviewReport->update([
            'status' => 'reviewed',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);
        if ($request->boolean('hide_review')) {
            $reviewReport->review->hide('Masqué suite à un signalement.');
        } elseif ($request->boolean('show_review')) {
            $reviewReport->review->update(['is_visible' => true]);
        }

        return redirect()->route('admin.review-reports.index')->with('success', 'Signalement traité.');
    }

    public function rejeter(ReviewReport $reviewReport): RedirectResponse
    {
        $reviewReport->update([
            'status' => 'dismissed',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        return redirect()->route('admin.review-reports.index')->with('success', 'Signalement rejeté.');
    }

    public function destroy(ReviewReport $reviewReport): RedirectResponse
    {
        $reviewReport->delete();

        return back()->with('success', 'Signalement supprimé.');
    }

    /** Si traité avec la case "masquer l'avis", l'avis disparaît du site. */
    private function applyDecision(Request $request, ReviewReport $report): void
    {
        if ($report->status === 'resolved' && $request->boolean('hide_review')) {
            $report->review->update([
                'is_visible' => false,
                'moderation_note' => 'Masqué suite à un signalement ('.(ReviewReport::REASONS[$report->reason] ?? $report->reason).')',
            ]);
        }
    }

    private function formData(ReviewReport $report): array
    {
        return [
            'report' => $report,
            'reviews' => Review::with('user')->latest()->get(),
            'users' => User::orderBy('name')->get(),
        ];
    }
}
