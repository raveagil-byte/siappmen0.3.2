<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstrumentUnitStatus extends Model
{
    use HasFactory;

    protected $table = 'instrument_unit_status';

    protected $fillable = [
        'unit_id',
        'instrument_id',
        'stock_steril',
        'stock_kotor',
        'stock_in_use',
    ];

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function instrument()
    {
        return $this->belongsTo(Instrument::class);
    }
}
