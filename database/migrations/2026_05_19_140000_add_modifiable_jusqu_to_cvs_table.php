<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cvs', function (Blueprint $table) {
            $table->string('nom_candidat')->nullable()->change();
            $table->string('email_candidat')->nullable()->change();
            $table->timestamp('modifiable_jusqu')->nullable()->after('date_depot');
        });
    }

    public function down(): void
    {
        Schema::table('cvs', function (Blueprint $table) {
            $table->string('nom_candidat')->nullable(false)->change();
            $table->string('email_candidat')->nullable(false)->change();
            $table->dropColumn('modifiable_jusqu');
        });
    }
};
