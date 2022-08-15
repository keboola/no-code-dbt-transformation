<?php

declare(strict_types=1);

namespace NoCodeDbtTransformation;

use Keboola\Component\BaseComponent;
use Keboola\StorageApi\Client;
use NoCodeDbtTransformation\DbtYamlCreateService\DbtProfilesYamlCreateService;
use NoCodeDbtTransformation\DbtYamlCreateService\DbtSourceYamlCreateService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;

class Component extends BaseComponent
{
    public const STRING_TO_REMOVE_FROM_HOST = '.snowflakecomputing.com';

    private DbtSourceYamlCreateService $createSourceFileService;
    private DbtProfilesYamlCreateService $createProfilesFileService;
    private Filesystem $filesystem;
    private string $projectPath;

    public function __construct(LoggerInterface $logger)
    {
        parent::__construct($logger);
        $this->createProfilesFileService = new DbtProfilesYamlCreateService;
        $this->createSourceFileService = new DbtSourceYamlCreateService;
        $this->filesystem = new Filesystem();
    }

    /**
     * @throws \Keboola\Component\UserException
     */
    protected function run(): void
    {
        $config = $this->getConfig();
        $this->setProjectPath($this->getDataDir());
        $this->filesystem->mirror(__DIR__ . '/../empty-dbt-project', $this->projectPath);
        foreach ($config->getModels() as $key => $model) {
            $this->filesystem->dumpFile(
                sprintf('%s/models/model%d.sql', $this->projectPath, $key + 1),
                $model
            );
        }
        $this->createDbtYamlFiles($config);

        (new DbtRunService($this->projectPath))->run();
    }

    protected function createDbtYamlFiles(Config $config): void
    {
        $workspace = $config->getAuthorization()['workspace'];
        $this->createProfilesFileService->dumpYaml($this->projectPath);

        $this->setEnvVars($workspace);

        $client = new Client(['url' => $config->getStorageApiUrl(), 'token' => $config->getStorageApiToken()]);
        $tables = $client->listTables();
        $tablesData = [];
        foreach ($tables as $table) {
            $tablesData[(string) $table['bucket']['id']][] = $table;
        }

        $this->createSourceFileService->dumpYaml(
            $this->projectPath,
            $tablesData
        );
    }

    /**
     * @param array<string, string> $workspace
     */
    private function setEnvVars(array $workspace): void
    {
        putenv(sprintf('DBT_KBC_PROD_DATABASE=%s', $workspace['database']));
        putenv(sprintf('DBT_KBC_PROD_SCHEMA=%s', $workspace['schema']));
        putenv(sprintf('DBT_KBC_PROD_WAREHOUSE=%s', $workspace['warehouse']));
        $account = str_replace(self::STRING_TO_REMOVE_FROM_HOST, '', $workspace['host']);
        putenv(sprintf('DBT_KBC_PROD_ACCOUNT=%s', $account));
        putenv(sprintf('DBT_KBC_PROD_TYPE=%s', 'snowflake'));
        putenv(sprintf('DBT_KBC_PROD_USER=%s', $workspace['user']));
        putenv(sprintf('DBT_KBC_PROD_PASSWORD=%s', $workspace['password']));
    }

    public function getConfig(): Config
    {
        /** @var Config $config */
        $config = parent::getConfig();
        return $config;
    }

    protected function setProjectPath(string $dataDir): void
    {
        $this->projectPath = sprintf('%s/%s', $dataDir, 'dbt-project');
    }

    protected function getConfigClass(): string
    {
        return Config::class;
    }

    protected function getConfigDefinitionClass(): string
    {
        return ConfigDefinition::class;
    }
}
