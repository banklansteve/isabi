<?php

use App\Enums\UserRole;
use App\Http\Controllers\Admin\AccountController;
use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\AnalyticsAdminController;
use App\Http\Controllers\Admin\AnnouncementAdminController;
use App\Http\Controllers\Admin\ApprovalController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\BillingIssueController;
use App\Http\Controllers\Admin\Auth\AcceptInviteController;
use App\Http\Controllers\Admin\Auth\AuthenticatedSessionController as AdminAuthenticatedSessionController;
use App\Http\Controllers\Admin\Auth\NewPasswordController as AdminNewPasswordController;
use App\Http\Controllers\Admin\Auth\PasswordResetLinkController as AdminPasswordResetLinkController;
use App\Http\Controllers\Admin\Auth\SetPasswordController;
use App\Http\Controllers\Admin\Auth\VerifyInvitationController;
use App\Http\Controllers\Admin\CreditAdminController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\FinancialAdminController;
use App\Http\Controllers\Admin\Hr\DisciplinaryNoticeController;
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
use App\Http\Controllers\Admin\ModerationDeskController;
use App\Http\Controllers\Admin\OpsAttentionController;
use App\Http\Controllers\Admin\OpsInsightsController;
use App\Http\Controllers\Admin\OpsMessageController;
use App\Http\Controllers\Admin\OpsTaskController;
use App\Http\Controllers\Admin\PatrolController;
use App\Http\Controllers\Admin\PricingAdminController;
use App\Http\Controllers\Admin\ReferralAdminController;
use App\Http\Controllers\Admin\ReviewAdminController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\StaffAssignmentController;
use App\Http\Controllers\Admin\StaffCaseReferralController;
use App\Http\Controllers\Admin\StaffChatController;
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
        Route::get('tasks', OpsTaskController::class)->name('tasks');
        Route::get('assigned', [StaffCaseReferralController::class, 'index'])->name('assigned.index');
        Route::get('my-approvals', [ApprovalController::class, 'mine'])->name('my-approvals.index');
        Route::get('my-approvals/{approval}', [ApprovalController::class, 'mineShow'])->name('my-approvals.show');
        Route::get('moderation-desk', [ModerationDeskController::class, 'index'])->name('moderation-desk.index');
        Route::post('referrals', [StaffCaseReferralController::class, 'store'])->name('referrals.store');
        Route::post('escalations', [StaffCaseReferralController::class, 'escalate'])->name('escalations.store');
        Route::post('escalations/{referral}/acknowledge', [StaffCaseReferralController::class, 'acknowledge'])->name('escalations.acknowledge');
        Route::post('escalations/{referral}/complete', [StaffCaseReferralController::class, 'completeEscalation'])->name('escalations.complete');
        Route::post('referrals/{referral}/return', [StaffCaseReferralController::class, 'returnCase'])->name('referrals.return');
        Route::get('insights', [OpsInsightsController::class, 'index'])->name('insights.index');
        Route::get('insights/live', [OpsInsightsController::class, 'live'])->name('insights.live');
        Route::get('account', [AccountController::class, 'show'])->name('account');
        Route::post('account/avatar', [AccountController::class, 'updateAvatar'])->name('account.avatar');
        Route::patch('account/profile', [AccountController::class, 'updateProfile'])->name('account.profile');
        Route::post('attention/read', [OpsAttentionController::class, 'read'])->name('attention.read');
        Route::post('attention/read-all', [OpsAttentionController::class, 'readAll'])->name('attention.read-all');

        Route::prefix('notices')->name('notices.')->group(function () {
            Route::get('/', [DisciplinaryNoticeController::class, 'index'])->name('index');
            Route::get('{disciplinaryAction}', [DisciplinaryNoticeController::class, 'show'])->name('show');
            Route::get('{disciplinaryAction}/letter', [DisciplinaryNoticeController::class, 'letter'])->name('letter');
            Route::post('{disciplinaryAction}/acknowledge', [DisciplinaryNoticeController::class, 'acknowledge'])->name('acknowledge');
            Route::post('{disciplinaryAction}/respond', [DisciplinaryNoticeController::class, 'respond'])->name('respond');
            Route::post('{disciplinaryAction}/appeal', [DisciplinaryNoticeController::class, 'appeal'])->name('appeal');
        });

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

        Route::middleware('ability:admin.content.manage')->prefix('jobs')->name('jobs.')->group(function () {
            Route::get('/', [JobAdminController::class, 'index'])->name('index');
            Route::get('{workLog}', [JobAdminController::class, 'show'])->name('show');
            Route::post('{workLog}/flag', [JobAdminController::class, 'flag'])->name('flag');
            Route::post('{workLog}/unflag', [JobAdminController::class, 'unflag'])->name('unflag');
            Route::patch('{workLog}', [JobAdminController::class, 'update'])->name('update');
            Route::post('{workLog}/hide', [JobAdminController::class, 'hide'])->name('hide');
            Route::post('{workLog}/unhide', [JobAdminController::class, 'unhide'])->name('unhide');
            Route::post('{workLog}/remove', [JobAdminController::class, 'remove'])->name('remove');
            Route::post('{workLog}/refer', [JobAdminController::class, 'refer'])->name('refer');
            Route::post('{workLog}/message', [JobAdminController::class, 'message'])->name('message');
        });

        Route::middleware('ability:admin.approvals.manage')->prefix('approvals')->name('approvals.')->group(function () {
            Route::get('/', [ApprovalController::class, 'index'])->name('index');
            Route::get('{approval}', [ApprovalController::class, 'show'])->name('show');
            Route::post('{approval}/approve', [ApprovalController::class, 'approve'])->name('approve');
            Route::post('{approval}/reject', [ApprovalController::class, 'reject'])->name('reject');
        });

        Route::prefix('ops-messages')->name('ops-messages.')->group(function () {
            Route::get('/', [OpsMessageController::class, 'index'])->name('index');
            Route::get('options', [OpsMessageController::class, 'options'])->name('options');
            Route::post('/', [OpsMessageController::class, 'send'])->name('send');
            Route::post('templates', [OpsMessageController::class, 'storeTemplate'])->name('templates.store');
            Route::patch('templates/{template}', [OpsMessageController::class, 'updateTemplate'])->name('templates.update');
            Route::delete('templates/{template}', [OpsMessageController::class, 'destroyTemplate'])->name('templates.destroy');
        });

        Route::middleware('ability:admin.billing_issues.manage')->prefix('billing-issues')->name('billing-issues.')->group(function () {
            Route::get('/', [BillingIssueController::class, 'index'])->name('index');
            Route::post('{billingIssue}/assign', [BillingIssueController::class, 'claim'])->name('assign');
            Route::post('{billingIssue}/resolve', [BillingIssueController::class, 'resolve'])->name('resolve');
        });

        Route::middleware('ability:patrol.view')->prefix('patrol')->name('patrol.')->group(function () {
            Route::get('/', fn () => redirect()->route('admin.patrol.jobs'))->name('index');
            Route::get('jobs', [PatrolController::class, 'jobs'])->name('jobs');
            Route::get('reviews', [PatrolController::class, 'reviews'])->name('reviews');
            Route::get('{patrolCase}', [PatrolController::class, 'show'])->name('show');
            Route::post('{patrolCase}/notes', [PatrolController::class, 'storeNote'])->middleware('ability:patrol.investigate')->name('notes.store');
            Route::post('{patrolCase}/review', [PatrolController::class, 'startReview'])->middleware('ability:patrol.investigate')->name('review');
            Route::post('{patrolCase}/recommend', [PatrolController::class, 'recommend'])->middleware('ability:patrol.investigate')->name('recommend');
            Route::post('{patrolCase}/dismiss', [PatrolController::class, 'dismiss'])->name('dismiss');
            Route::post('{patrolCase}/remove', [PatrolController::class, 'remove'])->middleware('ability:patrol.resolve')->name('remove');
            Route::post('{patrolCase}/hide', [PatrolController::class, 'hide'])->middleware('ability:patrol.resolve')->name('hide');
            Route::post('{patrolCase}/approve', [PatrolController::class, 'approve'])->middleware('ability:patrol.resolve')->name('approve');
            Route::post('{patrolCase}/reject', [PatrolController::class, 'reject'])->middleware('ability:patrol.resolve')->name('reject');
            Route::post('{patrolCase}/handoff', [PatrolController::class, 'handoff'])->middleware('ability:patrol.resolve')->name('handoff');
        });

        Route::get('reviews', [ReviewAdminController::class, 'index'])->middleware('ability:admin.content.manage')->name('reviews.index');
        Route::get('reviews/{review}', [ReviewAdminController::class, 'show'])->middleware('ability:admin.content.manage')->name('reviews.show');
        Route::post('reviews/{review}/flag', [ReviewAdminController::class, 'flag'])->name('reviews.flag');
        Route::post('reviews/{review}/unflag', [ReviewAdminController::class, 'unflag'])->name('reviews.unflag');
        Route::post('reviews/{review}/hide', [ReviewAdminController::class, 'hide'])->name('reviews.hide');
        Route::post('reviews/{review}/remove', [ReviewAdminController::class, 'remove'])->name('reviews.remove');

        Route::get('credits', [CreditAdminController::class, 'index'])->middleware('ability:admin.credits.view')->name('credits.index');
        Route::post('credits/adjust', [CreditAdminController::class, 'adjust'])->name('credits.adjust');

        Route::get('referrals', [ReferralAdminController::class, 'index'])->middleware('ability:admin.referrals.view')->name('referrals.index');

        Route::get('analytics', [AnalyticsAdminController::class, 'index'])->middleware('ability:admin.analytics.view')->name('analytics.index');

        Route::get('messaging', [AnnouncementAdminController::class, 'index'])->middleware('ability:admin.messaging.manage')->name('messaging.index');
        Route::middleware('ability:admin.messaging.manage')->group(function () {
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
        });

        Route::middleware('ability:admin.support.manage')->prefix('support')->name('support.')->group(function () {
            Route::get('/', [SupportController::class, 'index'])->name('index');
            Route::get('reports', [SupportController::class, 'reports'])->name('reports');
            Route::get('templates', [SupportController::class, 'templates'])->name('templates');
            Route::get('sync', [SupportController::class, 'sync'])->name('sync');
            Route::post('canned', [SupportController::class, 'storeCanned'])->name('canned.store');
            Route::patch('canned/{reply}', [SupportController::class, 'updateCanned'])->name('canned.update');
            Route::delete('canned/{reply}', [SupportController::class, 'destroyCanned'])->name('canned.destroy');
            Route::get('{ticket}', [SupportController::class, 'show'])->name('show');
            Route::post('{ticket}/claim', [SupportController::class, 'claim'])->name('claim');
            Route::post('{ticket}/reply', [SupportController::class, 'reply'])->name('reply');
            Route::post('{ticket}/notes', [SupportController::class, 'note'])->name('notes.store');
            Route::post('{ticket}/assign', [SupportController::class, 'assign'])->name('assign');
            Route::post('{ticket}/tags', [SupportController::class, 'tag'])->name('tags');
            Route::post('{ticket}/resolve', [SupportController::class, 'resolve'])->name('resolve');
            Route::post('{ticket}/reopen', [SupportController::class, 'reopen'])->name('reopen');
            Route::post('{ticket}/typing', [SupportController::class, 'typing'])->name('typing');
            Route::post('{ticket}/messages/{message}/react', [SupportController::class, 'react'])->name('react');
        });

        Route::prefix('asap')->name('asap.')->group(function () {
            Route::get('/', [StaffChatController::class, 'index'])->name('index');
            Route::get('sync', [StaffChatController::class, 'sync'])->name('sync');
            Route::post('direct', [StaffChatController::class, 'startDirect'])->name('direct');
            Route::get('{conversation}', [StaffChatController::class, 'show'])->name('show');
            Route::post('{conversation}', [StaffChatController::class, 'store'])->name('store');
            Route::post('{conversation}/typing', [StaffChatController::class, 'typing'])->name('typing');
            Route::post('{conversation}/read', [StaffChatController::class, 'markRead'])->name('read');
            Route::post('{conversation}/messages/{message}/react', [StaffChatController::class, 'react'])->name('react');
        });

        Route::middleware('role:'.UserRole::SuperAdmin->value)->group(function () {
            Route::get('activity', ActivityLogController::class)->name('activity');
            Route::get('audit', [AuditLogController::class, 'index'])->name('audit.index');

            Route::get('staff', [StaffController::class, 'index'])->name('staff.index');
            Route::post('staff', [StaffController::class, 'store'])->name('staff.store');
            Route::post('staff/bulk-message', [StaffController::class, 'bulkMessage'])->name('staff.bulk-message');
            Route::get('staff/{staff}', [StaffController::class, 'show'])->name('staff.show');
            Route::post('staff/{staff}/resend', [StaffController::class, 'resend'])->name('staff.resend');
            Route::post('staff/{staff}/revoke', [StaffController::class, 'revoke'])->name('staff.revoke');
            Route::post('staff/{staff}/disable', [StaffController::class, 'disable'])->name('staff.disable');
            Route::post('staff/{staff}/reinstate', [StaffController::class, 'reinstate'])->name('staff.reinstate');
            Route::post('staff/{staff}/logout', [StaffController::class, 'forceLogout'])->name('staff.logout');
            Route::post('staff/{staff}/password-reset', [StaffController::class, 'sendPasswordReset'])->name('staff.password-reset');
            Route::post('staff/{staff}/message', [StaffController::class, 'message'])->name('staff.message');
            Route::post('staff/{staff}/announce', [StaffController::class, 'sendAnnouncement'])->name('staff.announce');
            Route::post('staff/{staff}/shift', [StaffController::class, 'updateShift'])->name('staff.shift');
            Route::post('staff/{staff}/delete', [StaffController::class, 'destroy'])->name('staff.destroy');
            Route::post('staff/{staff}/roles', [StaffAssignmentController::class, 'store'])->name('staff.roles.store');
            Route::put('staff/{staff}/roles', [StaffAssignmentController::class, 'sync'])->name('staff.roles.sync');
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

        Route::prefix('hr')
            ->name('hr.')
            ->group(function () {
                Route::middleware('ability:hr.view')->group(function () {
                    Route::get('/', [HrController::class, 'index'])->name('index');
                    Route::get('/leave', [HrLeaveController::class, 'index'])->name('leave.index');
                    Route::get('/calendar', [HrLeaveController::class, 'calendar'])->name('calendar');
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
                        Route::post('/staff/{user}/documents', [HrDocumentController::class, 'store'])->name('documents.store');
                        Route::patch('/documents/{staffDocument}', [HrDocumentController::class, 'update'])->name('documents.update');
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
                        Route::post('/payslips/{payslip}/reissue', [HrPayrollController::class, 'reissue'])->name('payslips.reissue');
                        Route::delete('/payslips/{payslip}', [HrPayrollController::class, 'destroyPayslip'])->name('payslips.destroy');
                    });
                });

                Route::middleware('ability:hr.discipline.view')->group(function () {
                    Route::get('/discipline', [HrDisciplineController::class, 'index'])->name('discipline.index');
                    Route::get('/discipline/templates', [HrDisciplineController::class, 'templates'])->name('discipline.templates');
                    Route::get('/discipline/reports', [HrDisciplineController::class, 'reports'])->name('discipline.reports');
                    Route::get('/discipline/reports/export', [HrDisciplineController::class, 'exportReports'])->name('discipline.reports.export');
                    Route::get('/discipline/{disciplinaryCase}', [HrDisciplineController::class, 'show'])->name('discipline.show');
                    Route::get('/discipline/actions/{disciplinaryAction}/letter', [HrDisciplineController::class, 'letter'])->name('discipline.letter');
                    Route::get('/discipline/evidence/{disciplinaryEvidence}/download', [HrDisciplineController::class, 'downloadEvidence'])->name('discipline.evidence.download');
                });

                Route::middleware('ability:hr.discipline.manage')->group(function () {
                    Route::post('/discipline', [HrDisciplineController::class, 'store'])->name('discipline.store');
                    Route::post('/discipline/{disciplinaryCase}/transition', [HrDisciplineController::class, 'transition'])->name('discipline.transition');
                    Route::post('/discipline/{disciplinaryCase}/archive', [HrDisciplineController::class, 'archive'])->name('discipline.archive');
                    Route::post('/discipline/{disciplinaryCase}/notes', [HrDisciplineController::class, 'storeNote'])->name('discipline.notes.store');
                    Route::post('/discipline/{disciplinaryCase}/evidence', [HrDisciplineController::class, 'storeEvidence'])->name('discipline.evidence.store');
                    Route::post('/discipline/{disciplinaryCase}/actions', [HrDisciplineController::class, 'storeAction'])->name('discipline.actions.store');
                    Route::post('/discipline/{disciplinaryCase}/appeals', [HrDisciplineController::class, 'storeAppeal'])->name('discipline.appeals.store');
                    Route::post('/discipline/appeals/{disciplinaryAppeal}/review', [HrDisciplineController::class, 'reviewAppeal'])->name('discipline.appeals.review');
                    Route::patch('/discipline/templates/{disciplinaryLetterTemplate}', [HrDisciplineController::class, 'updateTemplate'])->name('discipline.templates.update');
                });
            });
    });
});
