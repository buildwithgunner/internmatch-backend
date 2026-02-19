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
use App\Http\Controllers\Api\Company\InternshipController;
use App\Http\Controllers\Api\Company\InterviewController as CompanyInterviewController;

// ── Student ───────────────────────────────────────────────────────────────────
use App\Http\Controllers\Api\Student\StudentController;
use App\Http\Controllers\Api\Student\ApplicationController;
use App\Http\Controllers\Api\Student\DocumentController;
use App\Http\Controllers\Api\Student\InterviewController as StudentInterviewController;

Route::prefix('v1')->group(function () {

    // ── Registration & OTP Verification ──────────────────────────────────────
    Route::post('/register',       [RegisterController::class, 'register']);
    Route::post('/verify-account', [RegisterController::class, 'verifyAccount']);
    Route::post('/resend-otp',     [RegisterController::class, 'resendOtp']);

    // ── Authentication ────────────────────────────────────────────────────────
    Route::post('/login', [AuthController::class, 'login']);

    // ── Password Reset ────────────────────────────────────────────────────────
    Route::post('/forgot-password',  [PasswordResetController::class, 'forgotPassword']);
    Route::post('/verify-reset-otp', [PasswordResetController::class, 'verifyResetOtp']);
    Route::post('/reset-password',   [PasswordResetController::class, 'resetPassword']);

    // ── Public Internship Browsing ────────────────────────────────────────────
    Route::get('/internships',              [InternshipController::class, 'index']);
    Route::get('/internships/{internship}', [InternshipController::class, 'show']);

    // ── Protected Routes ──────────────────────────────────────────────────────
    Route::middleware('auth:sanctum')->group(function () {

        // Auth session
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me',      [AuthController::class, 'me']);

        // ── Student Routes ────────────────────────────────────────────────────
        Route::prefix('student')->group(function () {
            Route::get('/profile',    [StudentController::class, 'profile']);
            Route::patch('/profile',  [StudentController::class, 'updateProfile']);
            Route::get('/interviews', [StudentInterviewController::class, 'studentIndex']);
        });

        // Documents (student)
        Route::post('/documents/upload',   [DocumentController::class, 'upload']);
        Route::get('/documents',           [DocumentController::class, 'index']);
        Route::delete('/documents/{type}', [DocumentController::class, 'destroy']);

        // Applications (student)
        Route::get('/applications',                  [ApplicationController::class, 'studentIndex']);
        Route::delete('/applications/{application}', [ApplicationController::class, 'destroy']);
        Route::post('/internships/{internship}/apply', [ApplicationController::class, 'store']);

        // ── Company Routes ────────────────────────────────────────────────────
        Route::prefix('company')->group(function () {
            Route::get('/profile',   [CompanyController::class, 'profile']);
            Route::patch('/profile', [CompanyController::class, 'updateProfile']);
            Route::post('/logo',     [CompanyController::class, 'uploadLogo']);
            Route::delete('/logo',   [CompanyController::class, 'deleteLogo']);

            // Internship management
            Route::post('/internships',                [InternshipController::class, 'store']);
            Route::get('/my-internships',              [InternshipController::class, 'companyIndex']);
            Route::put('/internships/{internship}',    [InternshipController::class, 'update']);
            Route::delete('/internships/{internship}', [InternshipController::class, 'destroy']);

            // Interview management
            Route::get('/interviews',                     [CompanyInterviewController::class, 'index']);
            Route::post('/interviews',                    [CompanyInterviewController::class, 'store']);
            Route::put('/interviews/{interview}',         [CompanyInterviewController::class, 'update']);
            Route::post('/interviews/{interview}/cancel', [CompanyInterviewController::class, 'cancel']);
            Route::delete('/interviews/{interview}',      [CompanyInterviewController::class, 'destroy']);

            // Dashboard stats
            Route::get('/dashboard-stats', [CompanyController::class, 'dashboardStats']);
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
        });
    });
});