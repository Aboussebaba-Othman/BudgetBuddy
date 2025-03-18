<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GroupExpense extends Model
{
    use HasFactory;

    /**
     * Les attributs qui sont mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'group_id',
        'created_by',
        'title',
        'description',
        'amount',
        'expense_date',
        'split_type',
    ];

    /**
     * Les attributs qui doivent être castés.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'expense_date' => 'date',
        'amount' => 'decimal:2',
    ];

    /**
     * Obtenir le groupe auquel cette dépense appartient.
     */
    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * Obtenir l'utilisateur qui a créé la dépense.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Les paiements associés à cette dépense.
     */
    public function payments()
    {
        return $this->hasMany(ExpensePayment::class);
    }

    /**
     * Les parts de dépense associées à cette dépense.
     */
    public function shares()
    {
        return $this->hasMany(ExpenseShare::class);
    }
}