<?php

declare(strict_types=1);

namespace NoCodeDbtTransformation;

use Keboola\Component\BaseComponent;
use Keboola\SnowflakeDbAdapter\Connection;
use Keboola\StorageApi\Client;
use NoCodeDbtTransformation\DbtYamlCreateService\DbtProfilesYamlCreateService;
use NoCodeDbtTransformation\DbtYamlCreateService\DbtSourceYamlCreateService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;

class Component extends BaseComponent
{
    private const STRING_TO_REMOVE_FROM_HOST = '.snowflakecomputing.com';
    private const ACTION_PREVIEW = 'preview';
    private const STRING_MAX_LENGTH = 16000;
    private const PREVIEW_ROWS_LIMIT = 1000;

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
        $this->doTransformation();
    }

    /**
     * @return array{
     *     'columns': array<int, string>,
     *     'rows': array<int, array<int, array{'columnName': string, 'value': string, 'isTruncated': bool}>>
     * }
     * @throws \Keboola\SnowflakeDbAdapter\Exception\SnowflakeDbAdapterException|\Keboola\Component\UserException
     */
    public function preview(): array
    {
        $this->doTransformation(true);

        $config = $this->getConfig();
        $workspace = $config->getAuthorization()['workspace'];

        $connection = new Connection($workspace);
        $tableName = 'model_last';
        $columns = $connection->getTableColumns($workspace['schema'], $tableName);
        $rows = $connection->fetchAll(sprintf('SELECT * FROM "%s"."%s";', $workspace['schema'], $tableName));

        return ['columns' => $columns, 'rows' => $this->formatDataForPreview($rows)];
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

    /**
     * @return array<string, string>
     */
    protected function getSyncActions(): array
    {
        return [
            self::ACTION_PREVIEW => 'preview',
        ];
    }

    /**
     * @throws \Keboola\Component\UserException
     */
    protected function doTransformation(bool $onlyPreview = false): void
    {
        $config = $this->getConfig();
        $this->setProjectPath($this->getDataDir());
        $this->filesystem->mirror(__DIR__ . '/../empty-dbt-project', $this->projectPath);
        $modelsCount = count($config->getModels());
        foreach ($config->getModels() as $key => $model) {
            $limit = $onlyPreview && $key === 0;
            $isLast = $key + 1 === $modelsCount;

            $this->filesystem->dumpFile(
                sprintf('%s/models/model%s.sql', $this->projectPath, $isLast ? '_last' : $key + 1),
                $limit ? sprintf('%s LIMIT %d', $model, self::PREVIEW_ROWS_LIMIT) : $model
            );
        }
        $this->createDbtYamlFiles($config);

        (new DbtRunService($this->projectPath))->run();
    }

    /**
     * @param array<int, array<string, string|int|bool|float>> $rows
     * @return array<int, array<int, array{'columnName': string, 'value': string, 'isTruncated': bool}>>
     */
    protected function formatDataForPreview(array $rows): array
    {
        $data = [];

        foreach ($rows as $key => $row) {
            foreach ($row as $columnName => $value) {
                if (mb_strlen((string) $value) > self::STRING_MAX_LENGTH) {
                    $truncated = true;
                    $value = mb_substr((string) $value, 0, self::STRING_MAX_LENGTH);
                } else {
                    $truncated = false;
                    $value = (string) $value;
                }

                $data[$key][] = [
                    'columnName' => $columnName,
                    'value' => $value,
                    'isTruncated' => $truncated,
                ];
            }
        }

        return $data;
    }
}
