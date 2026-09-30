<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CardTransaction extends Model
{
    protected $fillable = [
        'family_id', 'credit_card_id', 'user_id', 'category_id',
        'description', 'amount', 'occurred_on', 'status', 'kind',
        'installment_group_id', 'installment_number', 'installments_total',
    ];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'occurred_on' => 'date'];
    }

    public function card(): BelongsTo
    {
        return $this->belongsTo(CreditCard::class, 'credit_card_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
