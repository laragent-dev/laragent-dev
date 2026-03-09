<?php

namespace LaraAgent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class AgentSession extends Model
{
    protected $table = 'agent_sessions';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'name',
        'agent_type',
        'provider',
        'model',
        'context',
        'messages',
        'status',
        'total_tokens',
        'total_iterations',
        'error_message',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'context'      => 'array',
        'messages'     => 'array',
        'started_at'   => 'datetime',
        'completed_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = Str::uuid()->toString();
            }
        });
    }

    public function logs(): HasMany
    {
        return $this->hasMany(AgentLog::class, 'agent_session_id');
    }

    public function markAsRunning(): void
    {
        $this->update([
            'status'     => 'running',
            'started_at' => now(),
        ]);
    }

    public function markAsCompleted(int $tokens, int $iterations): void
    {
        $this->update([
            'status'           => 'completed',
            'completed_at'     => now(),
            'total_tokens'     => $tokens,
            'total_iterations' => $iterations,
        ]);
    }

    public function markAsFailed(string $error): void
    {
        $this->update([
            'status'        => 'failed',
            'completed_at'  => now(),
            'error_message' => $error,
        ]);
    }
}
