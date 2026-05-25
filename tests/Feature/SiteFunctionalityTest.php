<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Enums\StatutCv;
use App\Models\Cv;
use App\Models\Entreprise;
use App\Models\Poste;
use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SiteFunctionalityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->seed(DemoDataSeeder::class);
    }

    public function test_pages_publiques_accessibles(): void
    {
        $this->get(route('home'))->assertOk();
        $this->get(route('login'))->assertOk();
        $this->get(route('register'))->assertOk();
        $this->get(route('register.verify'))->assertRedirect(route('register'));
        $this->get(route('guest.deposer'))->assertOk();
        $this->get('/up')->assertOk();
    }

    public function test_connexion_redirige_selon_role(): void
    {
        $this->post(route('login'), [
            'email' => 'admin@cvapp.test',
            'password' => 'password',
        ])->assertRedirect(route('admin.subadmins'));

        $this->post('/logout');

        $this->post(route('login'), [
            'email' => 'rh@cvapp.test',
            'password' => 'password',
        ])->assertRedirect(route('rh.dashboard'));

        $this->post('/logout');

        $this->post(route('login'), [
            'email' => 'candidat@cvapp.test',
            'password' => 'password',
        ])->assertRedirect(route('candidat.statut'));
    }

    public function test_connexion_echoue_avec_mauvais_mot_de_passe(): void
    {
        $this->post(route('login'), [
            'email' => 'admin@cvapp.test',
            'password' => 'wrong',
        ])->assertSessionHasErrors('email');
    }

    public function test_zones_protegees_redirigent_ou_interdisent(): void
    {
        $this->get(route('rh.dashboard'))->assertRedirect(route('login'));
        $this->get(route('admin.subadmins'))->assertRedirect(route('login'));
        $this->get(route('candidat.statut'))->assertRedirect(route('login'));

        $candidat = User::where('email', 'candidat@cvapp.test')->first();
        $this->actingAs($candidat)->get(route('rh.dashboard'))->assertForbidden();
        $this->actingAs($candidat)->get(route('admin.subadmins'))->assertForbidden();

        $rh = User::where('email', 'rh@cvapp.test')->first();
        $this->actingAs($rh)->get(route('candidat.statut'))->assertForbidden();
        $this->actingAs($rh)->get(route('admin.subadmins'))->assertForbidden();
    }

    public function test_espace_rh_pages_principales(): void
    {
        $rh = User::where('email', 'rh@cvapp.test')->first();

        $this->actingAs($rh)->get(route('rh.dashboard'))->assertOk();
        $this->actingAs($rh)->get(route('rh.filtrer.page'))->assertOk();
        $this->actingAs($rh)->get(route('rh.cvs.liste'))->assertOk();
        $this->actingAs($rh)->get(route('rh.postes'))->assertOk();
    }

    public function test_espace_admin(): void
    {
        $admin = User::where('email', 'admin@cvapp.test')->first();

        $this->actingAs($admin)->get(route('admin.subadmins'))->assertOk();
        $this->actingAs($admin)->get(route('admin.backoffice'))->assertOk();
    }

    public function test_espace_candidat_statut(): void
    {
        $candidat = User::where('email', 'candidat@cvapp.test')->first();

        $this->actingAs($candidat)->get(route('candidat.statut'))->assertOk();
    }

    public function test_compte_profil_et_mot_de_passe(): void
    {
        $candidat = User::where('email', 'candidat@cvapp.test')->first();

        $this->actingAs($candidat)->get(route('account.edit'))->assertOk();
        $this->actingAs($candidat)->get(route('account.password.edit'))->assertOk();

        $this->actingAs($candidat)
            ->from(route('account.edit'))
            ->put(route('account.update'), [
                'name' => 'Jean Modifié',
                'email' => 'candidat@cvapp.test',
            ])
            ->assertRedirect(route('account.edit'));

        $this->assertEquals('Jean Modifié', $candidat->fresh()->name);

        $this->actingAs($candidat)->put(route('account.password.update'), [
            'current_password' => 'password',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ])->assertRedirect();

        $this->post(route('login'), [
            'email' => 'candidat@cvapp.test',
            'password' => 'newpassword123',
        ])->assertRedirect(route('candidat.statut'));

        $candidat->update(['password' => Hash::make('password')]);
    }

    public function test_rh_analyse_standard_avec_mots_cles(): void
    {
        $rh = User::where('email', 'rh@cvapp.test')->first();

        $eligibles = Cv::whereHas('poste', fn ($q) => $q->where('user_id', $rh->id))
            ->where('statut', StatutCv::CvRecu)
            ->get();

        Cv::whereIn('id', $eligibles->pluck('id'))->update([
            'modifiable_jusqu' => now()->subHour(),
        ]);

        $statutsAvant = $eligibles->mapWithKeys(fn (Cv $cv) => [$cv->id => $cv->fresh()->statut]);

        $this->actingAs($rh)->post(route('rh.filtrer'), [
            'mots_cles' => ['laravel', 'vue'],
            'inclure_non_valides' => false,
        ])->assertRedirect(route('rh.filtrer.resultats'));

        $this->actingAs($rh)->get(route('rh.filtrer.resultats'))->assertOk();
        $this->assertTrue(
            collect(session('rh_derniere_analyse')['cv_ids'] ?? [])->isNotEmpty()
        );

        foreach ($statutsAvant as $cvId => $statut) {
            $this->assertEquals(
                $statut,
                Cv::find($cvId)->statut,
                'Le statut ne doit pas changer avant confirmation.'
            );
        }

        $response = $this->actingAs($rh)->get(route('rh.filtrer.resultats'));
        $response->assertOk();
        $cvsPage = $response->original->getData()['page']['props']['cvs'] ?? [];
        $this->assertNotEmpty($cvsPage);
        $premier = $cvsPage[0];
        $this->assertSame('en_cours_analyse', $premier['statut_affichage'] ?? null);
        $this->assertSame('En cours d\'analyse', $premier['statut_label'] ?? null);
        $this->assertSame('cv_recu', Cv::find($premier['id'])->statut->value);
    }

    public function test_rh_retire_decision_provisoire_dans_lot(): void
    {
        $rh = User::where('email', 'rh@cvapp.test')->first();

        Cv::whereHas('poste', fn ($q) => $q->where('user_id', $rh->id))
            ->where('statut', StatutCv::CvRecu)
            ->update(['modifiable_jusqu' => now()->subHour()]);

        $this->actingAs($rh)->post(route('rh.filtrer'), [
            'mots_cles' => ['laravel'],
            'inclure_non_valides' => false,
        ]);

        $cvId = session('rh_derniere_analyse')['cv_ids'][0] ?? null;
        $this->assertNotNull($cvId);

        $this->actingAs($rh)->patch(route('rh.cv.valider', $cvId), ['valide' => true])
            ->assertRedirect();

        $this->assertSame('valide', session('rh_derniere_analyse')['decisions'][$cvId] ?? null);

        $this->actingAs($rh)->delete(route('rh.cv.decision.annuler', $cvId))
            ->assertRedirect();

        $this->assertArrayNotHasKey($cvId, session('rh_derniere_analyse')['decisions'] ?? []);
    }

    public function test_rh_annuler_analyse_provisoire_sans_changer_statut(): void
    {
        $rh = User::where('email', 'rh@cvapp.test')->first();

        Cv::whereHas('poste', fn ($q) => $q->where('user_id', $rh->id))
            ->where('statut', StatutCv::CvRecu)
            ->update(['modifiable_jusqu' => now()->subHour()]);

        $cv = Cv::whereHas('poste', fn ($q) => $q->where('user_id', $rh->id))
            ->where('statut', StatutCv::CvRecu)
            ->first();

        $this->assertNotNull($cv);
        $statutInitial = $cv->statut;

        $this->actingAs($rh)->post(route('rh.filtrer'), [
            'mots_cles' => ['laravel'],
            'inclure_non_valides' => false,
        ])->assertRedirect(route('rh.filtrer.resultats'));

        $this->assertEquals($statutInitial, $cv->fresh()->statut);

        $this->actingAs($rh)->post(route('rh.filtrer.annuler'))
            ->assertRedirect(route('rh.filtrer.page'));

        $cv->refresh();
        $this->assertEquals($statutInitial, $cv->statut);
        $this->assertNull($cv->resultatAnalyse);
        $this->assertNull(session('rh_derniere_analyse'));
    }

    public function test_rh_analyse_non_valides_salma_eligible_mehdi_non(): void
    {
        $rh = User::where('email', 'rh@cvapp.test')->first();

        $salma = Cv::where('email_candidat', 'salma.recente@email.test')->first();
        $mehdi = Cv::where('email_candidat', 'mehdi.ancien@email.test')->first();

        $this->assertNotNull($salma);
        $this->assertNotNull($mehdi);
        $this->assertTrue($salma->date_depot->gt(now()->subDays(30)));
        $this->assertTrue($mehdi->date_depot->lte(now()->subDays(30)));

        $this->actingAs($rh)->post(route('rh.filtrer'), [
            'mots_cles' => ['laravel'],
            'inclure_non_valides' => true,
        ])->assertRedirect(route('rh.filtrer.resultats'));

        $ids = session('rh_derniere_analyse')['cv_ids'] ?? [];
        $this->assertContains($salma->id, $ids);
        $this->assertNotContains($mehdi->id, $ids);
    }

    public function test_rh_filtrer_sans_mots_cles_echoue(): void
    {
        $rh = User::where('email', 'rh@cvapp.test')->first();

        $this->actingAs($rh)->post(route('rh.filtrer'), [
            'mots_cles' => [],
        ])->assertSessionHasErrors('mots_cles');
    }

    public function test_rh_valider_cv_en_analyse(): void
    {
        $rh = User::where('email', 'rh@cvapp.test')->first();
        $cv = Cv::whereHas('poste', fn ($q) => $q->where('user_id', $rh->id))
            ->where('statut', StatutCv::EnCoursAnalyse)
            ->first();

        if (! $cv) {
            $poste = Poste::where('user_id', $rh->id)->first();
            $cv = Cv::create([
                'poste_id' => $poste->id,
                'entreprise_id' => $rh->entreprise_id,
                'nom_candidat' => 'Test Analyse',
                'email_candidat' => 'test.analyse@email.test',
                'fichier_url' => 'cvs/test.pdf',
                'taille_fichier' => 0.1,
                'format_fichier' => 'pdf',
                'texte_extrait' => 'laravel vue php',
                'statut' => StatutCv::EnCoursAnalyse,
                'date_depot' => now()->subDays(2),
                'modifiable_jusqu' => now()->subDay(),
            ]);
        }

        $this->actingAs($rh)->patch(route('rh.cv.valider', $cv), [
            'valide' => true,
        ])->assertRedirect();

        $this->assertEquals(StatutCv::Valide, $cv->fresh()->statut);
    }

    public function test_rh_ne_peut_pas_valider_cv_recu_directement(): void
    {
        $rh = User::where('email', 'rh@cvapp.test')->first();
        $cv = Cv::whereHas('poste', fn ($q) => $q->where('user_id', $rh->id))
            ->where('statut', StatutCv::CvRecu)
            ->first();

        $this->assertNotNull($cv);

        $this->actingAs($rh)->patch(route('rh.cv.valider', $cv), [
            'valide' => true,
        ])->assertSessionHasErrors('decision');

        $this->assertEquals(StatutCv::CvRecu, $cv->fresh()->statut);
    }

    public function test_rh_consulter_et_telecharger_cv(): void
    {
        $rh = User::where('email', 'rh@cvapp.test')->first();
        $cv = Cv::whereHas('poste', fn ($q) => $q->where('user_id', $rh->id))->first();
        $this->assertNotNull($cv);

        Storage::disk('public')->put($cv->fichier_url, '%PDF-1.4 fake');

        $this->actingAs($rh)->get(route('rh.cv.consulter', $cv))->assertOk();
        $this->actingAs($rh)->get(route('rh.cv.telecharger', $cv))->assertOk();
    }

    public function test_rh_isolation_entreprise(): void
    {
        $rhData = User::where('email', 'rh3@cvapp.test')->first();
        $cvTech = Cv::whereHas('entreprise', fn ($q) => $q->where('nom', 'TechCorp'))->first();

        $this->actingAs($rhData)->get(route('rh.cv.consulter', $cvTech))->assertForbidden();
    }

    public function test_rh_ne_voit_pas_les_cv_des_postes_d_un_collegue(): void
    {
        $rhMarie = User::where('email', 'rh@cvapp.test')->first();
        $cvFrontend = Cv::where('email_candidat', 'nadia.berrada@email.test')->first();

        $this->assertNotNull($cvFrontend);
        $this->assertNotEquals($rhMarie->id, $cvFrontend->poste->user_id);

        $this->actingAs($rhMarie)->get(route('rh.cv.consulter', $cvFrontend))->assertForbidden();
    }

    public function test_admin_creer_sous_admin(): void
    {
        $admin = User::where('email', 'admin@cvapp.test')->first();
        $entreprise = Entreprise::where('nom', 'TechCorp')->first();

        $this->actingAs($admin)->post(route('admin.subadmins.store'), [
            'name' => 'Nouveau RH',
            'email' => 'nouveau.rh@cvapp.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'entreprise_nom' => $entreprise->nom,
        ])->assertRedirect();

        $this->assertDatabaseHas('users', [
            'email' => 'nouveau.rh@cvapp.test',
            'role' => Role::SousAdmin->value,
        ]);
    }

    public function test_deposer_cv_invite_avec_fichier(): void
    {
        $poste = Poste::whereHas('entreprise', fn ($q) => $q->where('nom', 'TechCorp'))->first();

        $file = UploadedFile::fake()->create('cv-test.pdf', 100, 'application/pdf');

        $this->post(route('guest.deposer.store'), [
            'nom_candidat' => 'Invité Test',
            'email_candidat' => 'invite.test@email.test',
            'entreprise_id' => $poste->entreprise_id,
            'poste_id' => $poste->id,
            'fichier' => $file,
        ])->assertRedirect(route('guest.deposer'));

        $this->assertDatabaseHas('cvs', [
            'email_candidat' => 'invite.test@email.test',
            'nom_candidat' => 'Invité Test',
            'statut' => StatutCv::CvRecu->value,
        ]);
    }

    public function test_staff_redirige_de_deposer(): void
    {
        $rh = User::where('email', 'rh@cvapp.test')->first();

        $this->actingAs($rh)->get(route('guest.deposer'))
            ->assertRedirect(route('rh.dashboard'));
    }

    public function test_deconnexion(): void
    {
        $admin = User::where('email', 'admin@cvapp.test')->first();

        $this->actingAs($admin)->post(route('logout'))
            ->assertRedirect(route('home'));

        $this->assertGuest();
    }

    public function test_service_analyse_score(): void
    {
        $service = app(\App\Services\ServiceAnalyse::class);
        $result = $service->calculerScore(
            'Développeur Laravel Vue Inertia PostgreSQL',
            ['laravel', 'vue', 'java']
        );

        $this->assertEquals(2, $result['nombre']);
        $this->assertContains('laravel', $result['matches']);
        $this->assertContains('vue', $result['matches']);
        $this->assertEqualsWithDelta(66.67, $result['score'], 0.1);
    }
}
