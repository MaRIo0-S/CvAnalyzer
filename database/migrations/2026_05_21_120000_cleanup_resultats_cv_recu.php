<?php

use App\Enums\StatutCv;
use App\Models\ResultatAnalyse;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        ResultatAnalyse::query()
            ->whereHas('cv', fn ($q) => $q->where('statut', StatutCv::CvRecu))
            ->delete();
    }

    public function down(): void {}
};
