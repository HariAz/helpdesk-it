<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\KbArticleController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\RatingController;
use App\Http\Controllers\SlaConfigController;
use App\Http\Controllers\TicketTemplateController;
use App\Http\Controllers\WorkScheduleController;

// Auth
Route::get('/', fn() => redirect()->route('login'));
Route::get('/login', [AuthController::class, 'showLogin'])->name('login')->middleware('guest');
Route::post('/login', [AuthController::class, 'login'])->middleware('guest');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Rating (public, no auth)
Route::get('/rating/{token}', [RatingController::class, 'show'])->name('rating.show');
Route::post('/rating/{token}', [RatingController::class, 'store'])->name('rating.store');

// Authenticated routes
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Tickets
    Route::get('/tickets/export', [TicketController::class, 'export'])->name('tickets.export');
    Route::get('/tickets/trash', [TicketController::class, 'trash'])->name('tickets.trash')->middleware('role:supervisor');
    Route::patch('/tickets/{id}/restore', [TicketController::class, 'restore'])->name('tickets.restore')->middleware('role:supervisor');
    Route::get('/tickets', [TicketController::class, 'index'])->name('tickets.index');
    Route::get('/tickets/create', [TicketController::class, 'create'])->name('tickets.create');
    Route::post('/tickets', [TicketController::class, 'store'])->name('tickets.store');
    Route::get('/tickets/{ticket}', [TicketController::class, 'show'])->name('tickets.show');
    Route::patch('/tickets/{ticket}/status', [TicketController::class, 'updateStatus'])->name('tickets.status');
    Route::patch('/tickets/{ticket}/assign', [TicketController::class, 'assign'])->name('tickets.assign')->middleware('role:supervisor');
    Route::patch('/tickets/{ticket}/priority', [TicketController::class, 'updatePriority'])->name('tickets.priority')->middleware('role:supervisor');
    Route::delete('/tickets/{ticket}', [TicketController::class, 'destroy'])->name('tickets.destroy')->middleware('role:supervisor');

    // Comments
    Route::post('/tickets/{ticket}/comments', [CommentController::class, 'store'])->name('comments.store');
    Route::delete('/comments/{id}', [CommentController::class, 'destroy'])->name('comments.destroy');

    // Attachments
    Route::post('/tickets/{ticket}/attachments', [AttachmentController::class, 'store'])->name('attachments.store');
    Route::get('/attachments/{attachment}/download', [AttachmentController::class, 'download'])->name('attachments.download');
    Route::delete('/attachments/{attachment}', [AttachmentController::class, 'destroy'])->name('attachments.destroy');

    // Supervisor only
    Route::middleware('role:supervisor')->group(function () {
        // Users
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::patch('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::patch('/users/{user}/toggle', [UserController::class, 'toggle'])->name('users.toggle');
        Route::post('/users/{user}/reassign', [UserController::class, 'reassignTickets'])->name('users.reassign');

        // Categories
        Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
        Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
        Route::patch('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');

        // Reports
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');
        Route::get('/reports/export-pdf', [ReportController::class, 'exportPdf'])->name('reports.export-pdf');

        // Activity Logs
        Route::get('/activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');
        Route::get('/activity-logs/export', [ActivityLogController::class, 'export'])->name('activity-logs.export');

        // Settings
        Route::get('/settings/work-schedule', [WorkScheduleController::class, 'index'])->name('settings.work-schedule');
        Route::post('/settings/work-schedule', [WorkScheduleController::class, 'update'])->name('settings.work-schedule.update');
        Route::get('/settings/sla', [SlaConfigController::class, 'index'])->name('settings.sla');
        Route::post('/settings/sla', [SlaConfigController::class, 'update'])->name('settings.sla.update');
        Route::post('/settings/sla/override', [SlaConfigController::class, 'store'])->name('settings.sla.store');
        Route::delete('/settings/sla/{slaConfig}', [SlaConfigController::class, 'destroy'])->name('settings.sla.destroy');

        // KB management (supervisor/teknisi)
        Route::get('/knowledge-base/create', [KbArticleController::class, 'create'])->name('knowledge-base.create');
        Route::post('/knowledge-base', [KbArticleController::class, 'store'])->name('knowledge-base.store');
        Route::get('/knowledge-base/{knowledgeBase}/edit', [KbArticleController::class, 'edit'])->name('knowledge-base.edit');
        Route::patch('/knowledge-base/{knowledgeBase}', [KbArticleController::class, 'update'])->name('knowledge-base.update');
        Route::delete('/knowledge-base/{knowledgeBase}', [KbArticleController::class, 'destroy'])->name('knowledge-base.destroy');
        Route::post('/tickets/{ticket}/kb-attach', [KbArticleController::class, 'attachToTicket'])->name('tickets.kb-attach');
        Route::delete('/tickets/{ticket}/kb-detach/{knowledgeBase}', [KbArticleController::class, 'detachFromTicket'])->name('tickets.kb-detach');
    });

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/{id}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');

    // Ticket Templates (AJAX for all, CRUD for supervisor)
    Route::get('/ticket-templates/list', [TicketTemplateController::class, 'apiList'])->name('ticket-templates.list');

    Route::middleware('role:supervisor')->group(function () {
        Route::get('/ticket-templates', [TicketTemplateController::class, 'index'])->name('ticket-templates.index');
        Route::post('/ticket-templates', [TicketTemplateController::class, 'store'])->name('ticket-templates.store');
        Route::patch('/ticket-templates/{ticketTemplate}', [TicketTemplateController::class, 'update'])->name('ticket-templates.update');
        Route::delete('/ticket-templates/{ticketTemplate}', [TicketTemplateController::class, 'destroy'])->name('ticket-templates.destroy');
    });

    // Knowledge Base (readable by all auth users)
    Route::get('/knowledge-base', [KbArticleController::class, 'index'])->name('knowledge-base.index');
    Route::get('/knowledge-base/search', [KbArticleController::class, 'search'])->name('knowledge-base.search');
    Route::get('/knowledge-base/{knowledgeBase}', [KbArticleController::class, 'show'])->name('knowledge-base.show');

    // Category subcategories (AJAX)
    Route::get('/categories/{category}/subcategories', [CategoryController::class, 'subcategories'])->name('categories.subcategories');
});
