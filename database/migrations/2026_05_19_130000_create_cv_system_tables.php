<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mots_cles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('valeur');
            $table->timestamp('date_creation')->useCurrent();
            $table->timestamps();
        });

        Schema::create('poste_mot_cle', function (Blueprint $table) {
            $table->id();
            $table->foreignId('poste_id')->constrained('postes')->cascadeOnDelete();
            $table->foreignId('mot_cle_id')->constrained('mots_cles')->cascadeOnDelete();
            $table->unique(['poste_id', 'mot_cle_id']);
        });

        Schema::create('cvs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('poste_id')->constrained('postes')->cascadeOnDelete();
            $table->foreignId('entreprise_id')->constrained('entreprises')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('nom_candidat');
            $table->string('email_candidat');
            $table->string('fichier_url');
            $table->float('taille_fichier');
            $table->timestamp('date_depot')->useCurrent();
            $table->string('format_fichier', 10);
            $table->longText('texte_extrait')->nullable();
            $table->string('statut')->default('cv_recu');
            $table->timestamps();
        });

        Schema::create('resultats_analyse', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cv_id')->unique()->constrained('cvs')->cascadeOnDelete();
            $table->float('score_matching')->default(0);
            $table->json('mots_cles_matches')->nullable();
            $table->unsignedInteger('nombre_matches')->default(0);
            $table->timestamp('date_analyse')->useCurrent();
            $table->timestamps();
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('cv_id')->constrained('cvs')->cascadeOnDelete();
            $table->text('message');
            $table->timestamp('date_envoi')->useCurrent();
            $table->string('statut_au_moment');
            $table->boolean('lu')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('resultats_analyse');
        Schema::dropIfExists('cvs');
        Schema::dropIfExists('poste_mot_cle');
        Schema::dropIfExists('mots_cles');
    }
};
