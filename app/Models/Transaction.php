<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class Transaction extends Model
{
    use HasFactory;

    /** ===== Status ===== */
    public const STATUS_PENDING = 'pending';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_RECONCILED = 'reconciled';

    /** ===== Types (conforme sua tabela) ===== */
    public const TYPE_INCOME = 'income';

    public const TYPE_EXPENSE = 'expense';

    public const TYPE_TRANSFER = 'transfer';

    public const TYPE_TRANSFER_IN = 'transfer_in';

    public const TYPE_TRANSFER_OUT = 'transfer_out';

    /** ===== Fillable ===== */
    protected $fillable = [
        'client_id',
        'account_id',
        'card_id',
        'invoice_month',         // 'YYYY-MM'
        'date',
        'amount',
        'installment_count',     // int
        'installment_index',     // int
        'status',
        'type',                  // enum
        'invoice_paid',          // bool
        'method',
        'notes',
        'parent_transaction_id', // fk para a "mãe"
    ];

    /** ===== Casts ===== */
    protected $casts = [
        'date' => 'datetime',
        'amount' => 'decimal:2',
        'invoice_paid' => 'boolean',
        'installment_count' => 'integer',
        'installment_index' => 'integer',
        'parent_transaction_id' => 'integer',
    ];

    /** ===== Relations principais ===== */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function card(): BelongsTo
    {
        return $this->belongsTo(Card::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'parent_transaction_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Transaction::class, 'parent_transaction_id');
    }

    public function splits(): HasMany
    {
        return $this->hasMany(TransactionSplit::class);
    }

    /**
     * Link 1:1 (pivot) que guarda (category_id, subcategory_id) da transação.
     * Tabela: transaction_categories
     */
    public function categoryLink(): HasOne
    {
        return $this->hasOne(TransactionCategory::class, 'transaction_id');
    }

    /**
     * Acesso direto à CATEGORIA principal da transação via hasOneThrough.
     * (transaction) -> (transaction_categories.category_id) -> (categories.id)
     */
    public function category(): HasOneThrough
    {
        return $this->hasOneThrough(
            Category::class,            // Model final
            TransactionCategory::class, // Through
            'transaction_id',           // FK em transaction_categories que aponta p/ transactions
            'id',                       // Chave primária em categories
            'id',                       // Chave local em transactions
            'category_id'               // FK em transaction_categories que aponta p/ categories
        );
    }

    /**
     * Acesso direto à SUBCATEGORIA principal da transação via hasOneThrough.
     * (transaction) -> (transaction_categories.subcategory_id) -> (subcategories.id)
     */
    public function subcategory(): HasOneThrough
    {
        return $this->hasOneThrough(
            Subcategory::class,         // <<< AGORA usa o model Subcategory
            TransactionCategory::class, // Through
            'transaction_id',           // FK em transaction_categories -> transactions
            'id',                       // PK em subcategories
            'id',                       // PK local em transactions
            'subcategory_id'            // FK em transaction_categories -> subcategories
        );
    }

    /** ===== Scopes úteis (opcional) ===== */
    public function scopeOfClient($q, int $clientId)
    {
        return $q->where('client_id', $clientId);
    }

    public function scopeConfirmed($q)
    {
        return $q->where('status', self::STATUS_CONFIRMED);
    }

    public function scopeIncome($q)
    {
        return $q->where('type', self::TYPE_INCOME);
    }

    public function scopeExpense($q)
    {
        return $q->where('type', self::TYPE_EXPENSE);
    }

    public function scopeSince($q, $date)
    {
        return $q->where('date', '>=', $date);
    }

    /** ===== Helpers (opcional) ===== */
    public function isExpense(): bool
    {
        return $this->type === self::TYPE_EXPENSE || $this->amount < 0;
    }

    public function isIncome(): bool
    {
        return $this->type === self::TYPE_INCOME || $this->amount > 0;
    }

    public function isTransfer(): bool
    {
        return in_array($this->type, [self::TYPE_TRANSFER, self::TYPE_TRANSFER_IN, self::TYPE_TRANSFER_OUT], true);
    }
}
