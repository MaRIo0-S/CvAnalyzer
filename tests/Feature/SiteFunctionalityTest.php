<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Enums\StatutCv;
use App\Models\Cv;
use App\Models\Entreprise;
use App\Models\Poste;
use App\Models\User;
use App\Mail\StatutCandidatureMail;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
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

    public function test_pages_publiques_accessibles(): void
    {
        $this->get(route('home'))->assertOk();
        $this->get(route('login'))->assertOk();
        $this->get(route('register'))->assertOk();
        $this->get(route('offres.index'))->assertOk();
        $this->get(route('guest.deposer'))->assertRedirect(route('offres.index'));
        $this->get(route('login.super-admin'))->assertOk();
        $this->get(route('login.gerant'))->assertOk();
    }

    public function test_authentification_par_portail_et_role(): void
    {
        $this->post(route('login.super-admin.store'), [
            'email' => 'admin@cvapp.test',
            'password' => 'password',
        ])->assertRedirect(route('admin.backoffice'));

        $this->post('/logout');

        $this->post(route('login.gerant.store'), [
            'email' => 'gerant@techcorp.test',
            'password' => 'password',
        ])->assertRedirect(route('gerant.dashboard'));

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

        $this->post('/logout');

        $this->post(route('login'), [
            'email' => 'admin@cvapp.test',
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_acces_zones_protegees_selon_role(): void
    {
        $this->get(route('rh.dashboard'))->assertRedirect(route('login'));
        $this->get(route('candidat.statut'))->assertRedirect(route('login'));

        $candidat = User::where('email', 'candidat@cvapp.test')->first();
        $rh = User::where('email', 'rh@cvapp.test')->first();

        $this->actingAs($candidat)->get(route('rh.dashboard'))->assertForbidden();
        $this->actingAs($rh)->get(route('candidat.statut'))->assertForbidden();
    }

    public function test_inscription_candidat_avec_code_email(): void
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

        Mail::assertSent(\App\Mail\RegisterCodeMail::class);
    }

    public function test_deposer_cv_invite_sans_email(): void
    {
        Mail::fake();

        $poste = Poste::whereHas('entreprise', fn ($q) => $q->where('nom', 'TechCorp'))->first();
        $this->get(route('offres.show', $poste))->assertOk();

        $file = UploadedFile::fake()->create('cv-test.pdf', 100, 'application/pdf');

        $this->post(route('guest.deposer.store'), [
            'nom_candidat' => 'Invite Test',
            'email_candidat' => 'invite.test@email.test',
            'entreprise_id' => $poste->entreprise_id,
            'poste_id' => $poste->id,
            'fichier' => $file,
        ])->assertRedirect(route('guest.deposer'));

        $this->assertDatabaseHas('cvs', [
            'email_candidat' => 'invite.test@email.test',
            'statut' => StatutCv::CvRecu->value,
            'user_id' => null,
        ]);

        Mail::assertNothingSent();
    }

    public function test_deposer_cv_candidat_connecte_envoie_email(): void
    {
        Mail::fake();

        $poste = Poste::whereHas('entreprise', fn ($q) => $q->where('nom', 'TechCorp'))->first();
        $candidat = User::where('email', 'candidat@cvapp.test')->first();

        $this->actingAs($candidat)->get(route('offres.show', $poste))->assertOk();

        $file = UploadedFile::fake()->create('cv-candidat.pdf', 100, 'application/pdf');

        $this->actingAs($candidat)->post(route('guest.deposer.store'), [
            'nom_candidat' => $candidat->name,
            'email_candidat' => $candidat->email,
            'entreprise_id' => $poste->entreprise_id,
            'poste_id' => $poste->id,
            'fichier' => $file,
        ])->assertRedirect(route('guest.deposer'));

        Mail::assertSent(StatutCandidatureMail::class);
    }

    public function test_formulaire_contact_enregistre_message(): void
    {
        $this->post(route('home.contact'), [
            'nom' => 'Marie Martin',
            'email' => 'marie@acme.test',
            'telephone' => '0612345678',
            'entreprise' => 'Acme SA',
            'message' => 'Demande de demonstration.',
        ])
            ->assertRedirect(route('home'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('messages_contact', [
            'email' => 'marie@acme.test',
            'entreprise' => 'Acme SA',
            'lu' => false,
        ]);
    }

    public function test_module_rh_pages_et_lancement_analyse(): void
    {
        $rh = User::where('email', 'rh@cvapp.test')->first();

        $this->actingAs($rh)->get(route('rh.dashboard'))->assertOk();
        $this->actingAs($rh)->get(route('rh.filtrer.page'))->assertOk();
        $this->actingAs($rh)->get(route('rh.cvs.liste'))->assertOk();
        $this->actingAs($rh)->get(route('rh.postes'))->assertOk();

        Cv::whereHas('poste', fn ($q) => $q->where('user_id', $rh->id))
            ->where('statut', StatutCv::CvRecu)
            ->update(['modifiable_jusqu' => now()->subHour()]);

        $this->actingAs($rh)->post(route('rh.filtrer'), [
            'mots_cles' => ['laravel', 'vue'],
            'inclure_non_valides' => false,
        ])->assertRedirect(route('rh.filtrer.resultats'));

        $this->assertNotEmpty(session('rh_derniere_analyse')['cv_ids'] ?? []);
    }

    public function test_rh_isolation_donnees(): void
    {
        $rhMarie = User::where('email', 'rh@cvapp.test')->first();
        $rhData = User::where('email', 'rh3@cvapp.test')->first();

        $cvAutreEntreprise = Cv::whereHas('entreprise', fn ($q) => $q->where('nom', 'TechCorp'))->first();
        $cvCollegue = Cv::where('email_candidat', 'nadia.berrada@email.test')->first();

        $this->actingAs($rhData)->get(route('rh.cv.consulter', $cvAutreEntreprise))->assertForbidden();
        $this->actingAs($rhMarie)->get(route('rh.cv.consulter', $cvCollegue))->assertForbidden();
    }

    public function test_admin_creer_gerant_et_back_office(): void
    {
        $admin = User::where('email', 'admin@cvapp.test')->first();
        $entreprise = Entreprise::where('nom', 'TechCorp')->first();

        $this->actingAs($admin)->get(route('admin.backoffice'))->assertOk();

        $this->actingAs($admin)->post(route('admin.gerants.store'), [
            'name' => 'Nouveau Gerant',
            'email' => 'nouveau.gerant@cvapp.test',
            'telephone' => '+33699999999',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'entreprise_nom' => $entreprise->nom,
        ])->assertRedirect();

        $this->assertDatabaseHas('users', [
            'email' => 'nouveau.gerant@cvapp.test',
            'role' => Role::Admin->value,
        ]);
    }

    public function test_desactivation_cascade_gerant(): void
    {
        $admin = User::where('email', 'admin@cvapp.test')->first();
        $gerant = User::where('email', 'gerant@techcorp.test')->first();
        $rh = User::where('email', 'rh@cvapp.test')->first();
        $poste = Poste::where('user_id', $rh->id)->first();

        $this->actingAs($admin)
            ->patch(route('admin.gerants.toggle', $gerant))
            ->assertRedirect();

        $this->assertFalse($gerant->fresh()->est_actif);
        $this->assertFalse($rh->fresh()->est_actif);
        $this->assertFalse($poste->fresh()->est_ouvert);
    }

    public function test_export_excel_admin_et_gerant(): void
    {
        $admin = User::where('email', 'admin@cvapp.test')->first();
        $gerant = User::where('email', 'gerant@techcorp.test')->first();

        $this->actingAs($admin)
            ->get(route('admin.backoffice.export'))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $this->actingAs($gerant)
            ->get(route('gerant.export.rh'))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }
}
