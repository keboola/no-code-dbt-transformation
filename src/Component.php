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
    private const MODEL_LAST = 'model_last';

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
     * }|array<int, array{
     *     'columns': array<int, string>,
     *     'rows': array<int, array<int, array{'columnName': string, 'value': string, 'isTruncated': bool}>>
     * }>
     * @throws \Keboola\SnowflakeDbAdapter\Exception\SnowflakeDbAdapterException|\Keboola\Component\UserException
     */
    public function preview(): array
    {
        $this->doTransformation(true);

        $config = $this->getConfig();
        $workspace = $config->getCredentials();
        if ($config->shouldReturnAllResults()) {
            $results = [];
            foreach ($config->getModels() as $key => $model) {
                $modelName = sprintf('model%d', $key + 1);
                $results[] = $this->getResult($workspace, $modelName);
            }
            $results[] = $this->getResult($workspace, self::MODEL_LAST);
        } else {
            return $this->getResult($workspace, self::MODEL_LAST);
        }

        return $results;
    }

    protected function createDbtYamlFiles(Config $config): void
    {
        $workspace = $config->getAuthorization() ? $config->getAuthorization()['workspace'] : $config->getCredentials();
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
        $action = $this->getRawConfig()['action'] ?? 'run';
        return $action === 'run' ? ConfigDefinition::class : ActionConfigDefinition::class;
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

        $models = $config->getModels();
        $models = $this->prependInputMappingModel($models);

        $modelsCount = count($models);
        foreach ($models as $key => $model) {
            $limit = $onlyPreview && $key === 0;
            $last = $modelsCount === $key + 1;
            $model = str_replace('%model%', sprintf('model%d', $key), $model);
            $this->filesystem->dumpFile(
                sprintf('%s/models/model%s.sql', $this->projectPath, $last ? '_last' : $key + 1),
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

    /**
     * @param array<int, string> $models
     * @return array<int, string>
     */
    protected function prependInputMappingModel(array $models): array
    {
        $config = $this->getConfig();
        if ($config->getTableName() && $config->getBucketId()) {
            array_unshift(
                $models,
                sprintf(
                    'SELECT {{ dbt_utils.star(from=source(\'%s\', \'%s\'), except=["_timestamp"]) }} ' .
                    'FROM {{ source(\'%s\', \'%s\') }}',
                    $config->getBucketId(),
                    $config->getTableName(),
                    $config->getBucketId(),
                    $config->getTableName()
                )
            );
        }

        return $models;
    }

    /**
     * @param array<string, string> $workspace
     * @return array{
     *     'columns': array<int, string>,
     *     'rows': array<int, array<int, array{'columnName': string, 'value': string, 'isTruncated': bool}>>
     * }
     * @throws \Keboola\SnowflakeDbAdapter\Exception\SnowflakeDbAdapterException
     */
    protected function getResult(array $workspace, string $modelName): array
    {
        $connection = new Connection($workspace);
        $columns = $connection->getTableColumns($workspace['schema'], $modelName);
        $rows = $connection->fetchAll(sprintf(
            'SELECT * FROM "%s"."%s";',
            $workspace['schema'],
            $modelName
        ));

        return ['columns' => $columns, 'rows' => $this->formatDataForPreview($rows)];
    }
}
