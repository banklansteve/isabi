<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DisciplinaryRecord extends Model
{
    public const TYPE_VERBAL = 'verbal_warning';

    public const TYPE_WRITTEN = 'written_warning';

    public const TYPE_FINAL = 'final_warning';

    public const TYPE_SUSPENSION = 'suspension';

    public const TYPE_PIP = 'improvement_plan';

    public const TYPE_OTHER = 'other';

    public const STATUS_OPEN = 'open';

    public const STATUS_MONITORING = 'monitoring';

    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'user_id',
        'type',
        'status',
        'occurred_on',
        'summary',
        'details',
        'issued_by',
        'follow_up_on',
        'outcome',
        'staff_document_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'occurred_on' => 'date',
            'follow_up_on' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(StaffDocument::class, 'staff_document_id');
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function typeOptions(): array
    {
        return [
            ['value' => self::TYPE_VERBAL, 'label' => 'Verbal warning'],
            ['value' => self::TYPE_WRITTEN, 'label' => 'Written warning'],
            ['value' => self::TYPE_FINAL, 'label' => 'Final warning'],
            ['value' => self::TYPE_SUSPENSION, 'label' => 'Suspension'],
            ['value' => self::TYPE_PIP, 'label' => 'Improvement plan'],
            ['value' => self::TYPE_OTHER, 'label' => 'Other'],
        ];
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function statusOptions(): array
    {
        return [
            ['value' => self::STATUS_OPEN, 'label' => 'Open'],
            ['value' => self::STATUS_MONITORING, 'label' => 'Monitoring'],
            ['value' => self::STATUS_CLOSED, 'label' => 'Closed'],
        ];
    }

    /**
     * @return list<string>
     */
    public static function types(): array
    {
        return array_column(self::typeOptions(), 'value');
    }

    /**
     * @return list<string>
     */
    public static function statuses(): array
    {
        return array_column(self::statusOptions(), 'value');
    }

    public function typeLabel(): string
    {
        return collect(self::typeOptions())->firstWhere('value', $this->type)['label'] ?? $this->type;
    }

    public function statusLabel(): string
    {
        return collect(self::statusOptions())->firstWhere('value', $this->status)['label'] ?? $this->status;
    }
}
