<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AccountEmailVerifyController;
use App\Http\Controllers\AccountPasswordController;
use App\Http\Controllers\Admin\BackOfficeController;
use App\Http\Controllers\Admin\GerantController;
use App\Http\Controllers\Admin\MessageContactController;
use App\Http\Controllers\Auth\PortalLoginController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CandidatController;
use App\Http\Controllers\CandidatNotificationController;
use App\Http\Controllers\Gerant\DashboardController as GerantDashboardController;
use App\Http\Controllers\Gerant\EntrepriseController as GerantEntrepriseController;
use App\Http\Controllers\Gerant\RhTeamController as GerantRhTeamController;
use App\Http\Controllers\GuestCvController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\Rh\CvController;
use App\Http\Controllers\Rh\CvImportController;
use App\Http\Controllers\Rh\DashboardController;
use App\Http\Controllers\Rh\PosteController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/offres', [HomeController::class, 'offres'])->name('offres.index');
Route::get('/offres/{poste}', [HomeController::class, 'offreShow'])->name('offres.show');
Route::post('/contact', [HomeController::class, 'contact'])->name('home.contact');

Route::get('/login', [PortalLoginController::class, 'showStaffLogin'])->name('login');
Route::post('/login', [PortalLoginController::class, 'loginStaff'])->name('login.store');

$superAdminLoginPath = config('cvanalyzer.super_admin_login_path');
$gerantLoginPath = config('cvanalyzer.gerant_login_path');

Route::get($superAdminLoginPath, [PortalLoginController::class, 'showSuperAdminLogin'])->name('login.super-admin');
Route::post($superAdminLoginPath, [PortalLoginController::class, 'loginSuperAdmin'])->name('login.super-admin.store');
Route::get($gerantLoginPath, [PortalLoginController::class, 'showGerantLogin'])->name('login.gerant');
Route::post($gerantLoginPath, [PortalLoginController::class, 'loginGerant'])->name('login.gerant.store');

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware('no.staff.depot')->group(function () {
    Route::get('/deposer', [GuestCvController::class, 'create'])->name('guest.deposer');
    Route::post('/deposer', [GuestCvController::class, 'store'])
        ->middleware(['guest.deposit'])
        ->name('guest.deposer.store');
    Route::post('/deposer/{cv}', [GuestCvController::class, 'update'])->name('guest.deposer.update');
});

Route::get('/inscription', [RegisterController::class, 'showRegister'])->name('register');
Route::post('/inscription', [RegisterController::class, 'register'])->name('register.store');
Route::get('/inscription/verification', [RegisterController::class, 'showVerify'])->name('register.verify');
Route::post('/inscription/verification', [RegisterController::class, 'verify'])->name('register.verify.store');

Route::middleware(['auth', 'active', 'no.rh.self.account'])->group(function () {
    Route::get('/compte', [AccountController::class, 'edit'])->name('account.edit');
    Route::put('/compte', [AccountController::class, 'update'])->name('account.update');
    Route::delete('/compte', [AccountController::class, 'destroy'])->name('account.destroy');
    Route::get('/compte/verification-email', [AccountEmailVerifyController::class, 'show'])->name('account.email.verify');
    Route::post('/compte/verification-email', [AccountEmailVerifyController::class, 'verify'])->name('account.email.verify.store');
    Route::get('/compte/mot-de-passe', [AccountPasswordController::class, 'edit'])->name('account.password.edit');
    Route::put('/compte/mot-de-passe', [AccountPasswordController::class, 'update'])->name('account.password.update');
});

Route::middleware(['auth', 'active', 'role:candidat'])->prefix('candidat')->group(function () {
    Route::get('/statut', [CandidatController::class, 'statut'])->name('candidat.statut');
    Route::post('/notifications/tout-lu', [CandidatNotificationController::class, 'marquerToutLu'])
        ->name('candidat.notifications.tout-lu');
});

