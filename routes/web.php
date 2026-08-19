<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\ProgramController;
use App\Http\Controllers\Admin\LevelController;
use App\Http\Controllers\Admin\CourseClassController;
use App\Http\Controllers\Manager\DashboardController as ManagerDashboardController;
use App\Http\Controllers\Manager\CourseClassAssignmentController;
use App\Http\Controllers\Manager\ClassSessionController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::get('/dashboard', [App\Http\Controllers\DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Notifications
    Route::get('/notifications', [App\Http\Controllers\NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/{id}/read', [App\Http\Controllers\NotificationController::class, 'read'])->name('notifications.read');
    Route::post('/notifications/read-all', [App\Http\Controllers\NotificationController::class, 'readAll'])->name('notifications.readAll');

    // Messages
    Route::get('/messages', [App\Http\Controllers\MessageController::class, 'index'])->name('messages.index');
    Route::get('/messages/sent', [App\Http\Controllers\MessageController::class, 'sent'])->name('messages.sent');
    Route::get('/messages/create', [App\Http\Controllers\MessageController::class, 'create'])->name('messages.create');
    Route::post('/messages', [App\Http\Controllers\MessageController::class, 'store'])->name('messages.store');
    Route::get('/messages/{message}', [App\Http\Controllers\MessageController::class, 'show'])->name('messages.show');
});

// Admin Routes
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::resource('users', UserController::class);
    Route::resource('programs', ProgramController::class);
    Route::resource('levels', LevelController::class);
    Route::resource('classes', CourseClassController::class);
    Route::get('/classes/{class}/assign', [CourseClassController::class, 'assign'])->name('classes.assign.edit');
    Route::post('/classes/{class}/assign', [CourseClassController::class, 'assignUpdate'])->name('classes.assign.update');
    // Onglet « Évaluations » : archive des devoirs rendus, en consultation seule.
    // Les avis des apprenants ne sont plus ici, ils figurent desormais dans le
    // detail de la session, aux cotes du rapport du coach.
    // Plan de paiement d'un apprenant : cout total, avance et echeancier.
    // C'est ce plan qui alimente les rappels d'echeance.
    Route::get('/apprenants/{student}/plan-paiement', [\App\Http\Controllers\Admin\PaymentPlanController::class, 'edit'])->name('students.plan.edit');
    Route::post('/apprenants/{student}/plan-paiement', [\App\Http\Controllers\Admin\PaymentPlanController::class, 'store'])->name('students.plan.store');
    Route::patch('/echeances/{echeance}/payee', [\App\Http\Controllers\Admin\PaymentPlanController::class, 'marquerPayee'])->name('echeances.payee');

    Route::get('/submissions', [\App\Http\Controllers\Admin\SubmissionArchiveController::class, 'index'])->name('submissions.index');
    Route::get('/submissions/{submission}', [\App\Http\Controllers\Admin\SubmissionArchiveController::class, 'show'])->name('submissions.show');
});

// Manager Routes
Route::middleware(['auth', 'role:manager|admin'])->prefix('manager')->name('manager.')->group(function () {
    Route::get('/dashboard', [ManagerDashboardController::class, 'index'])->name('dashboard');
    Route::get('/classes', [CourseClassAssignmentController::class, 'index'])->name('classes.index');
    Route::get('/classes/{courseClass}/assign', [CourseClassAssignmentController::class, 'edit'])->name('classes.assign.edit');
    Route::post('/classes/{courseClass}/assign', [CourseClassAssignmentController::class, 'update'])->name('classes.assign.update');
    Route::resource('sessions', ClassSessionController::class);
    Route::resource('payments', \App\Http\Controllers\Manager\PaymentController::class);
    Route::post('payments/{payment}/pay', [\App\Http\Controllers\Manager\PaymentController::class, 'markAsPaid'])->name('payments.pay');
    // Reglement groupe : evite de repeter le meme clic sur chaque ligne.
    Route::post('payments/pay-many', [\App\Http\Controllers\Manager\PaymentController::class, 'markManyAsPaid'])->name('payments.payMany');
});

