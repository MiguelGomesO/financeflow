<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use App\Support\Receipts;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

class Transaction extends Model
{
    use BelongsToUser, HasFactory;

    protected $fillable = [
        'description', 'amount', 'date', 'category_id', 'account_id', 'credit_card_id',
        'installment_group', 'installment_number', 'installment_total', 'attachment_path',
        'external_id',
    ];

    protected static function booted(): void
    {
        // Sem transação usando o comprovante, o arquivo vai embora junto.
        static::deleted(fn (Transaction $transaction) => Receipts::releaseIfUnused($transaction->attachment_path));
    }

    protected function casts(): array
    {
        return [
            'amount' => 'float',
            'date' => 'date',
        ];
    }

    /** Grava só a data ("Y-m-d"), sem hora, para comparações por data funcionarem em qualquer banco. */
    protected function date(): Attribute
    {
        return Attribute::set(fn ($value) => Carbon::parse($value)->toDateString());
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class)->orderByRaw('lower(tags.name)');
    }

    public function creditCard(): BelongsTo
    {
        return $this->belongsTo(CreditCard::class);
    }

    /** Nome de onde o dinheiro saiu ou entrou: a conta ou o cartão. */
    public function getSourceNameAttribute(): ?string
    {
        return $this->account?->name ?? $this->creditCard?->name;
    }

    /** "2/10" para parcelas de uma compra parcelada; null nas demais. */
    public function getInstallmentLabelAttribute(): ?string
    {
        return $this->installment_group ? "{$this->installment_number}/{$this->installment_total}" : null;
    }

    public function scopeBetweenDates(Builder $query, CarbonInterface $start, CarbonInterface $end): void
    {
        $query->whereBetween('date', [$start->toDateString(), $end->toDateString()]);
    }

    public function scopeLatestFirst(Builder $query): void
    {
        $query->orderByDesc('date')->orderByDesc('id');
    }
}
