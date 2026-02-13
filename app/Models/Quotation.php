<?php

namespace App\Models;

use App\Domain\Quotations\QuotationInvalidReason;
use App\Domain\Quotations\QuotationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Quotation extends Model
{
    /** @use HasFactory<\Database\Factories\QuotationFactory> */
    use HasFactory, SoftDeletes;

    public const STATUS_VALID = QuotationStatus::Valid->value;

    public const STATUS_INVALID = QuotationStatus::Invalid->value;

    public const INVALID_REASON_DUPLICATE = QuotationInvalidReason::DuplicateQuote->value;

    public const INVALID_REASON_OUTLIER = QuotationInvalidReason::OutlierPrice->value;

    public const INVALID_REASON_NON_POSITIVE = QuotationInvalidReason::NonPositivePrice->value;

    protected $fillable = [
        'asset_id',
        'price',
        'currency',
        'source',
        'status',
        'invalid_reason',
        'invalidated_at',
        'quoted_at',
    ];

    protected $casts = [
        'price' => 'decimal:6',
        'status' => 'string',
        'invalid_reason' => 'string',
        'invalidated_at' => 'datetime',
        'quoted_at' => 'datetime',
    ];

    /**
     * Executa a rotina principal do metodo asset.
     */
    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    /**
     * Executa a rotina principal do metodo scopeValid.
     */
    public function scopeValid($query)
    {
        return $query->where('status', self::STATUS_VALID);
    }

    /**
     * Executa a rotina principal do metodo scopeInvalid.
     */
    public function scopeInvalid($query)
    {
        return $query->where('status', self::STATUS_INVALID);
    }

    /**
     * Verifica o estado da condicao avaliada.
     */
    public function isValid(): bool
    {
        return $this->status === self::STATUS_VALID;
    }
}
