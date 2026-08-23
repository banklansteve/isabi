<?php

use App\Enums\UserRole;
use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\AnalyticsAdminController;
use App\Http\Controllers\Admin\AnnouncementAdminController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\Auth\AcceptInviteController;
use App\Http\Controllers\Admin\Auth\AuthenticatedSessionController as AdminAuthenticatedSessionController;
use App\Http\Controllers\Admin\Auth\NewPasswordController as AdminNewPasswordController;
use App\Http\Controllers\Admin\Auth\PasswordResetLinkController as AdminPasswordResetLinkController;
use App\Http\Controllers\Admin\Auth\SetPasswordController;
use App\Http\Controllers\Admin\Auth\VerifyInvitationController;
use App\Http\Controllers\Admin\CreditAdminController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\FinancialAdminController;
use App\Http\Controllers\Admin\Hr\HrChecklistController;
use App\Http\Controllers\Admin\Hr\HrController;
use App\Http\Controllers\Admin\Hr\HrDisciplineController;
use App\Http\Controllers\Admin\Hr\HrDocumentController;
use App\Http\Controllers\Admin\Hr\HrLeaveController;
use App\Http\Controllers\Admin\Hr\HrPayrollController;
use App\Http\Controllers\Admin\Hr\HrPerformanceController;
use App\Http\Controllers\Admin\Hr\HrProfileController;
use App\Http\Controllers\Admin\Hr\HrReportController;
use App\Http\Controllers\Admin\Hr\HrSettingsController;
use App\Http\Controllers\Admin\JobAdminController;
use App\Http\Controllers\Admin\PricingAdminController;
use App\Http\Controllers\Admin\ReferralAdminController;
use App\Http\Controllers\Admin\ReviewAdminController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\StaffAssignmentController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Admin\StaffRoleController;
use App\Http\Controllers\Admin\SupportController;
use App\Http\Controllers\Admin\UserAdminController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('login', [AdminAuthenticatedSessionController::class, 'create'])->name('login');
        Route::post('login', [AdminAuthenticatedSessionController::class, 'store']);

        Route::get('forgot-password', [AdminPasswordResetLinkController::class, 'create'])
            ->name('password.request');
        Route::post('forgot-password', [AdminPasswordResetLinkController::class, 'store'])
            ->name('password.email');
        Route::get('reset-password/{token}', [AdminNewPasswordController::class, 'create'])
            ->name('password.reset');
        Route::post('reset-password', [AdminNewPasswordController::class, 'store'])
            ->name('password.store');

        Route::get('invite/accept/{token}', [AcceptInviteController::class, 'create'])
            ->name('invite.accept');
        Route::post('invite/accept/{token}', [AcceptInviteController::class, 'store'])
            ->middleware('throttle:10,1');

        Route::get('invite/verify', [VerifyInvitationController::class, 'create'])
            ->name('invite.verify');
        Route::post('invite/verify', [VerifyInvitationController::class, 'store'])
            ->middleware('throttle:10,1');

        Route::get('invite/password', [SetPasswordController::class, 'create'])
            ->middleware('staff.invitation')
            ->name('invite.password');
        Route::post('invite/password', [SetPasswordController::class, 'store'])
            ->middleware('staff.invitation');
    });

    Route::post('logout', [AdminAuthenticatedSessionController::class, 'destroy'])
        ->middleware('auth')
        ->name('logout');

    Route::middleware(['auth', 'staff'])->group(function () {
        Route::get('/', AdminDashboardController::class)->name('dashboard');

        Route::middleware('ability:admin.users.view')->group(function () {
            Route::get('users', [UserAdminController::class, 'index'])->name('users.index');
            Route::get('users/{user}', [UserAdminController::class, 'show'])->name('users.show');
        });
        Route::post('users/bulk-suspend', [UserAdminController::class, 'bulkSuspend'])->name('users.bulk-suspend');
        Route::post('users/bulk-message', [UserAdminController::class, 'bulkMessage'])->name('users.bulk-message');
        Route::post('users/bulk-export', [UserAdminController::class, 'bulkExport'])->name('users.bulk-export');
        Route::patch('users/{user}', [UserAdminController::class, 'update'])->name('users.update');
        Route::post('users/{user}/suspend', [UserAdminController::class, 'suspend'])->name('users.suspend');
        Route::post('users/{user}/reinstate', [UserAdminController::class, 'reinstate'])->name('users.reinstate');
        Route::post('users/{user}/verify', [UserAdminController::class, 'verify'])->name('users.verify');
        Route::post('users/{user}/unverify', [UserAdminController::class, 'unverify'])->name('users.unverify');
        Route::post('users/{user}/plan', [UserAdminController::class, 'updatePlan'])->name('users.plan');
        Route::post('users/{user}/credits', [UserAdminController::class, 'adjustCredits'])->name('users.credits');
        Route::post('users/{user}/impersonate', [UserAdminController::class, 'impersonate'])->name('users.impersonate');
        Route::post('users/{user}/logout', [UserAdminController::class, 'forceLogout'])->name('users.logout');
        Route::post('users/{user}/password-reset', [UserAdminController::class, 'sendPasswordReset'])->name('users.password-reset');
        Route::post('users/{user}/delete', [UserAdminController::class, 'destroy'])->name('users.destroy');

        Route::get('jobs', [JobAdminController::class, 'index'])->middleware('ability:admin.content.manage')->name('jobs.index');
        Route::post('jobs/{workLog}/flag', [JobAdminController::class, 'flag'])->name('jobs.flag');
        Route::post('jobs/{workLog}/unflag', [JobAdminController::class, 'unflag'])->name('jobs.unflag');
        Route::patch('jobs/{workLog}', [JobAdminController::class, 'update'])->name('jobs.update');
        Route::post('jobs/{workLog}/hide', [JobAdminController::class, 'hide'])->name('jobs.hide');

        Route::get('reviews', [ReviewAdminController::class, 'index'])->middleware('ability:admin.content.manage')->name('reviews.index');
        Route::post('reviews/{review}/flag', [ReviewAdminController::class, 'flag'])->name('reviews.flag');
        Route::post('reviews/{review}/unflag', [ReviewAdminController::class, 'unflag'])->name('reviews.unflag');
        Route::post('reviews/{review}/hide', [ReviewAdminController::class, 'hide'])->name('reviews.hide');

        Route::get('credits', [CreditAdminController::class, 'index'])->middleware('ability:admin.credits.view')->name('credits.index');
        Route::post('credits/adjust', [CreditAdminController::class, 'adjust'])->name('credits.adjust');

        Route::get('referrals', [ReferralAdminController::class, 'index'])->name('referrals.index');

        Route::get('analytics', [AnalyticsAdminController::class, 'index'])->middleware('ability:admin.analytics.view')->name('analytics.index');

        Route::get('messaging', [AnnouncementAdminController::class, 'index'])->middleware('ability:admin.messaging.manage')->name('messaging.index');
        Route::get('messaging/create', [AnnouncementAdminController::class, 'create'])->name('messaging.create');
        Route::post('messaging', [AnnouncementAdminController::class, 'store'])->name('messaging.store');
        Route::post('messaging/preview', [AnnouncementAdminController::class, 'preview'])->name('messaging.preview');
        Route::get('messaging/templates', [AnnouncementAdminController::class, 'templates'])->name('messaging.templates');
        Route::post('messaging/templates', [AnnouncementAdminController::class, 'storeTemplate'])->name('messaging.templates.store');
        Route::patch('messaging/templates/{template}', [AnnouncementAdminController::class, 'updateTemplate'])->name('messaging.templates.update');
        Route::delete('messaging/templates/{template}', [AnnouncementAdminController::class, 'destroyTemplate'])->name('messaging.templates.destroy');
        Route::get('messaging/{announcement}', [AnnouncementAdminController::class, 'show'])->name('messaging.show');
        Route::post('messaging/{announcement}/send', [AnnouncementAdminController::class, 'send'])->name('messaging.send');
        Route::post('messaging/{announcement}/cancel', [AnnouncementAdminController::class, 'cancel'])->name('messaging.cancel');

        Route::get('support', [SupportController::class, 'index'])->middleware('ability:admin.support.manage')->name('support.index');
        Route::get('support/{ticket}', [SupportController::class, 'show'])->name('support.show');
        Route::post('support/{ticket}/reply', [SupportController::class, 'reply'])->name('support.reply');
        Route::post('support/{ticket}/resolve', [SupportController::class, 'resolve'])->name('support.resolve');

        Route::middleware('role:'.UserRole::SuperAdmin->value)->group(function () {
            Route::get('activity', ActivityLogController::class)->name('activity');
            Route::get('audit', [AuditLogController::class, 'index'])->name('audit.index');

            Route::get('staff', [StaffController::class, 'index'])->name('staff.index');
            Route::post('staff', [StaffController::class, 'store'])->name('staff.store');
            Route::get('staff/{staff}', [StaffController::class, 'show'])->name('staff.show');
            Route::post('staff/{staff}/resend', [StaffController::class, 'resend'])->name('staff.resend');
            Route::post('staff/{staff}/revoke', [StaffController::class, 'revoke'])->name('staff.revoke');
            Route::post('staff/{staff}/disable', [StaffController::class, 'disable'])->name('staff.disable');
            Route::post('staff/{staff}/reinstate', [StaffController::class, 'reinstate'])->name('staff.reinstate');
            Route::post('staff/{staff}/delete', [StaffController::class, 'destroy'])->name('staff.destroy');
            Route::post('staff/{staff}/roles', [StaffAssignmentController::class, 'store'])->name('staff.roles.store');
            Route::delete('staff/{staff}/roles/{role}', [StaffAssignmentController::class, 'destroy'])->name('staff.roles.destroy');

            Route::get('roles', [StaffRoleController::class, 'index'])->name('roles.index');
            Route::post('roles', [StaffRoleController::class, 'store'])->name('roles.store');
            Route::patch('roles/{role}', [StaffRoleController::class, 'update'])->name('roles.update');
            Route::delete('roles/{role}', [StaffRoleController::class, 'destroy'])->name('roles.destroy');

            Route::get('pricing', [PricingAdminController::class, 'index'])->name('pricing.index');
            Route::put('pricing', [PricingAdminController::class, 'update'])->name('pricing.update');

            Route::get('financials', [FinancialAdminController::class, 'index'])->name('financials.index');
            Route::get('financials/export', [FinancialAdminController::class, 'export'])->name('financials.export');

            Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
            Route::put('settings', [SettingsController::class, 'update'])->name('settings.update');
        });

        Route::middleware('ability:hr.view')
            ->prefix('hr')
            ->name('hr.')
            ->group(function () {
                Route::get('/', [HrController::class, 'index'])->name('index');
                Route::get('/leave', [HrLeaveController::class, 'index'])->name('leave.index');
                Route::get('/calendar', [HrLeaveController::class, 'calendar'])->name('calendar');
                Route::get('/discipline', [HrDisciplineController::class, 'index'])->name('discipline.index');
                Route::get('/reports', [HrReportController::class, 'index'])->name('reports.index');
                Route::get('/reports/{report}/export', [HrReportController::class, 'export'])->name('reports.export');
                Route::get('/settings', [HrSettingsController::class, 'index'])->name('settings');
                Route::get('/staff/{user}', [HrProfileController::class, 'show'])->name('staff.show');
                Route::get('/documents/{staffDocument}/download', [HrDocumentController::class, 'download'])->name('documents.download');
                Route::get('/payslips/{payslip}/pdf', [HrPayrollController::class, 'downloadPayslip'])->name('payslips.pdf');

                Route::middleware('ability:hr.manage')->group(function () {
                    Route::post('/staff/{user}/profile', [HrProfileController::class, 'store'])->name('staff.profile');
                    Route::post('/staff/{user}/exit', [HrProfileController::class, 'exit'])->name('staff.exit');
                    Route::post('/staff/{user}/reactivate', [HrProfileController::class, 'reactivate'])->name('staff.reactivate');
                    Route::post('/staff/{user}/performance', [HrPerformanceController::class, 'store'])->name('performance.store');
                    Route::delete('/performance/{performanceNote}', [HrPerformanceController::class, 'destroy'])->name('performance.destroy');
                    Route::post('/staff/{user}/discipline', [HrDisciplineController::class, 'store'])->name('discipline.store');
                    Route::patch('/discipline/{disciplinaryRecord}', [HrDisciplineController::class, 'update'])->name('discipline.update');
                    Route::post('/staff/{user}/documents', [HrDocumentController::class, 'store'])->name('documents.store');
                    Route::delete('/documents/{staffDocument}', [HrDocumentController::class, 'destroy'])->name('documents.destroy');
                    Route::post('/staff/{user}/checklists', [HrChecklistController::class, 'attach'])->name('checklists.attach');
                    Route::post('/checklist-items/{checklistInstanceItem}/toggle', [HrChecklistController::class, 'toggleItem'])->name('checklists.toggle');
                    Route::post('/checklist-templates/{checklistTemplate}/items', [HrSettingsController::class, 'storeTemplateItem'])->name('templates.items.store');
                    Route::delete('/checklist-template-items/{checklistTemplateItem}', [HrSettingsController::class, 'destroyTemplateItem'])->name('templates.items.destroy');
                });

                Route::post('/staff/{user}/leave', [HrLeaveController::class, 'store'])->name('leave.store');

                Route::middleware('ability:hr.leave.manage')->group(function () {
                    Route::post('/leave/{leaveRequest}/approve', [HrLeaveController::class, 'approve'])->name('leave.approve');
                    Route::post('/leave/{leaveRequest}/reject', [HrLeaveController::class, 'reject'])->name('leave.reject');
                    Route::post('/leave/{leaveRequest}/cancel', [HrLeaveController::class, 'cancel'])->name('leave.cancel');
                    Route::post('/staff/{user}/leave-allocations', [HrLeaveController::class, 'allocate'])->name('leave.allocate');
                    Route::post('/leave-types', [HrSettingsController::class, 'storeLeaveType'])->name('leave-types.store');
                    Route::patch('/leave-types/{leaveType}', [HrSettingsController::class, 'updateLeaveType'])->name('leave-types.update');
                    Route::delete('/leave-types/{leaveType}', [HrSettingsController::class, 'destroyLeaveType'])->name('leave-types.destroy');
                });

                Route::middleware('ability:hr.payroll.manage')->group(function () {
                    Route::post('/staff/{user}/compensation', [HrPayrollController::class, 'updateCompensation'])->name('compensation.update');
                    Route::post('/staff/{user}/payslips', [HrPayrollController::class, 'storePayslip'])->name('payslips.store');
                    Route::post('/payslips/{payslip}/status', [HrPayrollController::class, 'updatePayslipStatus'])->name('payslips.status');
                    Route::delete('/payslips/{payslip}', [HrPayrollController::class, 'destroyPayslip'])->name('payslips.destroy');
                });
            });
    });
});
