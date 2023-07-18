<?php

declare(strict_types=1);

namespace NoCodeDbtTransformation\DbtYamlCreateService;

use Symfony\Component\Yaml\Yaml;

class DbtProfilesYamlCreateService extends DbtYamlCreateService
{
    /**
     * @param array<int, int> $projectIds
     */
    public function dumpYaml(string $projectPath, array $projectIds): void
    {
        $outputs['kbc_prod'] = $this->getOutputDefinition('KBC_PROD');
        foreach ($projectIds as $projectId) {
            $configurationName = sprintf('kbc_prod_%d', $projectId);
            $outputs[$configurationName] = self::getOutputDefinition(strtoupper($configurationName));
        }

        $this->filesystem->dumpFile(
            sprintf('%s/profiles.yml', $projectPath),
            Yaml::dump([
                'default' => [
                    'target' => 'dev',
                    'outputs' => $outputs,
                ],
            ], 5)
        );
    }

    /**
     * @return array<string, string>
     */
    protected function getOutputDefinition(string $configurationName): array
    {
        return [
            'type' => sprintf('{{ env_var("DBT_%s_TYPE") }}', $configurationName),
            'user' => sprintf('{{ env_var("DBT_%s_USER") }}', $configurationName),
            'password' => sprintf('{{ env_var("DBT_%s_PASSWORD") }}', $configurationName),
            'schema' => sprintf('{{ env_var("DBT_%s_SCHEMA") }}', $configurationName),
            'warehouse' => sprintf('{{ env_var("DBT_%s_WAREHOUSE") }}', $configurationName),
            'database' => sprintf('{{ env_var("DBT_%s_DATABASE") }}', $configurationName),
            'account' => sprintf('{{ env_var("DBT_%s_ACCOUNT") }}', $configurationName),
        ];
    }
}
