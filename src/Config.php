<?php

declare(strict_types=1);

namespace NoCodeDbtTransformation;

use Keboola\Component\Config\BaseConfig;
use RuntimeException;

class Config extends BaseConfig
{
    /**
     * @return array<int, string>
     */
    public function getModels(): array
    {
        return $this->getArrayValue(['parameters', 'models']);
    }

    /**
     * @return array<string, string>
     */
    public function getCredentials(): array
    {
        $credentials = $this->getArrayValue(['parameters', 'authorization']);
        $credentials['password'] = $credentials['#password'];
        unset($credentials['#password']);

        return $credentials;
    }

    public function getStorageApiToken(): string
    {
        $token = getenv('KBC_TOKEN');
        if (!$token) {
            throw new RuntimeException('KBC_TOKEN environment variable must be set');
        }

        return $token;
    }

    public function getStorageApiUrl(): string
    {
        $url = getenv('KBC_URL');
        if (!$url) {
            throw new RuntimeException('KBC_URL environment variable must be set');
        }

        return $url;
    }
}
