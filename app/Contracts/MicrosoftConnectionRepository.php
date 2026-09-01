<?php

namespace App\Contracts;

use App\Models\MicrosoftConnection;
use DateTimeInterface;

interface MicrosoftConnectionRepository
{
    public function find(): ?MicrosoftConnection;

    public function exists(): bool;

    public function saveTokens(string $accessToken, string $refreshToken, DateTimeInterface $expiresAt): MicrosoftConnection;

    public function delete(): void;
}
