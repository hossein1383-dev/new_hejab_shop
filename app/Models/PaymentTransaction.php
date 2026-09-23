<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentTransaction extends Model
{
    protected $fillable = ['payment_id', 'type', 'status', 'raw_response'];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
