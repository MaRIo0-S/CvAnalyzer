<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('telephone', 30)->nullable()->after('email');
            $table->boolean('est_actif')->default(true)->after('role');
            $table->foreignId('super_admin_id')->nullable()->after('admin_id')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('super_admin_id');
            $table->dropColumn(['telephone', 'est_actif']);
        });
    }
};
