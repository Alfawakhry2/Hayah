<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Governorate extends Model
{
    use HasFactory;

    protected $fillable = ['country_id', 'governorate_name_ar', 'governorate_name_en'];


    protected $hidden = [
        'created_at' , 'updated_at'
    ];

    
    public function country()
    {
        return $this->belongsTo(Country::class);
    }

}
