<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowTransition extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'content_version_id',
        'from_status',
        'to_status',
        'performed_by',
        'comment',
    ];

    // Relationships

    public function version(): BelongsTo
    {
        return $this->belongsTo(ContentVersion::class, 'content_version_id');
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    // Helpers

    public function isInitialCreation(): bool
    {
        return $this->from_status === null;
    }

    public function getTransitionLabel(): string
    {
        if ($this->isInitialCreation()) {
            return "Created as {$this->to_status}";
        }

        return "{$this->from_status} → {$this->to_status}";
    }
}
