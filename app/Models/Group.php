<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    use HasFactory;

    /**
     * Les attributs qui sont mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'created_by',
        'currency',
        'description',
    ];

    /**
     * Obtenir l'utilisateur qui a créé le groupe.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Les utilisateurs qui appartiennent à ce groupe.
     */
    public function users()
    {
        return $this->belongsToMany(User::class)
            ->withPivot('is_admin')
            ->withTimestamps();
    }

    /**
     * Les administrateurs du groupe.
     */
    public function admins()
    {
        return $this->belongsToMany(User::class)
            ->wherePivot('is_admin', true)
            ->withTimestamps();
    }

    /**
     * Les dépenses associées à ce groupe.
     */
    public function expenses()
    {
        return $this->hasMany(GroupExpense::class);
    }

    /**
     * Les règlements associés à ce groupe.
     */
    public function settlements()
    {
        return $this->hasMany(Settlement::class);
    }
    
    /**
     * Vérifie si un utilisateur est membre du groupe.
     *
     * @param  int  $userId
     * @return bool
     */
    public function isMember($userId)
    {
        return $this->users()->where('user_id', $userId)->exists();
    }

    /**
     * Vérifie si un utilisateur est administrateur du groupe.
     *
     * @param  int  $userId
     * @return bool
     */
    public function isAdmin($userId)
    {
        return $this->users()
            ->where('user_id', $userId)
            ->wherePivot('is_admin', true)
            ->exists();
    }
}