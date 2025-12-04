<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Car extends Model
{
    use HasFactory;

    protected $fillable = [
        'brand',
        'model',
        'plate_number',
        'year',
        'price_per_day',
        'image', // main image (optional)
    ];

    public function images()
    {
        return $this->hasMany(CarImage::class);
    }
}
