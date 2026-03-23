<?php

use Illuminate\Support\Facades\Route;

// ── Auth ──────────────────────────────────────────────────────────────────────
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Api\Auth\PasswordResetController;

// ── Admin ─────────────────────────────────────────────────────────────────────
use App\Http\Controllers\Api\Admin\AdminController;

// ── Company ───────────────────────────────────────────────────────────────────
use App\Http\Controllers\Api\Company\CompanyController;
use App\Http\Controllers\Api\Company\InterviewController as CompanyInterviewController;

// ── Recruiter ─────────────────────────────────────────────────────────────────
use App\Http\Controllers\Api\Recruiter\InternshipController as RecruiterInternshipController;

// ── Student ───────────────────────────────────────────────────────────────────
use App\Http\Controllers\Api\Student\StudentController;
use App\Http\Controllers\Api\Student\ApplicationController;
use App\Http\Controllers\Api\Student\DocumentController;
use App\Http\Controllers\Api\Student\InterviewController as StudentInterviewController;
use App\Http\Controllers\Api\Student\ReportController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\LocationController;

Route::prefix('v1')->group(function () {

    // ── Registration & Authentication (Rate Limited) ─────────────────────────
    Route::middleware('throttle:auth')->group(function () {
        Route::post('/register',       [RegisterController::class, 'register']);
        Route::post('/verify-account', [RegisterController::class, 'verifyAccount']);
        Route::post('/resend-otp',     [RegisterController::class, 'resendOtp']);
        Route::get('/captcha',         [RegisterController::class, 'getCaptcha']);
        Route::post('/login',          [AuthController::class, 'login']);
    });

    // ── Password Reset ────────────────────────────────────────────────────────
    Route::post('/forgot-password',  [PasswordResetController::class, 'forgotPassword']);
    Route::post('/verify-reset-otp', [PasswordResetController::class, 'verifyResetOtp']);
    Route::post('/reset-password',   [PasswordResetController::class, 'resetPassword']);

    // ── Public Internship Browsing ────────────────────────────────────────────
    Route::get('/internships',              [RecruiterInternshipController::class, 'index']);
    Route::get('/internships/{internship}', [RecruiterInternshipController::class, 'show']);

    // ── Public Helpers ────────────────────────────────────────────────────────
    Route::get('/countries', [LocationController::class, 'getCountries']);

    // ── Protected Routes ──────────────────────────────────────────────────────
    Route::middleware('auth:sanctum')->group(function () {

        // Auth session
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me',      [AuthController::class, 'me']);

        // ── Notifications ─────────────────────────────────────────────────────
        Route::get('/notifications',              [NotificationController::class, 'index']);
        Route::get('/notifications/unread',       [NotificationController::class, 'unread']);
        Route::patch('/notifications/{id}/read',  [NotificationController::class, 'markAsRead']);
        Route::post('/notifications/read-all',    [NotificationController::class, 'markAllAsRead']);
        Route::delete('/notifications/{id}',      [NotificationController::class, 'destroy']);

        // ── Student Routes ────────────────────────────────────────────────────
        Route::prefix('student')->group(function () {
            Route::get('/profile',    [StudentController::class, 'profile']);
            Route::match(['put', 'patch'], '/profile', [StudentController::class, 'updateProfile']);
            Route::get('/interviews', [StudentInterviewController::class, 'studentIndex']);
            Route::get('/saved-internships', [\App\Http\Controllers\Api\Student\SavedInternshipController::class, 'index']);
            Route::get('/recommendations', [\App\Http\Controllers\Api\Student\RecommendationController::class, 'index']);
            Route::delete('/account', [AuthController::class, 'deleteAccount'])->middleware('throttle:sensitive');
        });

        // Documents (student)
        Route::post('/documents/upload',   [DocumentController::class, 'upload'])->middleware('throttle:uploads');
        Route::get('/documents',           [DocumentController::class, 'index']);
        Route::delete('/documents/{type}', [DocumentController::class, 'destroy']);

        // Applications & Interactions (student)
        Route::get('/applications',                  [ApplicationController::class, 'studentIndex']);
        Route::delete('/applications/{application}', [ApplicationController::class, 'destroy']);
        Route::post('/internships/{internship}/apply', [ApplicationController::class, 'store']);
        Route::post('/internships/{internship}/report', [ReportController::class, 'store']);
        Route::post('/internships/{internship}/save', [\App\Http\Controllers\Api\Student\SavedInternshipController::class, 'toggle']);

        // ── Company Routes ────────────────────────────────────────────────────
        Route::prefix('company')->group(function () {
            Route::get('/profile',   [CompanyController::class, 'profile']);
            Route::patch('/profile', [CompanyController::class, 'updateProfile']);
            Route::post('/logo',     [CompanyController::class, 'uploadLogo'])->middleware('throttle:uploads');
            Route::delete('/logo',   [CompanyController::class, 'deleteLogo']);

            // Interview management
            Route::get('/interviews',                     [CompanyInterviewController::class, 'index']);
            Route::post('/interviews',                    [CompanyInterviewController::class, 'store']);
            Route::put('/interviews/{interview}',         [CompanyInterviewController::class, 'update']);
            Route::post('/interviews/{interview}/cancel', [CompanyInterviewController::class, 'cancel']);
            Route::delete('/interviews/{interview}',      [CompanyInterviewController::class, 'destroy']);

            // Dashboard stats
            Route::get('/dashboard-stats', [CompanyController::class, 'dashboardStats']);
            Route::delete('/account', [CompanyController::class, 'deleteAccount'])->middleware('throttle:sensitive');
        });

        // ── Recruiter Routes ──────────────────────────────────────────────────
        Route::prefix('recruiter')->group(function () {
            // Profile & Settings
            Route::get('/profile',    [\App\Http\Controllers\Api\Recruiter\RecruiterController::class, 'profile']);
            Route::patch('/profile',  [\App\Http\Controllers\Api\Recruiter\RecruiterController::class, 'updateProfile']);
            Route::post('/document',  [\App\Http\Controllers\Api\Recruiter\RecruiterController::class, 'uploadDocument'])->middleware('throttle:uploads');
            Route::patch('/settings', [\App\Http\Controllers\Api\Recruiter\RecruiterController::class, 'updateSettings'])->middleware('throttle:sensitive');

            // Dashboard stats
            Route::get('/dashboard-stats', [\App\Http\Controllers\Api\Recruiter\DashboardController::class, 'index']);
            
            // Student Discovery
            Route::get('/discover/students', [\App\Http\Controllers\Api\Recruiter\DiscoveryController::class, 'searchStudents']);
            Route::get('/internships/{internship}/recommended-students', [\App\Http\Controllers\Api\Recruiter\DiscoveryController::class, 'recommendedStudents']);
            
            // Saved Candidates
            Route::get('/saved-candidates', [\App\Http\Controllers\Api\Recruiter\SavedCandidateController::class, 'index']);
            Route::post('/saved-candidates', [\App\Http\Controllers\Api\Recruiter\SavedCandidateController::class, 'store']);
            Route::delete('/saved-candidates/{student}', [\App\Http\Controllers\Api\Recruiter\SavedCandidateController::class, 'destroy']);
            
            // Internship management
            Route::post('/internships',                [RecruiterInternshipController::class, 'store']);
            Route::get('/my-internships',              [RecruiterInternshipController::class, 'recruiterIndex']);
            Route::put('/internships/{internship}',    [RecruiterInternshipController::class, 'update']);
            Route::delete('/internships/{internship}', [RecruiterInternshipController::class, 'destroy']);
            Route::delete('/account', [\App\Http\Controllers\Api\Recruiter\RecruiterController::class, 'deleteAccount'])->middleware('throttle:sensitive');
        });


        // Applications (company side)
        Route::get('/internships/{internship}/applications', [ApplicationController::class, 'index']);
        Route::patch('/applications/{application}/status',   [ApplicationController::class, 'updateStatus']);

        // Company views a student profile
        Route::get('/student/profile/{id}', [StudentController::class, 'show']);

        // ── Admin Routes ──────────────────────────────────────────────────────
        Route::prefix('admin')->group(function () {
            Route::get('/dashboard',   [AdminController::class, 'dashboard']);
            Route::get('/users',       [AdminController::class, 'users']);
            Route::get('/internships', [AdminController::class, 'internships']);
            Route::get('/reports',     [AdminController::class, 'reports']);
            
            // Moderation
            Route::get('/moderation/reports',     [AdminController::class, 'moderationReports']);
            Route::patch('/recruiters/{recruiter}/verify', [AdminController::class, 'verifyRecruiter']);
            Route::patch('/recruiters/{recruiter}/ban',    [AdminController::class, 'banRecruiter']);
            Route::patch('/reports/{report}/resolve',      [AdminController::class, 'resolveReport']);

            // User Management CRUD
            Route::get('/users/{type}/{id}',            [AdminController::class, 'showUser']);
            Route::patch('/users/{type}/{id}/toggle-ban', [AdminController::class, 'toggleBan']);
            Route::patch('/users/{type}/{id}/toggle-verify', [AdminController::class, 'toggleVerification']);
            Route::delete('/users/{type}/{id}',         [AdminController::class, 'deleteUser']); // Soft Delete
            Route::post('/users/{type}/{id}/restore',    [AdminController::class, 'restoreUser']);
            Route::delete('/users/{type}/{id}/force',    [AdminController::class, 'forceDeleteUser']);

            // Campus Ambassadors Management
            Route::get('/campus-ambassadors', [\App\Http\Controllers\Api\CampusAmbassadorController::class, 'index']);
            Route::patch('/campus-ambassadors/{id}/status', [\App\Http\Controllers\Api\CampusAmbassadorController::class, 'updateStatus']);
        });

        // ── Campus Ambassador (Student/Public) ────────────────────────────────
        Route::get('/ambassadors/leaderboard',            [\App\Http\Controllers\Api\CampusAmbassadorController::class, 'leaderboard']);
        Route::get('/ambassadors/university-leaderboard', [\App\Http\Controllers\Api\CampusAmbassadorController::class, 'universityLeaderboard']);
        Route::get('/ambassadors/status',                 [\App\Http\Controllers\Api\CampusAmbassadorController::class, 'status']);
        Route::post('/ambassadors/apply',                 [\App\Http\Controllers\Api\CampusAmbassadorController::class, 'apply']);
    });
});