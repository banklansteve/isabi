<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DisciplinaryEvidence extends Model
{
    protected $table = 'disciplinary_evidence';

    public $timestamps = false;

    protected $fillable = [
        'disciplinary_case_id',
        'staff_document_id',
        'uploaded_by',
        'created_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function case(): BelongsTo
    {
        return $this->belongsTo(DisciplinaryCase::class, 'disciplinary_case_id');
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(StaffDocument::class, 'staff_document_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
