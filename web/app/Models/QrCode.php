<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QrCode extends Model
{
    use HasFactory;

    protected $table = 'qr_codes';
    protected $fillable = [
        'shopDomain',
        'title',
        'productId',
        'variantId',
        'handle',
        'discountId',
        'discountCode',
        'destination',
        'scans'
    ];

    protected $dates = [
        'updated_at',
        'created_at'
    ];
}
