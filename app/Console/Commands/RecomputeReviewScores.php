<?php

namespace App\Console\Commands;

use App\Models\Equipment;
use App\Services\EquipmentRatingService;
use Illuminate\Console\Command;

class RecomputeReviewScores extends Command
{
    protected $signature = 'reviews:recompute-scores';

    protected $description = 'Recalcule le score bayésien et les statistiques d\'avis pour tous les équipements.';

    public function handle(): void
    {
        $equipments = Equipment::all();
        $bar = $this->output->createProgressBar($equipments->count());

        foreach ($equipments as $equipment) {
            app(EquipmentRatingService::class)->recompute($equipment);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Scores des avis recomputés pour '.$equipments->count().' équipement(s).');
    }
}
