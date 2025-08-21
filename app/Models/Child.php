<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Child extends Model
{
    protected $fillable = [
        'user_id','name' , 'birth_date' , 'gender' , 'nationality' , 'city' , 'image' ,
    ];


    protected $hidden = [

    ];

    protected $casts = [
        'birth_date' => 'date' ,

    ];



    //relation

    public function parent(){
        return $this->belongsTo(User::class);
    }


    public function medicalInfo(){
        return $this->hasOne(MedicalInfo::class);
    }

    public function ability(){
        return $this->hasOne(ChildAbility::class);
    }
}
