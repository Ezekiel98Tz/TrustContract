<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\ContractReviewController;
use App\Http\Controllers\CounterpartyController;
use App\Http\Controllers\NotificationController as WebNotificationController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Models\Contract; // For dashboard stats

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    // Basic stats for dashboard
    $user = auth()->user();
    $stats = [
        'total' => Contract::where('buyer_id', $user->id)->orWhere('seller_id', $user->id)->count(),
        'active' => Contract::where(function($q) use ($user) {
            $q->where('buyer_id', $user->id)->orWhere('seller_id', $user->id);
        })->whereIn('status', ['draft', 'pending_approval', 'signed'])->count(),
        'completed' => Contract::where(function($q) use ($user) {
            $q->where('buyer_id', $user->id)->orWhere('seller_id', $user->id);
        })->where('status', 'finalized')->count(),
    ];
    
    return Inertia::render('Dashboard', [
        'stats' => $stats
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\VerificationController as AdminVerificationController;
use App\Http\Controllers\PersonalInformationController;
use App\Http\Controllers\Account\DeviceController;

Route::middleware(['auth', 'verified', \App\Http\Middleware\EnsureTwoFactorVerified::class, \App\Http\Middleware\EnsureDeviceNotRevoked::class])->group(function () {
    Route::get('/contracts', [ContractController::class, 'index'])->name('contracts.index');
    Route::get('/contracts/create', [ContractController::class, 'create'])->name('contracts.create');
    Route::post('/contracts', [ContractController::class, 'store'])->name('contracts.store');
    Route::get('/contracts/{contract}', [ContractController::class, 'show'])->name('contracts.show');
    Route::post('/contracts/{contract}/sign', [ContractController::class, 'sign'])->name('contracts.sign');
    Route::post('/contracts/{contract}/reviews', [ContractReviewController::class, 'store'])->name('contracts.reviews.store');
    Route::get('/counterparties/search', [CounterpartyController::class, 'search'])->name('counterparties.search');
    Route::get('/counterparties/{id}', [CounterpartyController::class, 'insights'])->name('counterparties.insights');
    Route::get('/counterparties/{id}/reviews', function (\Illuminate\Http\Request $request, $id) {
        $user = \App\Models\User::findOrFail($id);
        $avg = \App\Models\ContractReview::where('reviewee_id', $user->id)->avg('rating');
        $count = \App\Models\ContractReview::where('reviewee_id', $user->id)->count();
        $reviews = \App\Models\ContractReview::where('reviewee_id', $user->id)->with(['reviewer'])->latest()->paginate(10);
        return \Inertia\Inertia::render('Counterparties/Reviews', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'verification_status' => $user->verification_status,
                'verification_level' => $user->verification_level ?? 'none',
            ],
            'rating_avg' => $avg ? round($avg, 1) : null,
            'rating_count' => $count,
            'reviews' => $reviews->through(function ($rv) {
                return [
                    'id' => $rv->id,
                    'rating' => $rv->rating,
                    'comment' => $rv->comment,
                    'created_at' => $rv->created_at,
                    'reviewer' => $rv->reviewer ? ['id' => $rv->reviewer->id, 'name' => $rv->reviewer->name] : null,
                ];
            }),
        ]);
    })->name('counterparties.reviews');
    Route::get('/contracts/{contract}/pdf', [ContractController::class, 'downloadPdf'])->name('contracts.pdf');
    Route::get('/contracts/{contract}/print', [ContractController::class, 'print'])->name('contracts.print');
    Route::delete('/contracts/{contract}', [ContractController::class, 'destroy'])->name('contracts.destroy');
    Route::get('/notifications', [WebNotificationController::class, 'index'])->name('notifications.index');
    Route::patch('/notifications/{id}/read', [WebNotificationController::class, 'read'])->name('notifications.read');
    Route::patch('/notifications/read-all', [WebNotificationController::class, 'readAll'])->name('notifications.read_all');
});

Route::middleware(['auth', 'verified', \App\Http\Middleware\EnsureTwoFactorVerified::class, \App\Http\Middleware\EnsureDeviceNotRevoked::class, 'role:Admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::get('/users/{user}', [AdminUserController::class, 'show'])->name('users.show');
    Route::post('/users/{user}/verify', [AdminUserController::class, 'verify'])->name('users.verify');
    Route::post('/users/{user}/unverify', [AdminUserController::class, 'unverify'])->name('users.unverify');
    
    Route::get('/verifications', [AdminVerificationController::class, 'index'])->name('verifications.index');
    Route::patch('/verifications/{verification}/review', [AdminVerificationController::class, 'review'])->name('verifications.review');
});

Route::middleware(['auth', \App\Http\Middleware\EnsureTwoFactorVerified::class])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/account/personal-information', [PersonalInformationController::class, 'edit'])->name('account.personal-information.edit');
    Route::patch('/account/personal-information', [PersonalInformationController::class, 'update'])->name('account.personal-information.update');
    Route::post('/account/personal-information/submit-id', [PersonalInformationController::class, 'submitId'])->name('account.personal-information.submit-id');

    Route::get('/account/devices', [DeviceController::class, 'index'])->name('account.devices.index');
    Route::post('/account/devices/{id}/revoke', [DeviceController::class, 'revoke'])->name('account.devices.revoke');
});

require __DIR__.'/auth.php';
