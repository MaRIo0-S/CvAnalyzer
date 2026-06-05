<?php

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        User::query()
            ->where('role', Role::SuperAdmin)
            ->whereNull('admin_id')
            ->each(function (User $gerant) {
                $admin = User::where('role', Role::Admin)->orderBy('id')->first();
                if ($admin) {
                    $gerant->update(['admin_id' => $admin->id]);
                }
            });

        User::query()
            ->where('role', Role::SousAdmin)
            ->whereNull('super_admin_id')
            ->each(function (User $rh) {
                $gerant = User::query()
                    ->where('role', Role::SuperAdmin)
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
