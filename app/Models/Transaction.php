<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable =
    [
        'invoice_number',
        'user_id',
        'transaction_status_id',
        'item_total_price',
        'item_total_net_price',
        'voucher_price',
        'amount',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function review()
    {
        return $this->hasOne(Review::class);
    }
}
