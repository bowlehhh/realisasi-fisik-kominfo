<?php

namespace App\Repositories;

use App\Contracts\MicrosoftConnectionRepository;
use App\Models\MicrosoftConnection;
use DateTimeInterface;

class EloquentMicrosoftConnectionRepository implements MicrosoftConnectionRepository
{
    public function find(): ?MicrosoftConnection
    {
        return MicrosoftConnection::query()->find(1);
    }

    public function exists(): bool
    {
        return MicrosoftConnection::query()->whereKey(1)->exists();
    }

    public function saveTokens(string $accessToken, string $refreshToken, DateTimeInterface $expiresAt): MicrosoftConnection
    {
        $connection = $this->find() ?? new MicrosoftConnection(['id' => 1]);

        $connection->forceFill([
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_at' => $expiresAt,
        ])->save();

        return $connection;
    }

    public function delete(): void
    {
        MicrosoftConnection::query()->whereKey(1)->delete();
    }
}
