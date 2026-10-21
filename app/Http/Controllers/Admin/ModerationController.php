<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Equipment;
use App\Services\ModerationStatsService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ModerationController extends Controller
{
    public function __construct(private ModerationStatsService $stats) {}

    public function index(Request $request): View
    {
        $filters = $request->only(['from', 'to', 'sentiment', 'equipment_id']);
        $filters = $this->normalizeEquipmentFilter($filters);
        $equipment = Equipment::query()->orderBy('title')->get(['id', 'title']);
        $productReviews = null;
        if (! empty($filters['equipment_id'])) {
            $productReviews = \App\Models\Review::query()
                ->where('equipment_id', $filters['equipment_id'])
                ->with('user')
                ->latest()
                ->limit(10)
                ->get();
        }
        return view('admin.moderation.index', [
            'kpis' => $this->stats->kpis($filters),
            'sentiment' => $this->stats->sentimentDistribution($filters),
            'monthly' => $this->stats->monthlyAverageRating($filters),
            'aspects' => $this->stats->topAspects($filters),
            'queue' => $this->stats->reportQueue($filters),
            'filters' => $filters,
            'equipment' => $equipment,
            'productReviews' => $productReviews,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $request->only(['from', 'to', 'sentiment', 'equipment_id']);
        $filters = $this->normalizeEquipmentFilter($filters);
        $query = \App\Models\Review::query()->whereNull('deleted_at')
            ->with('user', 'equipment')
            ->withCount('reports');
        if (! empty($filters['from'])) $query->where('created_at', '>=', $filters['from']);
        if (! empty($filters['to'])) $query->where('created_at', '<=', $filters['to']);
        if (! empty($filters['sentiment'])) $query->where('sentiment', $filters['sentiment']);
        if (! empty($filters['equipment_id'])) $query->where('equipment_id', $filters['equipment_id']);

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['id', 'date', 'auteur', 'équipement', 'note', 'sentiment', 'score', 'aspects', 'visible', 'nombre de signalements', 'commentaire']);
            foreach ($query->cursor() as $r) {
                $comment = $r->comment;
                if (preg_match('/^[=+\-@]/', $comment)) {
                    $comment = "'".$comment;
                }
                fputcsv($out, [
                    $r->id,
                    $r->created_at?->format('Y-m-d H:i:s'),
                    $r->user?->name,
                    $r->equipment?->title,
                    $r->rating,
                    $r->sentiment,
                    $r->sentiment_score,
                    implode(';', $r->aspects ?? []),
                    $r->is_visible ? 'oui' : 'non',
                    $r->reports_count,
                    $comment,
                ]);
            }
            fclose($out);
        }, 'moderation.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function pdf(Request $request): Response
    {
        $filters = $request->only(['from', 'to', 'sentiment', 'equipment_id']);
        $filters = $this->normalizeEquipmentFilter($filters);
        $pdf = Pdf::loadView('admin.moderation.pdf', [
            'kpis' => $this->stats->kpis($filters),
            'sentiment' => $this->stats->sentimentDistribution($filters),
            'monthly' => $this->stats->monthlyAverageRating($filters),
            'aspects' => $this->stats->topAspects($filters),
            'queue' => $this->stats->reportQueue($filters),
            'filters' => $filters,
            'equipment' => Equipment::query()->orderBy('title')->get(['id', 'title']),
            'generatedAt' => now(),
        ])->setPaper('a4');

        return $pdf->download('rapport-moderation-'.now()->format('Y-m-d').'.pdf');
    }

    private function normalizeEquipmentFilter(array $filters): array
    {
        $id = $filters['equipment_id'] ?? null;
        if (! is_null($id) && (! is_numeric($id) || ! Equipment::whereKey((int) $id)->exists())) {
            unset($filters['equipment_id']);
        }
        return $filters;
    }
}
