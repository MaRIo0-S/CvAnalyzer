<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AccountPasswordController;
use App\Http\Controllers\Admin\BackOfficeController;
use App\Http\Controllers\Admin\SubAdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CandidatController;
use App\Http\Controllers\CandidatNotificationController;
use App\Http\Controllers\GuestCvController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\Rh\CvController;
use App\Http\Controllers\Rh\DashboardController;
use App\Http\Controllers\Rh\PosteController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

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

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/compte', [AccountController::class, 'edit'])->name('account.edit');
    Route::put('/compte', [AccountController::class, 'update'])->name('account.update');
    Route::get('/compte/mot-de-passe', [AccountPasswordController::class, 'edit'])->name('account.password.edit');
    Route::put('/compte/mot-de-passe', [AccountPasswordController::class, 'update'])->name('account.password.update');
});

Route::middleware(['auth', 'role:candidat'])->prefix('candidat')->group(function () {
    Route::get('/statut', [CandidatController::class, 'statut'])->name('candidat.statut');
    Route::post('/notifications/tout-lu', [CandidatNotificationController::class, 'marquerToutLu'])
        ->name('candidat.notifications.tout-lu');
});

Route::middleware(['auth', 'role:sous_admin'])->prefix('rh')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('rh.dashboard');
    Route::get('/cvs', [DashboardController::class, 'filtrerPage'])->name('rh.filtrer.page');
    Route::post('/filtrer', [DashboardController::class, 'filtrer'])->name('rh.filtrer');
    Route::post('/filtrer/confirmer', [DashboardController::class, 'confirmerAnalyse'])->name('rh.filtrer.confirmer');
    Route::get('/filtrer/resultats', [DashboardController::class, 'derniereAnalyse'])->name('rh.filtrer.resultats');
    Route::patch('/cvs/{cv}/valider', [CvController::class, 'valider'])->name('rh.cv.valider');
    Route::get('/cvs/liste', [CvController::class, 'index'])->name('rh.cvs.liste');
    Route::get('/cvs/zip', [CvController::class, 'telechargerZip'])->name('rh.cvs.zip');
    Route::get('/cvs/{cv}/consulter', [CvController::class, 'show'])->name('rh.cv.consulter');
    Route::get('/cvs/{cv}/fichier', [CvController::class, 'fichier'])->name('rh.cv.fichier');
    Route::get('/cvs/{cv}/telecharger', [CvController::class, 'telecharger'])->name('rh.cv.telecharger');
    Route::get('/postes', [PosteController::class, 'index'])->name('rh.postes');
    Route::put('/entreprise', [PosteController::class, 'updateEntreprise'])->name('rh.entreprise.update');
    Route::post('/postes', [PosteController::class, 'store'])->name('rh.postes.store');
    Route::put('/postes/{poste}', [PosteController::class, 'update'])->name('rh.postes.update');
    Route::patch('/postes/{poste}/ouvert', [PosteController::class, 'toggleOuvert'])->name('rh.postes.toggle');
    Route::delete('/postes/{poste}', [PosteController::class, 'destroy'])->name('rh.postes.destroy');
});

Route::middleware(['auth', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('/back-office', [BackOfficeController::class, 'index'])->name('admin.backoffice');
    Route::get('/sous-admins', [SubAdminController::class, 'index'])->name('admin.subadmins');
    Route::post('/sous-admins', [SubAdminController::class, 'store'])->name('admin.subadmins.store');
    Route::delete('/sous-admins/{user}', [SubAdminController::class, 'destroy'])->name('admin.subadmins.destroy');
});