// Coach Routes
Route::middleware(['auth', 'role:coach'])->prefix('coach')->name('coach.')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Coach\DashboardController::class, 'index'])->name('dashboard');
    
    // Sessions & Reporting
    Route::resource('sessions', \App\Http\Controllers\Coach\SessionController::class)->only(['index', 'create', 'store', 'show']);
    Route::patch('/sessions/{session}/cancel', [\App\Http\Controllers\Coach\SessionController::class, 'cancel'])->name('sessions.cancel');
    Route::get('/sessions/{session}/report', [\App\Http\Controllers\Coach\SessionReportController::class, 'create'])->name('sessions.report.create');
    Route::post('/sessions/{session}/report', [\App\Http\Controllers\Coach\SessionReportController::class, 'store'])->name('sessions.report.store');
    Route::get('/reports', [\App\Http\Controllers\Coach\SessionReportController::class, 'index'])->name('reports.index');

    // Pages de l'espace formateur, extraites du tableau de bord.
    Route::get('/programmes', [\App\Http\Controllers\Coach\EspaceController::class, 'programmes'])->name('programmes.index');
    Route::get('/classes', [\App\Http\Controllers\Coach\EspaceController::class, 'classes'])->name('classes.index');
    Route::get('/prochains-cours', [\App\Http\Controllers\Coach\EspaceController::class, 'prochainsCours'])->name('cours.index');

    // Assignments & Evaluations
    Route::resource('assignments', \App\Http\Controllers\Coach\AssignmentController::class)->except(['show']);
    Route::get('/assignments/{assignment}/submissions', [\App\Http\Controllers\Coach\EvaluationController::class, 'index'])->name('evaluations.index');
    Route::post('/submissions/{submission}/evaluate', [\App\Http\Controllers\Coach\EvaluationController::class, 'store'])->name('evaluations.store');

    // Payments
    Route::get('/payments', [\App\Http\Controllers\Coach\PaymentController::class, 'index'])->name('payments.index');
});

// Student Routes
Route::middleware(['auth', 'role:student'])->prefix('student')->name('student.')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Student\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/assignments', [\App\Http\Controllers\Student\AssignmentController::class, 'index'])->name('assignments.index');
    Route::get('/assignments/{assignment}', [\App\Http\Controllers\Student\AssignmentController::class, 'show'])->name('assignments.show');
    Route::post('/assignments/{assignment}/submit', [\App\Http\Controllers\Student\AssignmentController::class, 'submit'])->name('assignments.submit');
    Route::get('/sessions/{session}/feedback', [\App\Http\Controllers\Student\SessionFeedbackController::class, 'create'])->name('sessions.feedback.create');
    Route::post('/sessions/{session}/feedback', [\App\Http\Controllers\Student\SessionFeedbackController::class, 'store'])->name('sessions.feedback.store');
    
    // Payments
    Route::get('/payments', [\App\Http\Controllers\Student\PaymentController::class, 'index'])->name('payments.index');

    // Pages de l'espace apprenant.
    Route::get('/programme', [\App\Http\Controllers\Student\EspaceController::class, 'programme'])->name('programme.index');
    Route::get('/emploi-du-temps', [\App\Http\Controllers\Student\EspaceController::class, 'emploiDuTemps'])->name('emploi.index');
    Route::get('/progression', [\App\Http\Controllers\Student\EspaceController::class, 'progression'])->name('progression.index');
});

// Niveau d'anglais de l'apprenant : positionnement initial et promotion.
// Ouvert au coach comme a l'administrateur, la decision etant manuelle.
Route::middleware(['auth', 'role:coach|admin'])->group(function () {
    Route::get('/apprenants/{student}/niveau', [\App\Http\Controllers\StudentLevelController::class, 'edit'])->name('students.level.edit');
    Route::put('/apprenants/{student}/niveau', [\App\Http\Controllers\StudentLevelController::class, 'update'])->name('students.level.update');
});

// Maintenance depuis le navigateur : l'hebergement mutualise ne donne pas
// acces a un terminal. Protegee par MAINTENANCE_TOKEN ; sans ce jeton dans
// le .env, ces routes repondent 404.
Route::get('/maintenance/{action?}', \App\Http\Controllers\MaintenanceController::class)
     ->where('action', '[a-z-]+')
     ->name('maintenance');

require __DIR__.'/auth.php';
