<?php

namespace App\Models;

use Database\Factories\BookingPassengerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'booking_id',
    'passenger_index',
    'title',
    'first_name',
    'last_name',
    'date_of_birth',
    'nationality',
    'passport_number',
    'meta',
])]
class BookingPassenger extends Model
{
    /** @use HasFactory<BookingPassengerFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'meta' => 'array',
        ];
    }

    /** @return BelongsTo<Booking, $this> */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
