<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionPhoto extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'photo_path',
        'photo_type', // 'before', 'after', 'verification'
        'uploaded_by',
        'notes',
    ];

    // Relationships
    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    // Accessor for full URL
    public function getPhotoUrlAttribute()
    {
        return asset('storage/' . $this->photo_path);
    }

    // Helper methods
    public function isBeforePhoto()
    {
        return $this->photo_type === 'before';
    }

    public function isAfterPhoto()
    {
        return $this->photo_type === 'after';
    }

    public function isVerificationPhoto()
    {
        return $this->photo_type === 'verification';
    }
}
