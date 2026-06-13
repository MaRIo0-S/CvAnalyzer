<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        User::query()
            ->where('role', 'super_admin')
            ->whereNull('admin_id')
            ->each(function (User $gerant) {
                $platform = User::where('role', 'admin')->orderBy('id')->first();
                if ($platform) {
                    $gerant->update(['admin_id' => $platform->id]);
                }
            });

        User::query()
            ->where('role', 'sous_admin')
            ->whereNull('super_admin_id')
            ->each(function (User $rh) {
                $gerant = User::query()
                    ->where('role', 'super_admin')
                    ->where('entreprise_id', $rh->entreprise_id)
                    ->first();

                if ($gerant) {
                    $rh->update([
                        'super_admin_id' => $gerant->id,
                        'admin_id' => null,
                    ]);
                }
            });
    }

    public function down(): void
    {
        // non réversible
    }
};
