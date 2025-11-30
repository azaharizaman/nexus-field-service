<?php

declare(strict_types=1);

namespace Nexus\FieldService\ValueObjects;

use Nexus\FieldService\Exceptions\InvalidSignatureException;

/**
 * Customer Signature Value Object
 *
 * Immutable representation of a customer's digital signature with security hash.
 * Tier 3 adds optional RFC 3161 cryptographic timestamp for legal non-repudiation.
 */
final readonly class CustomerSignature
{
    private const string HASH_ALGORITHM = 'sha256';

    private function __construct(
        private string $signatureData,
        private string $hash,
        private \DateTimeImmutable $capturedAt,
        private ?string $timestampSignature = null
    ) {
        // Validate hash matches data
        $expectedHash = hash(self::HASH_ALGORITHM, $signatureData);
        if ($hash !== $expectedHash) {
            throw new InvalidSignatureException('Signature hash does not match data');
        }
    }

    /**
     * Create a customer signature from base64-encoded image data.
     *
     * @param string $signatureData Base64-encoded image data
     * @param string|null $timestampSignature Optional RFC 3161 timestamp (Tier 3)
     */
    public static function create(
        string $signatureData,
        ?\DateTimeImmutable $capturedAt = null,
        ?string $timestampSignature = null
    ): self {
        $hash = hash(self::HASH_ALGORITHM, $signatureData);
        
        return new self(
            $signatureData,
            $hash,
            $capturedAt ?? new \DateTimeImmutable(),
            $timestampSignature
        );
    }

    /**
     * Reconstruct signature from stored components.
     */
    public static function fromComponents(
        string $signatureData,
        string $hash,
        \DateTimeImmutable $capturedAt,
        ?string $timestampSignature = null
    ): self {
        return new self($signatureData, $hash, $capturedAt, $timestampSignature);
    }

    /**
     * Get the signature data (base64-encoded image).
     */
    public function getSignatureData(): string
    {
        return $this->signatureData;
    }

    /**
     * Get the SHA-256 hash of the signature.
     */
    public function getHash(): string
    {
        return $this->hash;
    }

    /**
     * Get the timestamp when signature was captured.
     */
    public function getCapturedAt(): \DateTimeImmutable
    {
        return $this->capturedAt;
    }

    /**
     * Get the cryptographic timestamp signature (Tier 3 only).
     */
    public function getTimestampSignature(): ?string
    {
        return $this->timestampSignature;
    }

    /**
     * Check if this signature has cryptographic timestamp (Tier 3 feature).
     */
    public function hasTimestamp(): bool
    {
        return $this->timestampSignature !== null;
    }

    /**
     * Verify the integrity of the signature data.
     */
    public function verify(): bool
    {
        return $this->hash === hash(self::HASH_ALGORITHM, $this->signatureData);
    }

    /**
     * Get the size of the signature data in bytes.
     */
    public function getSize(): int
    {
        return strlen($this->signatureData);
    }

    /**
     * Check if this signature equals another.
     */
    public function equals(self $other): bool
    {
        return $this->hash === $other->hash;
    }

    /**
     * Convert to array format for storage.
     *
     * @return array{signature_data: string, hash: string, captured_at: string, timestamp_signature: string|null}
     */
    public function toArray(): array
    {
        return [
            'signature_data' => $this->signatureData,
            'hash' => $this->hash,
            'captured_at' => $this->capturedAt->format('c'),
            'timestamp_signature' => $this->timestampSignature,
        ];
    }
}
