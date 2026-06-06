<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Enums\StatutCv;
use App\Models\Cv;
use App\Models\Entreprise;
use App\Models\MessageContact;
use App\Models\Poste;
use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
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

    public function test_inscription_envoie_code_et_redirige_vers_verification(): void
    {
        Mail::fake();

        $this->post(route('register.store'), [
            'name' => 'Nouveau Candidat',
            'email' => 'nouveau.candidat@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])
            ->assertRedirect(route('register.verify'))
            ->assertSessionHas('register_pending');

        Mail::assertSent(\App\Mail\RegisterCodeMail::class, function ($mail) {
            return $mail->hasTo('nouveau.candidat@example.com');
        });
    }

    public function test_pages_publiques_accessibles(): void
    {
        $this->get(route('home'))->assertOk();
        $this->get(route('login'))->assertOk();
        $this->get(route('register'))->assertOk();
        $this->get(route('register.verify'))->assertRedirect(route('register'));
        $this->get(route('offres.index'))->assertOk();
        $this->get(route('guest.deposer'))->assertRedirect(route('offres.index'));
        $this->get(route('login.admin'))->assertOk();
        $this->get(route('login.super-admin'))->assertOk();
        $this->get('/up')->assertOk();
    }

    public function test_connexion_redirige_selon_role(): void
    {
        $this->post(route('login.admin.store'), [
            'email' => 'admin@cvapp.test',
            'password' => 'password',
        ])->assertRedirect(route('admin.backoffice'));

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

    public function test_admin_et_gerant_ne_peuvent_pas_se_connecter_via_login_public(): void
    {
        $this->post(route('login'), [
            'email' => 'admin@cvapp.test',
            'password' => 'password',
        ])->assertSessionHasErrors(['email' => 'Identifiants incorrects.']);

        $this->post(route('login'), [
            'email' => 'gerant@techcorp.test',
            'password' => 'password',
        ])->assertSessionHasErrors(['email' => 'Identifiants incorrects.']);

        $this->assertGuest();
    }

    public function test_zones_protegees_redirigent_ou_interdisent(): void
    {
        $this->get(route('rh.dashboard'))->assertRedirect(route('login'));
        $this->get(route('admin.super-admins'))->assertRedirect(route('login.admin'));
        $this->get(route('super-admin.dashboard'))->assertRedirect(route('login.super-admin'));
        $this->get(route('candidat.statut'))->assertRedirect(route('login'));

        $candidat = User::where('email', 'candidat@cvapp.test')->first();
        $this->actingAs($candidat)->get(route('rh.dashboard'))->assertForbidden();
        $this->actingAs($candidat)->get(route('admin.super-admins'))->assertForbidden();

        $rh = User::where('email', 'rh@cvapp.test')->first();
        $this->actingAs($rh)->get(route('candidat.statut'))->assertForbidden();
        $this->actingAs($rh)->get(route('admin.super-admins'))->assertForbidden();
    }

    public function test_espace_rh_pages_principales(): void
    {
        $rh = User::where('email', 'rh@cvapp.test')->first();

        $this->actingAs($rh)->get(route('rh.dashboard'))->assertOk();
        $this->actingAs($rh)->get(route('rh.filtrer.page'))->assertOk();
        $this->actingAs($rh)->get(route('rh.cvs.liste'))->assertOk();
        $this->actingAs($rh)->get(route('rh.cvs.importer'))->assertOk();
        $this->actingAs($rh)->get(route('rh.postes'))->assertOk();
        $this->actingAs($rh)->get(route('account.edit'))->assertForbidden();
        $this->actingAs($rh)->get(route('account.password.edit'))->assertForbidden();
    }

    public function test_espace_admin(): void
    {
        $admin = User::where('email', 'admin@cvapp.test')->first();

        $this->actingAs($admin)->get(route('admin.super-admins'))->assertOk();
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

    public function test_admin_creer_super_admin(): void
    {
        $admin = User::where('email', 'admin@cvapp.test')->first();
        $entreprise = Entreprise::where('nom', 'TechCorp')->first();

        $this->actingAs($admin)->post(route('admin.super-admins.store'), [
            'name' => 'Nouveau Gérant',
            'email' => 'nouveau.gerant@cvapp.test',
            'telephone' => '+33699999999',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'entreprise_nom' => $entreprise->nom,
        ])->assertRedirect();

        $this->assertDatabaseHas('users', [
            'email' => 'nouveau.gerant@cvapp.test',
            'role' => Role::SuperAdmin->value,
        ]);
    }

    public function test_deposer_cv_invite_avec_fichier(): void
    {
        $poste = Poste::whereHas('entreprise', fn ($q) => $q->where('nom', 'TechCorp'))->first();

        $this->get(route('offres.show', $poste))->assertOk();

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

    public function test_formulaire_contact_enregistre_en_base(): void
    {
        $before = \App\Models\MessageContact::count();

        $this->post(route('home.contact'), [
            'nom' => 'Marie Martin',
            'email' => 'marie@acme.test',
            'telephone' => '0612345678',
            'entreprise' => 'Acme SA',
            'message' => 'Nous souhaitons une démo pour 15 postes.',
        ])
            ->assertRedirect(route('home'))
            ->assertSessionHas('success');

        $this->assertDatabaseCount('messages_contact', $before + 1);
        $this->assertDatabaseHas('messages_contact', [
            'nom' => 'Marie Martin',
            'email' => 'marie@acme.test',
            'telephone' => '0612345678',
            'entreprise' => 'Acme SA',
            'lu' => false,
        ]);
    }

    public function test_formulaire_contact_requiert_entreprise(): void
    {
        $before = \App\Models\MessageContact::count();

        $this->post(route('home.contact'), [
            'nom' => 'Test',
            'email' => 'test@test.com',
            'telephone' => '0612345678',
            'entreprise' => '',
            'message' => 'Message assez long.',
        ])->assertSessionHasErrors('entreprise');

        $this->assertDatabaseCount('messages_contact', $before);
    }

    public function test_formulaire_contact_requiert_telephone(): void
    {
        $before = \App\Models\MessageContact::count();

        $this->post(route('home.contact'), [
            'nom' => 'Test',
            'email' => 'test@test.com',
            'entreprise' => 'Acme',
            'message' => 'Message assez long pour validation.',
        ])->assertSessionHasErrors('telephone');

        $this->assertDatabaseCount('messages_contact', $before);
    }

    public function test_admin_messages_contact(): void
    {
        MessageContact::create([
            'nom' => 'Client',
            'email' => 'client@demo.test',
            'entreprise' => 'Demo Corp',
            'message' => 'Besoin d un devis pour le module RH.',
        ]);

        $admin = User::where('email', 'admin@cvapp.test')->first();
        $rh = User::where('email', 'rh@cvapp.test')->first();

        $this->actingAs($admin)
            ->get(route('admin.messages-contact'))
            ->assertOk();

        $this->actingAs($rh)
            ->get(route('admin.messages-contact'))
            ->assertForbidden();

        $message = MessageContact::first();
        $this->actingAs($admin)
            ->patch(route('admin.messages-contact.lu', $message))
            ->assertRedirect();

        $this->assertTrue($message->fresh()->lu);
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

    public function test_desactivation_gerant_desactive_rh_et_postes_en_cascade(): void
    {
        $admin = User::where('email', 'admin@cvapp.test')->first();
        $gerant = User::where('email', 'gerant@techcorp.test')->first();
        $rh = User::where('email', 'rh@cvapp.test')->first();
        $poste = Poste::where('user_id', $rh->id)->first();

        $this->assertTrue($gerant->est_actif);
        $this->assertTrue($rh->est_actif);
        $this->assertTrue($poste->est_ouvert);

        $this->actingAs($admin)
            ->patch(route('admin.super-admins.toggle', $gerant))
            ->assertRedirect();

        $this->assertFalse($gerant->fresh()->est_actif);
        $this->assertFalse($rh->fresh()->est_actif);
        $this->assertFalse($poste->fresh()->est_ouvert);
    }

    public function test_desactivation_rh_ferme_ses_postes(): void
    {
        $gerant = User::where('email', 'gerant@techcorp.test')->first();
        $gerant->update(['est_actif' => true]);

        $rh = User::where('email', 'rh2@cvapp.test')->first();
        $poste = Poste::where('user_id', $rh->id)->first();

        $this->assertTrue($poste->est_ouvert);

        $this->actingAs($gerant)
            ->patch(route('super-admin.rh.toggle', $rh))
            ->assertRedirect();

        $this->assertFalse($rh->fresh()->est_actif);
        $this->assertFalse($poste->fresh()->est_ouvert);
    }

    public function test_reactivation_rh_restaure_postes_selon_etat_initial(): void
    {
        $gerant = User::where('email', 'gerant@techcorp.test')->first();
        $gerant->update(['est_actif' => true]);

        $rh = User::where('email', 'rh@cvapp.test')->first();
        $rh->update(['est_actif' => true]);

        $postes = Poste::where('user_id', $rh->id)->get();
        $this->assertGreaterThanOrEqual(1, $postes->count());

        $posteOuvert = $postes->first();
        $posteOuvert->update(['est_ouvert' => true]);

        if ($postes->count() > 1) {
            $postes->skip(1)->first()->update(['est_ouvert' => false]);
        }

        $this->actingAs($gerant)
            ->patch(route('super-admin.rh.toggle', $rh))
            ->assertRedirect();

        $this->assertFalse($rh->fresh()->est_actif);
        $this->assertFalse($posteOuvert->fresh()->est_ouvert);

        $this->actingAs($gerant)
            ->patch(route('super-admin.rh.toggle', $rh))
            ->assertRedirect();

        $this->assertTrue($rh->fresh()->est_actif);
        $this->assertTrue($posteOuvert->fresh()->est_ouvert);

        if ($postes->count() > 1) {
            $this->assertFalse($postes->skip(1)->first()->fresh()->est_ouvert);
        }
    }

    public function test_reactivation_gerant_restaure_rh_et_postes_selon_etat_initial(): void
    {
        $admin = User::where('email', 'admin@cvapp.test')->first();
        $gerant = User::where('email', 'gerant@techcorp.test')->first();
        $marie = User::where('email', 'rh@cvapp.test')->first();
        $paul = User::where('email', 'rh2@cvapp.test')->first();

        $gerant->update(['est_actif' => true]);
        $marie->update(['est_actif' => true]);
        $paul->update(['est_actif' => false]);

        $posteLaravel = Poste::where('titre', 'Développeur Laravel')->first();
        $posteDevOps = Poste::where('titre', 'Ingénieur DevOps')->first();
        $posteFrontend = Poste::where('titre', 'Développeur Frontend Vue')->first();

        $posteLaravel->update(['est_ouvert' => true]);
        $posteDevOps->update(['est_ouvert' => false]);
        $posteFrontend->update(['est_ouvert' => true]);

        $this->actingAs($admin)
            ->patch(route('admin.super-admins.toggle', $gerant))
            ->assertRedirect();

        $this->assertFalse($gerant->fresh()->est_actif);
        $this->assertFalse($marie->fresh()->est_actif);
        $this->assertFalse($paul->fresh()->est_actif);
        $this->assertFalse($posteLaravel->fresh()->est_ouvert);
        $this->assertFalse($posteDevOps->fresh()->est_ouvert);
        $this->assertFalse($posteFrontend->fresh()->est_ouvert);

        $this->actingAs($admin)
            ->patch(route('admin.super-admins.toggle', $gerant))
            ->assertRedirect();

        $this->assertTrue($gerant->fresh()->est_actif);
        $this->assertTrue($marie->fresh()->est_actif);
        $this->assertFalse($paul->fresh()->est_actif);
        $this->assertTrue($posteLaravel->fresh()->est_ouvert);
        $this->assertFalse($posteDevOps->fresh()->est_ouvert);
        $this->assertTrue($posteFrontend->fresh()->est_ouvert);
    }

    public function test_export_back_office_excel(): void
    {
        $admin = User::where('email', 'admin@cvapp.test')->first();

        $response = $this->actingAs($admin)->get(route('admin.backoffice.export'));

        $response->assertOk();
        $response->assertHeader(
            'content-type',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        );
        $this->assertStringContainsString('back-office.xlsx', (string) $response->headers->get('content-disposition'));
    }

    public function test_export_gerant_excel(): void
    {
        $gerant = User::where('email', 'gerant@techcorp.test')->first();

        $response = $this->actingAs($gerant)->get(route('super-admin.export.rh'));

        $response->assertOk();
        $response->assertHeader(
            'content-type',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        );
        $this->assertStringContainsString('back-office-gerant.xlsx', (string) $response->headers->get('content-disposition'));
    }

    public function test_anciennes_urls_staff_retournent_404(): void
    {
        $this->get('/super-admin')->assertNotFound();
        $this->get('/super-admin/rh')->assertNotFound();
        $this->get('/admin/back-office')->assertNotFound();
    }
}
