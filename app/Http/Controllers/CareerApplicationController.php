<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCareerApplicationRequest;
use App\Models\CareerApplication;
use App\Models\CareerVacancy;
use App\Support\Careers\CareerCvUploadService;
use App\Support\Seo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class CareerApplicationController extends Controller
{
    public function show(CareerVacancy $vacancy): Response
    {
        abort_unless($vacancy->isAcceptingApplications(), 404);

        app(Seo::class)
            ->title($vacancy->title.' · Careers at Kraftrack')
            ->description($vacancy->summary)
            ->canonical(url('/careers/'.$vacancy->public_uid));

        return Inertia::render('Careers/Show', [
            'canLogin' => \Illuminate\Support\Facades\Route::has('login'),
            'canRegister' => \Illuminate\Support\Facades\Route::has('register'),
            'vacancy' => $vacancy->toPublicArray(),
        ]);
    }

    public function apply(CareerVacancy $vacancy): Response
    {
        abort_unless($vacancy->isAcceptingApplications(), 404);

        app(Seo::class)
            ->title('Apply · '.$vacancy->title)
            ->description('Apply for '.$vacancy->title.' at Kraftrack.')
            ->canonical(url('/careers/'.$vacancy->public_uid.'/apply'));

        return Inertia::render('Careers/Apply', [
            'canLogin' => \Illuminate\Support\Facades\Route::has('login'),
            'canRegister' => \Illuminate\Support\Facades\Route::has('register'),
            'vacancy' => $vacancy->toPublicArray(),
            'skillOptions' => [
                'Customer support',
                'Conflict resolution',
                'Excel',
                'Google Sheets',
                'Written communication',
                'WhatsApp / chat support',
                'Trust & safety',
                'Content moderation',
                'Product operations',
                'Data entry',
                'Research',
                'Project coordination',
                'HR / recruiting',
                'Accounting basics',
                'Graphic design',
                'Frontend development',
                'Backend development',
                'QA / testing',
            ],
            'classOfDegreeOptions' => [
                'First Class',
                'Second Class Upper',
                'Second Class Lower',
                'Third Class',
                'Pass',
                'Distinction',
                'Merit',
                'Credit',
                'Not applicable',
            ],
            'nyscOptions' => [
                ['value' => 'completed', 'label' => 'Completed'],
                ['value' => 'exempted', 'label' => 'Exempted'],
                ['value' => 'currently_serving', 'label' => 'Currently serving'],
                ['value' => 'not_yet_due', 'label' => 'Not yet due'],
            ],
            'workModeOptions' => [
                ['value' => 'remote', 'label' => 'Remote'],
                ['value' => 'hybrid', 'label' => 'Hybrid'],
                ['value' => 'onsite', 'label' => 'Onsite'],
            ],
        ]);
    }

    public function store(
        StoreCareerApplicationRequest $request,
        CareerVacancy $vacancy,
        CareerCvUploadService $cvUpload,
    ): RedirectResponse {
        abort_unless($vacancy->isAcceptingApplications(), 404);

        $data = $request->validated();
        $cv = $cvUpload->store($request->file('cv'), $vacancy->id);

        $noExperience = (bool) ($data['no_work_experience'] ?? false);

        CareerApplication::query()->create([
            'career_vacancy_id' => $vacancy->id,
            'status' => 'received',
            'full_name' => $data['full_name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'city' => $data['city'] ?? null,
            'linkedin_url' => $data['linkedin_url'] ?? null,
            'portfolio_url' => $data['portfolio_url'] ?? null,
            'education' => $data['education'] ?? [],
            'certifications' => $data['certifications'] ?? null,
            'secondary_education' => $data['secondary_education'] ?? null,
            'primary_education' => $data['primary_education'] ?? null,
            'no_work_experience' => $noExperience,
            'work_experience' => $noExperience ? [] : ($data['work_experience'] ?? []),
            'skills' => $data['skills'] ?? [],
            'skills_other' => $data['skills_other'] ?? null,
            'achievements' => $data['achievements'] ?? null,
            'nysc_status' => $data['nysc_status'] ?? null,
            'willing_to_relocate' => array_key_exists('willing_to_relocate', $data)
                ? (bool) $data['willing_to_relocate']
                : null,
            'preferred_work_mode' => $data['preferred_work_mode'] ?? null,
            'earliest_availability' => $data['earliest_availability'] ?? null,
            'expected_salary' => $data['expected_salary'] ?? null,
            'notice_period' => $data['notice_period'] ?? null,
            'why_this_role' => $data['why_this_role'] ?? null,
            'cv_disk' => $cv['disk'],
            'cv_path' => $cv['path'],
            'cv_url' => $cv['url'],
            'cv_name' => $cv['name'],
            'work_sample_url' => $data['work_sample_url'] ?? null,
            'references' => $this->cleanReferences($data['references'] ?? []),
            'ndpr_consent' => true,
            'ndpr_consented_at' => Carbon::now(),
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 1000),
        ]);

        return redirect()
            ->route('careers.apply.thanks', $vacancy->public_uid)
            ->with('success', 'Application received.');
    }

    public function thanks(CareerVacancy $vacancy): Response
    {
        return Inertia::render('Careers/ApplyThanks', [
            'canLogin' => \Illuminate\Support\Facades\Route::has('login'),
            'canRegister' => \Illuminate\Support\Facades\Route::has('register'),
            'vacancy' => [
                'title' => $vacancy->title,
                'public_uid' => $vacancy->public_uid,
            ],
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $refs
     * @return array<int, array{name: string, relationship: string, contact: string}>
     */
    private function cleanReferences(array $refs): array
    {
        return collect($refs)
            ->map(fn ($r) => [
                'name' => trim((string) ($r['name'] ?? '')),
                'relationship' => trim((string) ($r['relationship'] ?? '')),
                'contact' => trim((string) ($r['contact'] ?? '')),
            ])
            ->filter(fn ($r) => $r['name'] !== '' || $r['contact'] !== '')
            ->values()
            ->take(2)
            ->all();
    }
}
