<?php

declare(strict_types=1);

namespace NoCodeDbtTransformation;

use Keboola\Component\Config\BaseConfig;

class Config extends BaseConfig
{
    /**
     * @return array<int, string>
     */
    public function getModels(): array
    {
        return $this->getArrayValue(['parameters', 'models']);
    }
}
