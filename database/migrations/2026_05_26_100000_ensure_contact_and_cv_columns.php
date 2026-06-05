<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('messages_contact') && ! Schema::hasColumn('messages_contact', 'telephone')) {
            Schema::table('messages_contact', function (Blueprint $table) {
                $table->string('telephone', 30)->nullable()->after('email');
            });
        }

        if (Schema::hasTable('cvs') && ! Schema::hasColumn('cvs', 'importe_par_rh')) {
            Schema::table('cvs', function (Blueprint $table) {
                $table->boolean('importe_par_rh')->default(false)->after('user_id');
            });

            DB::table('cvs')
                ->whereNull('user_id')
                ->whereNull('modifiable_jusqu')
                ->update(['importe_par_rh' => true]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('messages_contact', 'telephone')) {
            Schema::table('messages_contact', function (Blueprint $table) {
                $table->dropColumn('telephone');
            });
        }

        if (Schema::hasColumn('cvs', 'importe_par_rh')) {
            Schema::table('cvs', function (Blueprint $table) {
                $table->dropColumn('importe_par_rh');
            });
        }
    }
};
