<?php

namespace App\Domain\Quotations;

use DateTimeInterface;

/**
 * Representa o resultado imutavel da classificacao de qualidade da cotacao.
 */
class QuotationQualityResult
{
    /**
     * Monta o resultado com status, motivo e data de invalidacao.
     */
    public function __construct(
        public readonly string $status,
        public readonly ?string $invalidReason,
        public readonly ?DateTimeInterface $invalidatedAt
    ) {}

    /**
     * Cria um resultado valido sem motivo de invalidacao.
     */
    public static function valid(): static
    {
        return new static(
            status: QuotationStatus::Valid->value,
            invalidReason: null,
            invalidatedAt: null
        );
    }

    /**
     * Cria um resultado invalido para preco nao positivo.
     */
    public static function invalidNonPositive(?DateTimeInterface $invalidatedAt = null): static
    {
        return new static(
            status: QuotationStatus::Invalid->value,
            invalidReason: QuotationInvalidReason::NonPositivePrice->value,
            invalidatedAt: $invalidatedAt
        );
    }

    /**
     * Cria um resultado invalido para preco fora do intervalo esperado.
     */
    public static function invalidOutlier(?DateTimeInterface $invalidatedAt = null): static
    {
        return new static(
            status: QuotationStatus::Invalid->value,
            invalidReason: QuotationInvalidReason::OutlierPrice->value,
            invalidatedAt: $invalidatedAt
        );
    }

    /**
     * Cria um resultado invalido para cotacao duplicada.
     */
    public static function invalidDuplicate(?DateTimeInterface $invalidatedAt = null): static
    {
        return new static(
            status: QuotationStatus::Invalid->value,
            invalidReason: QuotationInvalidReason::DuplicateQuote->value,
            invalidatedAt: $invalidatedAt
        );
    }

    /**
     * Informa se a cotacao foi classificada como valida.
     */
    public function isValid(): bool
    {
        return $this->status === QuotationStatus::Valid->value;
    }

    /**
     * Informa se a cotacao foi classificada como invalida.
     */
    public function isInvalid(): bool
    {
        return $this->status === QuotationStatus::Invalid->value;
    }

    /**
     * Informa se a invalidacao ocorreu por preco nao positivo.
     */
    public function isNonPositive(): bool
    {
        return $this->invalidReason === QuotationInvalidReason::NonPositivePrice->value;
    }

    /**
     * Informa se a invalidacao ocorreu por outlier de preco.
     */
    public function isOutlier(): bool
    {
        return $this->invalidReason === QuotationInvalidReason::OutlierPrice->value;
    }

    /**
     * Retorna uma copia do resultado com timestamp de invalidacao atualizado.
     */
    public function withInvalidatedAt(?DateTimeInterface $invalidatedAt): static
    {
        return new static(
            status: $this->status,
            invalidReason: $this->invalidReason,
            invalidatedAt: $invalidatedAt
        );
    }

    /**
     * Exporta o resultado em formato de array para serializacao.
     *
     * @return array{status: string, invalid_reason: string|null, invalidated_at: DateTimeInterface|null}
     */
    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'invalid_reason' => $this->invalidReason,
            'invalidated_at' => $this->invalidatedAt,
        ];
    }
}
