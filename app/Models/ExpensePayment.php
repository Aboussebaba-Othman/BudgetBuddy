<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExpensePayment extends Model
{
    use HasFactory;

    /**
     * Les attributs qui sont mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'group_expense_id',
        'user_id',
        'amount_paid',
    ];

    /**
     * Les attributs qui doivent être castés.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount_paid' => 'decimal:2',
    ];

    /**
     * Obtenir la dépense de groupe associée à ce paiement.
     */
    public function groupExpense()
    {
        return $this->belongsTo(GroupExpense::class);
    }

    /**
     * Obtenir l'utilisateur qui a effectué ce paiement.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}