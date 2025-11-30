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
        'is_tray', // Added to identify if an instrument is a tray.
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_tray' => 'boolean',
    ];

    /**
     * Defines the relationship for instruments that are part of this tray.
     * A tray (which is an instrument itself) can have many instruments.
     */
    public function trayItems()
    {
        return $this->belongsToMany(Instrument::class, 'instrument_tray_items', 'tray_id', 'instrument_id')
                    ->withPivot('quantity')
                    ->withTimestamps();
    }

    /**
     * Defines the relationship for trays that this instrument belongs to.
     * An instrument can be part of many trays.
     */
    public function trays()
    {
        return $this->belongsToMany(Instrument::class, 'instrument_tray_items', 'instrument_id', 'tray_id')
                    ->withPivot('quantity')
                    ->withTimestamps();
    }

    public function instrumentUnitStatuses()
    {
        return $this->hasMany(InstrumentUnitStatus::class);
    }

    public function transactionItems()
    {
        return $this->hasMany(TransactionItem::class);
    }
}
