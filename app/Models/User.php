<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
// use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory;
    // use SoftDeletes;

    // protected $dates = ['deleted_at'];

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'image',
        'toggle',
        'fcm_token',
        'login_type',
        'google_social_id',
        'apple_social_id',
        'deactivation_reason',
        'otp',
    ];
}
