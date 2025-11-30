<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Unit extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'location',
        'description',
        'uuid',
        'qr_code_path',
        'is_active',
    ];

    protected $casts = [
        'uuid' => 'string',
    ];

    public function instrumentStatuses()
    {
        return $this->hasMany(InstrumentUnitStatus::class, 'unit_id');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'unit_id');
    }
}
