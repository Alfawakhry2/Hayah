<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'image',
        'phone',
        'email',
        'country_id',
        'governorate_id',
        'nationality_id',
        'password',
        'phone_code',
        'registration_token',
        'is_complete',
        'phone_verified_at'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'image'
    ];

    protected $appends = [
        'image_url'
    ];
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'phone_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_complete' => 'boolean',

        ];
    }
    // required methods
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }


    //relationships
    public function children()
    {
        ## here mean the parent (belongs to one only)
        return $this->hasMany(Child::class);
    }

    public function nationality()
    {
        return $this->belongsTo(Nationality::class);
    }

    public function country(){
        return $this->belongsTo(Country::class);
    }


    //accessors

    public function getImageUrlAttribute($value){
        if($this->image){
            return config('app.url').'/storage/'.$this->image;
        }
        return "https://placehold.co/150x150/fff/000/png";
    }
}
