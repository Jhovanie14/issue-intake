<?php

namespace App\Models;

use App\Enums\Priority;
use App\Enums\Status;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Issue extends Model
{
    /** @use HasFactory<\Database\Factories\IssueFactory> */
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'priority',
        'category',
        'status',
        'summary',
        'suggested_action',
        'summary_source',
        'due_at',
        'escalated',
        'escalated_at',
    ];

    protected $casts = [
        'priority' => Priority::class,
        'status' => Status::class,
        'due_at' => 'datetime',
        'escalated' => 'boolean',
        'escalated_at' => 'datetime'
    ];
}
