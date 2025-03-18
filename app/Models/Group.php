<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'created_by',
        'currency',
    ];

    
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    
    public function users()
    {
        return $this->belongsToMany(User::class)
            ->withPivot('is_admin')
            ->withTimestamps();
    }

    
    public function admins()
    {
        return $this->belongsToMany(User::class)
            ->wherePivot('is_admin', true)
            ->withTimestamps();
    }

    
    public function expenses()
    {
        return $this->hasMany(GroupExpense::class);
    }

    
    public function settlements()
    {
        return $this->hasMany(Settlement::class);
    }
    
    
    public function isMember($userId)
    {
        return $this->users()->where('user_id', $userId)->exists();
    }

    
    public function isAdmin($userId)
    {
        return $this->users()
            ->where('user_id', $userId)
            ->wherePivot('is_admin', true)
            ->exists();
    }
}