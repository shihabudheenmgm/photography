<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\GalleryController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SocialAuthController;
use App\Http\Controllers\ShareableLinkController;
use App\Http\Controllers\ProfileImageController;

// Public routes
Route::get('/', [AuthenticatedSessionController::class, 'create'])->middleware('guest')->name('home');

// Authentication routes
Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login');
Route::get('/register', [RegisterController::class, 'show'])->name('register');

// Forgot and Reset Password
Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->middleware('guest')->name('password.request');
Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->middleware('guest')->name('password.email');
Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->middleware('guest')->name('password.reset');
Route::post('/reset-password', [NewPasswordController::class, 'store'])->middleware('guest')->name('password.store');

// Protected routes
Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard home
    Route::get('/dashboard', function () {
        $galleries = \App\Models\Gallery::where('user_id', auth()->id())->latest()->get();
        return Inertia::render('Dashboard/index', ['galleries' => $galleries]);
    })->name('dashboard');

    // Add Gallery
    Route::get('/dashboard/addgallery', function () {
        $galleries = \App\Models\Gallery::where('user_id', auth()->id())->latest()->get();
        return Inertia::render('Dashboard/index', [
            'galleries' => $galleries,
            'addGallery' => true,
        ]);
    })->name('dashboard.addgallery');

    // View Single Gallery
    Route::get('/dashboard/{id}', function ($id) {
        $gallery = \App\Models\Gallery::where('id', $id)->where('user_id', auth()->id())->firstOrFail();
        $galleries = \App\Models\Gallery::where('user_id', auth()->id())->latest()->get();
        return Inertia::render('Dashboard/index', [
            'galleries' => $galleries,
            'selectedGallery' => $gallery,
        ]);
    })->whereNumber('id', '[A-Za-z0-9]+');

    Route::get('/galleries', [GalleryController::class, 'index'])->name('galleries.index');
    Route::get('/galleries/{id}', [GalleryController::class, 'show'])->name('galleries.show');
    Route::post('/galleries', [GalleryController::class, 'store'])->name('galleries.store');
    Route::delete('/galleries/{gallery}/delete-media', [GalleryController::class, 'deleteMedia'])->name('galleries.deleteMedia');
    Route::delete('/galleries/{gallery}', [GalleryController::class, 'destroy'])->name('galleries.destroy');
    Route::put('/galleries/{gallery}', [GalleryController::class, 'update'])->name('galleries.update');
    Route::get('/dashboard/{gallery}/edit', [GalleryController::class, 'edit']);

    //cover image
    Route::post('/galleries/{gallery}/set-cover', [GalleryController::class, 'setCover'])->name('galleries.set-cover');
    Route::post('/galleries/{gallery}/update-cover-position', [GalleryController::class, 'updateCoverPosition']);

    // Profile routes
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.updatePassword');
    Route::get('/profile/{user}/{filename}', [ProfileController::class, 'getUploadedFile'])->name('profile.image');
    Route::post('/profile/image/delete', [ProfileController::class, 'deleteImage'])->middleware('auth');
    Route::post('/profile/logo/delete', [ProfileController::class, 'deleteLogo'])->name('profile.logo.delete');
    Route::get('/user/profile-image', [ProfileImageController::class, 'profileImageShow'])->name('user.profile.image');


    // Logout
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    //genarate gallery
    Route::post('/galleries/{gallery}/share', [ShareableLinkController::class, 'generate']);
    Route::get('/galleries/{id}/shared-link', [ShareableLinkController::class, 'getLink']);
    
});

// shareable link
Route::post('/galleries/{id}/enable-download', [ShareableLinkController::class, 'enableDownload']);
Route::get('/shared/{token}', [ShareableLinkController::class, 'show']);
Route::get('/shared/{token}/download', [ShareableLinkController::class, 'download']);

// Social login routes
Route::prefix('auth')->group(function () {
    Route::get('{provider}/redirect', [SocialAuthController::class, 'redirect']);
    Route::get('{provider}/callback', [SocialAuthController::class, 'callback']);
});

require __DIR__.'/auth.php';