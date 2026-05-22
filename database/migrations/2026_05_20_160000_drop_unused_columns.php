<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['email_verified_at', 'seuil_score']);
        });

        Schema::table('entreprises', function (Blueprint $table) {
            $table->dropColumn('description');
        });

        Schema::table('cvs', function (Blueprint $table) {
            $table->dropColumn(['guest_uuid', 'guest_ip', 'date_analyse']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('email_verified_at')->nullable();
            $table->decimal('seuil_score', 5, 2)->default(50.00);
        });

        Schema::table('entreprises', function (Blueprint $table) {
            $table->text('description')->nullable();
        });

        Schema::table('cvs', function (Blueprint $table) {
            $table->timestamp('date_analyse')->nullable();
            $table->string('guest_uuid')->nullable()->index();
            $table->string('guest_ip', 45)->nullable()->index();
        });
    }
};
