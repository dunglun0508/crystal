<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FailedCrawl extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'identifier', 
        'error',
        'attempts',
        'last_attempt_at',
        'resolved_at'
    ];

    protected $casts = [
        'last_attempt_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    // Scope để lấy các bản ghi chưa được resolve
    public function scopeUnresolved($query)
    {
        return $query->whereNull('resolved_at');
    }

    // Scope để lấy theo type
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    // Scope để lấy các bản ghi có thể retry (dưới 5 lần thử)
    public function scopeRetryable($query)
    {
        return $query->where('attempts', '<', 5);
    }

    // Method để mark as resolved
    public function markAsResolved($error = null)
    {
        $this->update([
            'resolved_at' => now(),
            'last_attempt_at' => now(),
            'error' => $error
        ]);
    }

    // Method để increment attempts
    public function incrementAttempts()
    {
        $this->increment('attempts');
        $this->update(['last_attempt_at' => now()]);
    }
} 