<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Admin audit trail (table: activity_log).
 *
 * Entries reach this table from two places:
 *
 *  1. Explicitly — controllers (and the profile page's JS) that know the exact
 *     business context write a precise entry themselves.
 *  2. Automatically — App\Http\Middleware\LogAdminActivity writes an entry for
 *     every state-changing admin request that did not already log itself, so the
 *     trail also covers pages nobody remembered to instrument.
 *
 * `category` groups entries into the audit types shown on the Audit Logs page.
 * Rows written by legacy code that never set a category are derived on the fly by
 * categoryFor(), so nothing ever shows up as "uncategorised".
 */
class ActivityLog extends Model
{
    use HasFactory;

    /**
     * Request attribute set the moment an entry is written for the current
     * request. LogAdminActivity reads it so an action is never recorded twice.
     */
    public const FLAG_WRITTEN = 'activity_log.written';

    /**
     * Audit types: slug => display metadata, in the order the filter lists them.
     */
    public const CATEGORIES = [
        'auth' => [
            'label' => 'Authentication',
            'hint' => 'Sign-ins, sign-outs, MFA and password events',
        ],
        'security' => [
            'label' => 'Security',
            'hint' => 'Incidents, access denials, permissions and admin accounts',
        ],
        'data' => [
            'label' => 'Data Changes',
            'hint' => 'Records created, updated, approved or deleted',
        ],
        'config' => [
            'label' => 'Configuration',
            'hint' => 'Settings, form builder and availability changes',
        ],
        'system' => [
            'label' => 'System / Other',
            'hint' => 'Anything that does not fit the types above',
        ],
    ];

    /**
     * Keyword rules used to derive a category from an activity type (and the
     * route that produced it). Checked in order — first match wins, which is why
     * "auth" sits before "data" (a password change also contains "change").
     */
    private const CATEGORY_PATTERNS = [
        'auth' => '/(^|[^a-z])(log ?in|log ?out|sign ?in|sign ?out|password|otp|mfa|session|profile|credential|verification)/i',
        'security' => '/(incident|permission|privilege|role|trusted device|access denied|unauthor|breach|security|suspend|deactivat|admin account|super ?admin)/i',
        'config' => '/(settings|configuration|config|form ?builder|form ?field|availability|theme|maintenance|backup|publish)/i',
        'data' => '/(creat|updat|delet|approv|deni|resolv|clos|toggle|cancel|upload|restor|archiv|assign|status|import|export|added|removed)/i',
    ];
    
    protected $primaryKey = 'log_id';
    public $incrementing = false; 
    protected $keyType = 'string'; 
    
    protected $table = 'activity_log';

    protected $fillable = [
        'log_id', 
        'admin_id',
        'activity_type',
        'category',
        'route_name',
        'http_method',
        'subject',
        'description',
        'inquiry_id',
        'reserve_id',
        'ip_address',
        'user_agent',
    ];

    /**
     * Boot method to auto-generate log_id and fill in the audit category.
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (empty($model->log_id)) {
                $model->log_id = 'LOG_' . uniqid('', true);
            }

            // Legacy call sites only pass activity_type; derive the category here so
            // every row stays filterable without touching every controller.
            if (empty($model->category)) {
                $model->category = static::categoryFor($model->activity_type, $model->route_name);
            }
        });

        static::created(function () {
            if (app()->bound('request')) {
                static::markWritten(app('request'));
            }
        });
    }

    /**
     * Relationship to the Admin who owns this activity.
     */
    public function admin()
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }

    // ------------------------------------------------------------------
    // Audit categories
    // ------------------------------------------------------------------

    /**
     * Derive the audit category for an activity type / route name pair.
     */
    public static function categoryFor(?string $activityType, ?string $routeName = null): string
    {
        $haystack = trim((string) $activityType . ' ' . (string) $routeName);

        if ($haystack === '') {
            return 'system';
        }

        foreach (self::CATEGORY_PATTERNS as $slug => $pattern) {
            if (preg_match($pattern, $haystack) === 1) {
                return $slug;
            }
        }

        return 'system';
    }

    /**
     * Category slug — falls back to the derived value for legacy rows.
     */
    public function getCategoryKeyAttribute(): string
    {
        $category = $this->category ?: static::categoryFor($this->activity_type, $this->route_name);

        return isset(self::CATEGORIES[$category]) ? $category : 'system';
    }

    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORIES[$this->category_key]['label'];
    }

    public function getCategoryHintAttribute(): string
    {
        return self::CATEGORIES[$this->category_key]['hint'];
    }

    /**
     * Readable label for the raw activity type ("failed_login" → "Failed Login").
     */
    public function getEventLabelAttribute(): string
    {
        return Str::headline((string) $this->activity_type);
    }

    /**
     * Display name of the acting admin. Failed sign-ins are logged with admin_id 0
     * because no account was matched, so they read as "Not signed in".
     */
    public function getAdminNameAttribute(): string
    {
        $name = trim((string) optional($this->admin)->f_name . ' ' . (string) optional($this->admin)->l_name);

        if ($name !== '') {
            return $name;
        }

        return $this->admin_id ? 'Admin #' . $this->admin_id : 'Not signed in';
    }

    public function getAdminInitialsAttribute(): string
    {
        $initials = Str::of($this->admin_name)
            ->squish()
            ->explode(' ')
            ->reject(fn ($part) => $part === '' || ! ctype_alpha($part[0]))
            ->take(2)
            ->map(fn ($part) => Str::upper(Str::substr($part, 0, 1)))
            ->implode('');

        return $initials !== '' ? $initials : '—';
    }

    // ------------------------------------------------------------------
    // Request-scoped "already logged" flag
    // ------------------------------------------------------------------

    public static function markWritten(Request $request): void
    {
        $request->attributes->set(self::FLAG_WRITTEN, true);
    }

    public static function wasWritten(Request $request): bool
    {
        return (bool) $request->attributes->get(self::FLAG_WRITTEN, false);
    }

    // ------------------------------------------------------------------
    // Query scopes (used by Admin\AuditLogController)
    // ------------------------------------------------------------------

    public function scopeCategory(Builder $query, ?string $slug): Builder
    {
        if (! $slug || $slug === 'all' || ! isset(self::CATEGORIES[$slug])) {
            return $query;
        }

        return $query->where('category', $slug);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $query;
        }

        $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $term) . '%';

        return $query->where(function (Builder $q) use ($like) {
            $q->where('description', 'like', $like)
                ->orWhere('activity_type', 'like', $like)
                ->orWhere('ip_address', 'like', $like)
                ->orWhere('route_name', 'like', $like)
                ->orWhereHas('admin', function ($admin) use ($like) {
                    $admin->withTrashed()
                        ->where('f_name', 'like', $like)
                        ->orWhere('l_name', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhereRaw("concat(coalesce(f_name, ''), ' ', coalesce(l_name, '')) like ?", [$like]);
                });
        });
    }
}