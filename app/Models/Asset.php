<?php

namespace App\Models;

use App\Domain\MarketData\AssetType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Asset extends Model
{
    /** @use HasFactory<\Database\Factories\AssetFactory> */
    use HasFactory;

    protected $fillable = [
        'symbol',
        'name',
        'type',
    ];

    protected $casts = [
        'symbol' => 'string',
        'name' => 'string',
        'type' => AssetType::class,
    ];

    /**
     * Executa a rotina principal do metodo quotations.
     */
    public function quotations(): HasMany
    {
        return $this->hasMany(Quotation::class);
    }

    /**
     * Define valores de configuracao para o fluxo atual.
     */
    /**
     * Normalize stored symbol as uppercase.
     */
    public function setSymbolAttribute(string $value): void
    {
        $this->attributes['symbol'] = strtoupper($value);
    }
}
