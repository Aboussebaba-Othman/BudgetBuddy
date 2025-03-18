<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Settlement extends Model
{
    use HasFactory;

    /**
     * Les attributs qui sont mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'group_id',
        'from_user_id',
        'to_user_id',
        'amount',
        'status',
        'note',
        'settled_at',
    ];

    /**
     * Les attributs qui doivent être castés.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'settled_at' => 'datetime',
    ];

    /**
     * Obtenir le groupe associé à ce règlement.
     */
    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * Obtenir l'utilisateur qui doit effectuer le paiement.
     */
    public function fromUser()
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    /**
     * Obtenir l'utilisateur qui doit recevoir le paiement.
     */
    public function toUser()
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }
}