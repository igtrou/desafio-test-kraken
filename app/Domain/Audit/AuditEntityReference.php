<?php

namespace App\Domain\Audit;

/**
 * Referencia tipada de entidade para associacao de auditoria.
 */
class AuditEntityReference
{
    public const TYPE_USER = 'user';
    public const TYPE_QUOTATION = 'quotation';

    /**
     * Define o tipo logico da entidade e seu identificador persistido.
     */
    public function __construct(
        public readonly string $type,
        public readonly int $id,
    ) {}

    /**
     * Fabrica referencia de usuario.
     */
    public static function user(int $userId): self
    {
        return new self(self::TYPE_USER, $userId);
    }

    /**
     * Fabrica referencia de cotacao.
     */
    public static function quotation(int $quotationId): self
    {
        return new self(self::TYPE_QUOTATION, $quotationId);
    }
}
