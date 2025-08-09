<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'producer_id',
        'listening_history_id',
        'is_read',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function producer()
    {
        return $this->belongsTo(Producer::class);
    }

    public function track()
    {
        return $this->belongsTo(ListeningHistory::class, 'listening_history_id');
    }
}
