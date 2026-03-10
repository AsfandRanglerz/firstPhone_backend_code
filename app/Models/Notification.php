<?php

namespace App\Models;

use App\Models\NotificationTarget;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'user_type', 'title', 'description', 'sent_by', 'delete_by_admin'];

    public function targets()
    {
        return $this->hasMany(NotificationTarget::class);
    }
}
