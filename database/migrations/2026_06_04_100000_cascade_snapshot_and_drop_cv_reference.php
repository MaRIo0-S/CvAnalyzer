<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users') && ! Schema::hasColumn('users', 'etat_cascade_snapshot')) {
            Schema::table('users', function (Blueprint $table) {
                $table->json('etat_cascade_snapshot')->nullable()->after('est_actif');
            });
        }

        if (Schema::hasColumn('cvs', 'reference')) {
            Schema::table('cvs', function (Blueprint $table) {
                $table->dropColumn('reference');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'etat_cascade_snapshot')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('etat_cascade_snapshot');
            });
        }

        if (Schema::hasTable('cvs') && ! Schema::hasColumn('cvs', 'reference')) {
            Schema::table('cvs', function (Blueprint $table) {
                $table->string('reference', 24)->nullable()->unique()->after('id');
            });
        }
    }
};
