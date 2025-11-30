<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'role',
        'action',
        'device',
        'transaction_id',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public static function log($action, $user = null, $message = null, $metadata = null, $transaction_id = null, $role = null, $device = null)
    {
        return self::create([
            'user_id' => $user?->id,
            'role' => $role ?? $user?->role,
            'action' => $action,
            'device' => $device,
            'transaction_id' => $transaction_id,
            'metadata' => is_array($metadata) ? $metadata : ['message' => $message],
        ]);
    }
}
