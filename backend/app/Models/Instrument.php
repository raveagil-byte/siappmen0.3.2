<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Instrument extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function instrumentUnitStatuses()
    {
        return $this->hasMany(InstrumentUnitStatus::class);
    }

    public function transactionItems()
    {
        return $this->hasMany(TransactionItem::class);
    }

    public function trayInstruments()
    {
        return $this->belongsToMany(Instrument::class, 'instrument_tray_items', 'tray_id', 'instrument_id')
                    ->withPivot('quantity')
                    ->withTimestamps();
    }

    public function trays()
    {
        return $this->belongsToMany(Instrument::class, 'instrument_tray_items', 'instrument_id', 'tray_id')
                    ->withPivot('quantity')
                    ->withTimestamps();
    }
}
