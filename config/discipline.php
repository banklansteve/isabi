<?php

return [

    'statuses' => [
        'reported' => 'Reported',
        'investigating' => 'Under investigation',
        'decision_pending' => 'Decision pending',
        'action_issued' => 'Action issued',
        'appealed' => 'Appealed',
        'resolved' => 'Resolved',
        'closed' => 'Closed',
    ],

    'transitions' => [
        'reported' => ['investigating'],
        'investigating' => ['decision_pending'],
        'decision_pending' => [],
        'action_issued' => ['resolved', 'closed'],
        'appealed' => [],
        'resolved' => ['closed'],
        'closed' => [],
    ],

    'severities' => [
        'minor' => 'Minor',
        'moderate' => 'Moderate',
        'serious' => 'Serious',
    ],

    'categories' => [
        'conduct' => 'Conduct',
        'attendance' => 'Attendance',
        'performance' => 'Performance-related',
        'policy' => 'Policy violation',
        'other' => 'Other',
    ],

    'outcomes' => [
        'verbal_warning' => 'Verbal warning',
        'written_warning' => 'Written warning',
        'final_written_warning' => 'Final written warning',
        'suspension' => 'Suspension',
        'termination' => 'Termination of employment',
        'no_action' => 'No action taken',
    ],

    'serious_outcomes' => [
        'suspension',
        'termination',
    ],

    'appeal_outcomes' => [
        'upheld' => 'Upheld',
        'overturned' => 'Overturned',
        'modified' => 'Modified',
    ],

    'event_types' => [
        'created' => 'Case opened',
        'status_changed' => 'Status changed',
        'owner_changed' => 'Case owner changed',
        'note_added' => 'Note added',
        'evidence_attached' => 'Evidence attached',
        'action_issued' => 'Formal action recorded',
        'letter_finalized' => 'Notice finalized',
        'acknowledged' => 'Notice acknowledged',
        'response_submitted' => 'Written response received',
        'appeal_raised' => 'Appeal raised',
        'appeal_reviewed' => 'Appeal reviewed',
        'archived' => 'Case archived',
    ],

];
