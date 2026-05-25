<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $userDrops = array_filter([
            Schema::hasColumn('users', 'email_verified_at') ? 'email_verified_at' : null,
            Schema::hasColumn('users', 'seuil_score') ? 'seuil_score' : null,
        ]);

        if ($userDrops !== []) {
            Schema::table('users', function (Blueprint $table) use ($userDrops) {
                $table->dropColumn(array_values($userDrops));
            });
        }

        if (Schema::hasColumn('entreprises', 'description')) {
            Schema::table('entreprises', function (Blueprint $table) {
                $table->dropColumn('description');
            });
        }

        $cvDrops = array_filter([
            Schema::hasColumn('cvs', 'guest_uuid') ? 'guest_uuid' : null,
            Schema::hasColumn('cvs', 'guest_ip') ? 'guest_ip' : null,
            Schema::hasColumn('cvs', 'date_analyse') ? 'date_analyse' : null,
        ]);

        if ($cvDrops !== []) {
            Schema::table('cvs', function (Blueprint $table) use ($cvDrops) {
                $table->dropColumn(array_values($cvDrops));
            });
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'email_verified_at')) {
                $table->timestamp('email_verified_at')->nullable();
            }
            if (! Schema::hasColumn('users', 'seuil_score')) {
                $table->decimal('seuil_score', 5, 2)->default(50.00);
            }
        });

        Schema::table('entreprises', function (Blueprint $table) {
            if (! Schema::hasColumn('entreprises', 'description')) {
                $table->text('description')->nullable();
            }
        });

        Schema::table('cvs', function (Blueprint $table) {
            if (! Schema::hasColumn('cvs', 'date_analyse')) {
                $table->timestamp('date_analyse')->nullable();
            }
            if (! Schema::hasColumn('cvs', 'guest_uuid')) {
                $table->string('guest_uuid')->nullable()->index();
            }
            if (! Schema::hasColumn('cvs', 'guest_ip')) {
                $table->string('guest_ip', 45)->nullable()->index();
            }
        });
    }
};
