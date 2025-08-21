<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChildAbility extends Model
{
    protected $fillable = [
        'child_id' , 'can_sit' , 'can_walk' , 'uses_hand' , 'target_goals'
    ];


    protected $casts = [
        'target_goals' =>  'array' ,

    ];


    public function child(){
        return $this->belongsTo(Child::class);
    }
}
