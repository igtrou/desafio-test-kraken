<?php

namespace App\Http\Requests\Concerns;

use App\Domain\MarketData\SymbolNormalizer;
use App\Domain\MarketData\Exceptions\InvalidSymbolException;

/**
 * Reuso de normalizacao de payload para requests de cotacoes e dashboard.
 */
trait NormalizesRequestInput
{
    private ?SymbolNormalizer $symbolNormalizer = null;

    /**
     * Retorna regras comuns para validacao de simbolos de ativos.
     *
     * @return array<int, string>
     */
    protected function symbolRules(string ...$prefixRules): array
    {
        return [
            ...$prefixRules,
            'string',
            'max:20',
            'regex:/^[A-Z0-9.\-\/_]+$/',
        ];
    }

    /**
     * Normaliza um campo de simbolo obrigatorio no payload.
     */
    protected function normalizeRequiredSymbol(string $field = 'symbol', mixed $value = null): void
    {
        $rawSymbol = $value ?? $this->input($field, '');

        $this->merge([
            $field => $this->normalizeSymbolValue($rawSymbol),
        ]);
    }

    /**
     * Normaliza um campo de simbolo opcional quando presente.
     */
    protected function normalizeOptionalSymbol(string $field = 'symbol'): void
    {
        $value = $this->input($field);

        if (is_string($value) && $value !== '') {
            $this->merge([
                $field => $this->normalizeSymbolValue($value),
            ]);
        }
    }

    /**
     * Normaliza um campo booleano aceitando representacoes textuais.
     */
    protected function normalizeBooleanInput(string $field): void
    {
        if (! $this->has($field)) {
            return;
        }

        $normalized = filter_var(
            $this->input($field),
            FILTER_VALIDATE_BOOLEAN,
            FILTER_NULL_ON_FAILURE
        );

        if ($normalized !== null) {
            $this->merge([
                $field => $normalized,
            ]);
        }
    }

    /**
     * Normaliza uma lista de campos booleanos.
     *
     * @param  array<int, string>  $fields
     */
    protected function normalizeBooleanInputs(array $fields): void
    {
        foreach ($fields as $field) {
            $this->normalizeBooleanInput($field);
        }
    }

    /**
     * Normaliza valor bruto de simbolo usando o normalizador de dominio.
     *
     * @throws InvalidSymbolException
     */
    protected function normalizeSymbolValue(mixed $value): string
    {
        $rawSymbol = (string) $value;

        return $this->symbolNormalizer()->normalize($rawSymbol);
    }

    /**
     * Reusa uma unica instancia do normalizador durante o ciclo da request.
     */
    private function symbolNormalizer(): SymbolNormalizer
    {
        return $this->symbolNormalizer ??= new SymbolNormalizer();
    }
}
