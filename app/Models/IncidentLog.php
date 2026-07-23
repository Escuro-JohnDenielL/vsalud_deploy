<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IncidentLog extends Model
{
    use HasFactory;

    protected $table = 'incident_logs';

    protected $fillable = [
        'title',
        'description',
        'severity',
        'status',
        'detected_at',
        'resolved_at',
        'resolution',
        'reported_by',
    ];

    protected $casts = [
        'detected_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    /**
     * Severity labels for display.
     */
    public static function severityLabels(): array
    {
        return [
            'low'      => 'Low',
            'medium'   => 'Medium',
            'high'     => 'High',
            'critical' => 'Critical',
        ];
    }

    /**
     * Status labels for display.
     */
    public static function statusLabels(): array
    {
        return [
            'open'           => 'Open',
            'investigating'  => 'Investigating',
            'resolved'       => 'Resolved',
            'closed'         => 'Closed',
        ];
    }
}