Route::middleware(['auth', 'active', 'role:sous_admin,admin'])->prefix('rh')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('rh.dashboard');
    Route::get('/cvs', [DashboardController::class, 'filtrerPage'])->name('rh.filtrer.page');
    Route::post('/filtrer', [DashboardController::class, 'filtrer'])->name('rh.filtrer');
    Route::post('/filtrer/confirmer', [DashboardController::class, 'confirmerAnalyse'])->name('rh.filtrer.confirmer');
    Route::post('/filtrer/annuler', [DashboardController::class, 'annulerAnalyse'])->name('rh.filtrer.annuler');
    Route::get('/filtrer/resultats', [DashboardController::class, 'derniereAnalyse'])->name('rh.filtrer.resultats');
    Route::patch('/cvs/{cv}/valider', [CvController::class, 'valider'])->name('rh.cv.valider');
    Route::delete('/cvs/{cv}/decision-provisoire', [CvController::class, 'annulerDecisionProvisoire'])
        ->name('rh.cv.decision.annuler');
    Route::get('/cvs/liste', [CvController::class, 'index'])->name('rh.cvs.liste');
    Route::get('/cvs/importer', [CvImportController::class, 'create'])->name('rh.cvs.importer');
    Route::post('/cvs/importer', [CvImportController::class, 'store'])->name('rh.cvs.importer.store');
    Route::post('/cvs/zip', [CvController::class, 'telechargerZip'])->name('rh.cvs.zip');
    Route::get('/cvs/{cv}/consulter', [CvController::class, 'show'])->name('rh.cv.consulter');
    Route::get('/cvs/{cv}/fichier', [CvController::class, 'fichier'])->name('rh.cv.fichier');
    Route::get('/cvs/{cv}/telecharger', [CvController::class, 'telecharger'])->name('rh.cv.telecharger');
    Route::get('/postes', [PosteController::class, 'index'])->name('rh.postes');
    Route::post('/postes', [PosteController::class, 'store'])->name('rh.postes.store');
    Route::put('/postes/{poste}', [PosteController::class, 'update'])->name('rh.postes.update');
    Route::patch('/postes/{poste}/ouvert', [PosteController::class, 'toggleOuvert'])->name('rh.postes.toggle');
    Route::delete('/postes/{poste}', [PosteController::class, 'destroy'])->name('rh.postes.destroy');
});

$gerantAppPrefix = trim(config('cvanalyzer.gerant_app_prefix'), '/');
$adminAppPrefix = trim(config('cvanalyzer.admin_app_prefix'), '/');

Route::middleware(['auth', 'active', 'role:admin'])->prefix($gerantAppPrefix)->group(function () {
    Route::get('/', [GerantDashboardController::class, 'index'])->name('gerant.dashboard');
    Route::get('/export/rh', [GerantDashboardController::class, 'exportExcel'])->name('gerant.export.rh');
    Route::get('/rh', [GerantRhTeamController::class, 'index'])->name('gerant.rh.index');
    Route::post('/rh', [GerantRhTeamController::class, 'store'])->name('gerant.rh.store');
    Route::get('/rh/{rh}/edit', [GerantRhTeamController::class, 'edit'])->name('gerant.rh.edit');
    Route::put('/rh/{rh}', [GerantRhTeamController::class, 'update'])->name('gerant.rh.update');
    Route::patch('/rh/{rh}/actif', [GerantRhTeamController::class, 'toggleActif'])->name('gerant.rh.toggle');
    Route::delete('/rh/{rh}', [GerantRhTeamController::class, 'destroy'])->name('gerant.rh.destroy');
    Route::get('/entreprise', [GerantEntrepriseController::class, 'edit'])->name('gerant.entreprise');
    Route::put('/entreprise', [GerantEntrepriseController::class, 'update'])->name('gerant.entreprise.update');
});

Route::middleware(['auth', 'active', 'role:super_admin'])->prefix($adminAppPrefix)->group(function () use ($adminAppPrefix) {
    Route::get('/back-office', [BackOfficeController::class, 'index'])->name('admin.backoffice');
    Route::get('/back-office/export', [BackOfficeController::class, 'exportExcel'])->name('admin.backoffice.export');
    Route::get('/messages-contact', [MessageContactController::class, 'index'])->name('admin.messages-contact');
    Route::patch('/messages-contact/{messageContact}/lu', [MessageContactController::class, 'marquerLu'])
        ->name('admin.messages-contact.lu');
    Route::get('/gerants', [GerantController::class, 'index'])->name('admin.gerants');
    Route::post('/gerants', [GerantController::class, 'store'])->name('admin.gerants.store');
    Route::get('/gerants/{user}/edit', [GerantController::class, 'edit'])->name('admin.gerants.edit');
    Route::put('/gerants/{user}', [GerantController::class, 'update'])->name('admin.gerants.update');
    Route::patch('/gerants/{user}/actif', [GerantController::class, 'toggleActif'])->name('admin.gerants.toggle');
    Route::delete('/gerants/{user}', [GerantController::class, 'destroy'])->name('admin.gerants.destroy');
    Route::redirect('/super-admins', '/'.$adminAppPrefix.'/gerants');
    Route::redirect('/sous-admins', '/'.$adminAppPrefix.'/gerants');
});

Route::redirect('super-admin', '/'.$gerantAppPrefix);
Route::redirect('super-admin/{any}', '/'.$gerantAppPrefix)->where('any', '.*');
Route::any('admin', fn () => abort(404));
Route::any('admin/{any}', fn () => abort(404))->where('any', '.*');
