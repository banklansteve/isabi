<?php

namespace App\Support\Hr;

use App\Models\DisciplinaryAction;
use App\Models\DisciplinaryCase;
use App\Models\DisciplinaryLetterTemplate;

class DisciplinaryLetter
{
    /**
     * @return array<string, string>
     */
    public static function placeholders(DisciplinaryCase $case, ?DisciplinaryAction $action = null): array
    {
        $staff = $case->staff;
        $suspension = '';

        if ($action?->suspension_starts_on && $action?->suspension_ends_on) {
            $suspension = $action->suspension_starts_on->format('j F Y').' to '.$action->suspension_ends_on->format('j F Y');
        }

        return [
            '{{staff_name}}' => $staff?->name ?: $staff?->email ?: 'Staff member',
            '{{case_reference}}' => $case->reference,
            '{{incident_date}}' => $case->incident_on->format('j F Y'),
            '{{incident_summary}}' => $case->description,
            '{{outcome}}' => $action?->typeLabel() ?: '[outcome]',
            '{{justification}}' => $action?->justification ?: '[justification]',
            '{{issued_date}}' => ($action?->issued_at ?? now())->format('j F Y'),
            '{{owner_name}}' => $case->owner?->name ?: 'Case owner',
            '{{suspension_period}}' => $suspension ?: '[suspension period]',
        ];
    }

    public static function render(string $body, DisciplinaryCase $case, ?DisciplinaryAction $action = null): string
    {
        return strtr($body, self::placeholders($case, $action));
    }

    public static function defaultSubject(string $outcomeType): string
    {
        $label = config('discipline.outcomes')[$outcomeType] ?? 'Employment notice';

        return $label;
    }

    /**
     * @return list<array{slug: string, name: string, outcome_type: string, body: string}>
     */
    public static function defaultTemplates(): array
    {
        return [
            [
                'slug' => 'written_warning',
                'name' => 'Written warning letter',
                'outcome_type' => 'written_warning',
                'body' => self::skeleton(
                    'This letter confirms that a written warning has been recorded following a review of the matter referenced above.',
                    'This warning will remain on your employment file. A further related matter may lead to a final written warning or other formal action.',
                ),
            ],
            [
                'slug' => 'final_written_warning',
                'name' => 'Final written warning letter',
                'outcome_type' => 'final_written_warning',
                'body' => self::skeleton(
                    'This letter confirms that a final written warning has been recorded following a review of the matter referenced above.',
                    'This is a final written warning. A further related matter may lead to suspension or termination of employment.',
                ),
            ],
            [
                'slug' => 'suspension',
                'name' => 'Suspension notice',
                'outcome_type' => 'suspension',
                'body' => self::skeleton(
                    'This letter confirms that a period of suspension has been recorded following a review of the matter referenced above. The suspension period is {{suspension_period}}.',
                    'During this period you should not attend work or use platform access unless you are asked to do so. Platform access, if it is to be paused, will be confirmed separately in Access & Roles.',
                ),
            ],
            [
                'slug' => 'termination',
                'name' => 'Termination notice',
                'outcome_type' => 'termination',
                'body' => self::skeleton(
                    'This letter confirms that a decision has been recorded to end employment following a review of the matter referenced above.',
                    'Employment status and platform access will be confirmed separately in the HR profile and Access & Roles. Those steps are not automatic and will be recorded when they are completed.',
                ),
            ],
            [
                'slug' => 'no_action',
                'name' => 'No further action letter',
                'outcome_type' => 'no_action',
                'body' => self::skeleton(
                    'This letter confirms that the matter referenced above has been reviewed and that no formal disciplinary action will be taken.',
                    'The case record will be retained as part of the employment file.',
                ),
            ],
            [
                'slug' => 'verbal_warning',
                'name' => 'Verbal warning confirmation',
                'outcome_type' => 'verbal_warning',
                'body' => self::skeleton(
                    'This letter confirms that a verbal warning has been recorded following a review of the matter referenced above.',
                    'This confirmation is for the employment file. A further related matter may lead to a written warning or other formal action.',
                ),
            ],
        ];
    }

    public static function ensureTemplates(): void
    {
        foreach (self::defaultTemplates() as $definition) {
            DisciplinaryLetterTemplate::query()->firstOrCreate(
                ['slug' => $definition['slug']],
                [
                    'name' => $definition['name'],
                    'outcome_type' => $definition['outcome_type'],
                    'body' => $definition['body'],
                    'is_active' => true,
                ],
            );
        }
    }

    private static function skeleton(string $decision, string $next): string
    {
        return <<<TXT
Private and confidential

{{staff_name}}

Case reference: {{case_reference}}
Date of notice: {{issued_date}}
Date of incident: {{incident_date}}

This notice concerns a matter that has been reviewed under the organisation's employment process.

The incident
{{incident_summary}}

The decision
{$decision}

Reason for this decision
{{justification}}

What this means
{$next}

You may acknowledge receipt of this notice in the staff console. If you wish to respond in writing, you may submit one formal response against this notice. If you wish to appeal, you may do so from the same notice.

Yours sincerely
{{owner_name}}
TXT;
    }
}
