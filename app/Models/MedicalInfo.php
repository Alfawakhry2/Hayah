<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MedicalInfo extends Model
{
    protected $fillable = [
        'child_id' , 'age' , 'length' , 'weight' , 'diagnosis' , 'severity' , 'has_seizures' , 'on_medication' , 'medication_name'
    ];

    protected $casts = [
        'has_seizures' => 'boolean' ,
        'on_medication' => 'boolean' ,
    ];



    public function child(){
        return $this->belongsTo(Child::class);
    }
}
