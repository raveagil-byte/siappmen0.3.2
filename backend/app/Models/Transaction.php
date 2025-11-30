<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'unit_id',
        'creator_id',
        'validator_id',
        'type',
        'status',
        'notes',
        'cancel_reason',
    ];

    protected $casts = [
        'uuid' => 'string',
    ];

    // Relationships
    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function validator()
    {
        return $this->belongsTo(User::class, 'validator_id');
    }

    public function items()
    {
        return $this->hasMany(TransactionItem::class);
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function photos()
    {
        return $this->hasMany(TransactionPhoto::class);
    }

    // Helper methods
    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function isValidated()
    {
        return $this->status === 'validated';
    }

    public function isCancelled()
    {
        return $this->status === 'cancelled';
    }

    public function isSteril()
    {
        return $this->type === 'steril';
    }

    public function isKotor()
    {
        return $this->type === 'kotor';
    }

    public function getTotalQuantity()
    {
        return $this->items->sum('quantity');
    }

    public function getBeforePhotos()
    {
        return $this->photos->where('photo_type', 'before');
    }

    public function getAfterPhotos()
    {
        return $this->photos->where('photo_type', 'after');
    }

    public function getVerificationPhotos()
    {
        return $this->photos->where('photo_type', 'verification');
    }
}
