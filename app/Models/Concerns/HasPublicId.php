<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Concerns\HasUniqueStringIds;

/**
 * A short random public_id for URLs (e.g. /circles/k3x9q2mzt8), so they don't reveal the incrementing id.
 * The model's table needs a unique `char('public_id', 10)` column.
 */
trait HasPublicId
{
    use HasUniqueStringIds;

    /**
     * The characters a public id is made of: lowercase letters and digits only, so it reads cleanly in a URL.
     */
    private const string PUBLIC_ID_ALPHABET = '0123456789abcdefghijklmnopqrstuvwxyz';

    private const int PUBLIC_ID_LENGTH = 10;

    /**
     * Fill public_id on create, leaving the incrementing id as the primary key.
     *
     * @return list<string>
     */
    public function uniqueIds(): array
    {
        return ['public_id'];
    }

    /**
     * A new random public id.
     */
    public function newUniqueId(): string
    {
        $publicId = '';

        for ($character = 0; $character < self::PUBLIC_ID_LENGTH; $character++) {
            $publicId .= self::PUBLIC_ID_ALPHABET[random_int(0, strlen(self::PUBLIC_ID_ALPHABET) - 1)];
        }

        return $publicId;
    }

    /**
     * Find the model in URLs by its public id.
     */
    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    /**
     * Whether a value looks like a public id, so malformed URLs are a 404 without a query.
     */
    protected function isValidUniqueId(mixed $value): bool
    {
        return is_string($value) && preg_match('/^[0-9a-z]{'.self::PUBLIC_ID_LENGTH.'}$/', $value) === 1;
    }
}
