<?php

namespace App\Services;

use App\Models\Equipment;
use App\Models\Review;
use App\Models\ReviewReport;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ModerationStatsService
{
    private const ASPECTS = ['prix', 'qualitÃ©', 'ponctualitÃ©', 'communication'];

    public function kpis(array $filters = []): array
    {
        $filters = $this->normalizeFilters($filters);
        return Cache::remember('mod.kpis.'.md5(serialize($filters)), 300, function () use ($filters) {
            $q = $this->baseQuery($filters);
            $total = (clone $q)->count();
            $hidden = (clone $q)->where('is_visible', false)->count();
            $toxic = (clone $q)->whereNotNull('moderation_note')->count();
            $pendingReports = ReviewReport::where('status', 'pending')->count();
            $avgRating = (clone $q)->avg('rating');
            return [
                'total' => $total,
                'hidden' => $hidden,
                'toxic_share' => $total > 0 ? round($toxic / $total * 100, 1) : 0,
                'pending_reports' => $pendingReports,
                'avg_rating' => $avgRating ? round($avgRating, 2) : null,
            ];
        });
    }

    public function sentimentDistribution(array $filters = []): array
    {
        $filters = $this->normalizeFilters($filters);
        return Cache::remember('mod.sentiment.'.md5(serialize($filters)), 300, function () use ($filters) {
            $q = $this->baseQuery($filters);
            return [
                'positive' => (clone $q)->where('sentiment', 'positive')->count(),
                'neutral' => (clone $q)->where('sentiment', 'neutral')->count(),
                'negative' => (clone $q)->where('sentiment', 'negative')->count(),
            ];
        });
    }

    public function monthlyAverageRating(array $filters = []): array
    {
        $filters = $this->normalizeFilters($filters);
        return Cache::remember('mod.monthly.'.md5(serialize($filters)), 300, function () use ($filters) {
            $months = [];
            for ($i = 11; $i >= 0; $i--) {
                $months[] = now()->subMonths($i)->format('Y-m');
            }
            $q = $this->baseQuery($filters);
            $rows = (clone $q)
                ->select(DB::raw("strftime('%Y-%m', created_at) as m"), DB::raw('AVG(rating) as avg'))
                ->where('created_at', '>=', now()->subMonths(11)->startOfMonth())
                ->groupBy('m')
                ->pluck('avg', 'm');
            $result = [];
            foreach ($months as $m) {
                $result[$m] = isset($rows[$m]) ? round((float) $rows[$m], 2) : null;
            }
            return $result;
        });
    }

    public function topAspects(array $filters = []): array
    {
        $filters = $this->normalizeFilters($filters);
        return Cache::remember('mod.aspects.'.md5(serialize($filters)), 300, function () use ($filters) {
            $result = [];
            foreach (self::ASPECTS as $aspect) {
                $result[$aspect] = $this->baseQuery($filters)->whereJsonContains('aspects', $aspect)->count();
            }
            arsort($result);
            return array_slice($result, 0, 6, true);
        });
    }

    public function reportQueue(array $filters = []): \Illuminate\Support\Collection
    {
        $filters = $this->normalizeFilters($filters);
        return Cache::remember('mod.reportqueue.'.md5(serialize($filters)), 300, function () use ($filters) {
            $q = \App\Models\ReviewReport::with('review', 'user')
                ->where('status', 'pending')
                ->orderByRaw("CASE severity WHEN 'high' THEN 1 WHEN 'medium' THEN 2 WHEN 'low' THEN 3 ELSE 4 END")
                ->latest();
            if (! empty($filters['equipment_id'])) {
                $q->whereHas('review', fn ($r) => $r->where('equipment_id', $filters['equipment_id']));
            }
            return $q->limit(10)->get();
        });
    }

    private function normalizeFilters(array $filters): array
    {
        if (isset($filters['equipment_id']) && (! is_numeric($filters['equipment_id']) || ! Equipment::whereKey((int) $filters['equipment_id'])->exists())) {
            $filters['equipment_id'] = null;
        }
        return $filters;
    }

    private function baseQuery(array $filters): \Illuminate\Database\Eloquent\Builder
    {
        $q = Review::query()->whereNull('deleted_at');
        if (! empty($filters['from'])) {
            $q->where('created_at', '>=', $filters['from']);
        }
        if (! empty($filters['to'])) {
            $q->where('created_at', '<=', $filters['to']);
        }
        if (! empty($filters['sentiment'])) {
            $q->where('sentiment', $filters['sentiment']);
        }
        if (! empty($filters['equipment_id'])) {
            $q->where('equipment_id', $filters['equipment_id']);
        }
        return $q;
    }
}