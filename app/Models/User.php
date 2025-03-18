<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }
    
    public function tags()
    {
        return $this->hasMany(Tag::class);
    }

    public function groups()
{
    return $this->belongsToMany(Group::class)
        ->withPivot('is_admin')
        ->withTimestamps();
}

/**
 * Les groupes que l'utilisateur administre.
 */
public function adminGroups()
{
    return $this->belongsToMany(Group::class)
        ->wherePivot('is_admin', true)
        ->withTimestamps();
}

/**
 * Les groupes créés par l'utilisateur.
 */
public function createdGroups()
{
    return $this->hasMany(Group::class, 'created_by');
}

/**
 * Les dépenses de groupe créées par l'utilisateur.
 */
public function createdGroupExpenses()
{
    return $this->hasMany(GroupExpense::class, 'created_by');
}

/**
 * Les paiements effectués par l'utilisateur dans des dépenses de groupe.
 */
public function expensePayments()
{
    return $this->hasMany(ExpensePayment::class);
}

/**
 * Les parts de dépenses associées à l'utilisateur.
 */
public function expenseShares()
{
    return $this->hasMany(ExpenseShare::class);
}

/**
 * Les règlements que l'utilisateur doit effectuer.
 */
public function outgoingSettlements()
{
    return $this->hasMany(Settlement::class, 'from_user_id');
}

/**
 * Les règlements que l'utilisateur doit recevoir.
 */
public function incomingSettlements()
{
    return $this->hasMany(Settlement::class, 'to_user_id');
}
}
