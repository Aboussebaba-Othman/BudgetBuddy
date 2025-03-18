<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExpenseShare extends Model
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
        'share_percentage',
        'share_amount',
    ];

    /**
     * Les attributs qui doivent être castés.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'share_percentage' => 'decimal:2',
        'share_amount' => 'decimal:2',
    ];

    /**
     * Obtenir la dépense de groupe associée à cette part.
     */
    public function groupExpense()
    {
        return $this->belongsTo(GroupExpense::class);
    }

    /**
     * Obtenir l'utilisateur associé à cette part.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}