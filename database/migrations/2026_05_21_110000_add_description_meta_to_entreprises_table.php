<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('entreprises', function (Blueprint $table) {
            $table->foreignId('description_updated_by')
                ->nullable()
                ->after('description')
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('description_updated_at')->nullable()->after('description_updated_by');
        });
    }

    public function down(): void
    {
        Schema::table('entreprises', function (Blueprint $table) {
            $table->dropConstrainedForeignId('description_updated_by');
            $table->dropColumn('description_updated_at');
        });
    }
};
