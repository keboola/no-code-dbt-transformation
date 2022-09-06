<?php

declare(strict_types=1);

namespace NoCodeDbtTransformation\Tests;

use Generator;
use NoCodeDbtTransformation\ActionConfigDefinition;
use NoCodeDbtTransformation\Config;
use NoCodeDbtTransformation\ConfigDefinition;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

class ConfigTest extends TestCase
{
    /**
     * @param array<string, mixed> $configData
     * @dataProvider validConfigsData
     */
    public function testValidConfig(array $configData): void
    {
        $config = new Config($configData, new ConfigDefinition());
        $this->assertEquals($configData, $config->getData());
    }

    /**
     * @param array<string, mixed> $configData
     * @dataProvider validActionConfigsData
     */
    public function testValidActionConfig(array $configData): void
    {
        $config = new Config($configData, new ActionConfigDefinition());
        $this->assertEquals($configData, $config->getData());
    }

    /**
     * @param array<string, mixed> $configData
     * @dataProvider invalidConfigsData
     */
    public function testInvalidConfigs(array $configData, string $expectedError): void
    {
        try {
            new Config($configData, new ConfigDefinition());
            $this->fail('Validation should produce error');
        } catch (InvalidConfigurationException $e) {
            $this->assertStringContainsString($expectedError, $e->getMessage());
        }
    }

    /**
     * @return Generator<string, array<string, mixed>>
     */
    public function validConfigsData(): Generator
    {
        yield 'config with table and bucket specified' => [
            'configData' => [
                'parameters' => [
                    'tableName' => 'tableName',
                    'bucketId' => 'bucketId',
                    'models' => ['SELECT * FROM table1;'],
                ],
            ],
        ];

        yield 'config with one model' => [
            'configData' => [
                'parameters' => [
                    'models' => ['SELECT * FROM table1;'],
                ],
            ],
        ];

        yield 'config with two models' => [
            'configData' => [
                'parameters' => [
                    'models' => ['SELECT * FROM table1;', 'SELECT 1 FROM table2;'],
                ],
                'authorization' => [
                    'host' => 'kebooladev.snowflake.com',
                    'warehouse' => 'KEBOOLA_PROD',
                    'database' => 'KEBOOLA_PROD',
                    'schema' => 'KEBOOLA_PROD',
                    'user' => 'KEBOOLA_PROD_1111',
                    '#password' => 'password',
                ],
            ],
        ];
    }

    /**
     * @return Generator<string, array<string, mixed>>
     */
    public function validActionConfigsData(): Generator
    {
        yield 'config with table and bucket specified' => [
            'configData' => [
                'parameters' => [
                    'tableName' => 'tableName',
                    'bucketId' => 'bucketId',
                    'models' => ['SELECT * FROM table1;'],
                ],
                'authorization' => [
                    'host' => 'kebooladev.snowflake.com',
                    'warehouse' => 'KEBOOLA_PROD',
                    'database' => 'KEBOOLA_PROD',
                    'schema' => 'KEBOOLA_PROD',
                    'user' => 'KEBOOLA_PROD_1111',
                    '#password' => 'password',
                ],
            ],
        ];

        yield 'config with one model' => [
            'configData' => [
                'parameters' => [
                    'models' => ['SELECT * FROM table1;'],
                ],
                'authorization' => [
                    'host' => 'kebooladev.snowflake.com',
                    'warehouse' => 'KEBOOLA_PROD',
                    'database' => 'KEBOOLA_PROD',
                    'schema' => 'KEBOOLA_PROD',
                    'user' => 'KEBOOLA_PROD_1111',
                    '#password' => 'password',
                ],
            ],
        ];

        yield 'config with two models' => [
            'configData' => [
                'parameters' => [
                    'models' => ['SELECT * FROM table1;', 'SELECT 1 FROM table2;'],
                ],
                'authorization' => [
                    'host' => 'kebooladev.snowflake.com',
                    'warehouse' => 'KEBOOLA_PROD',
                    'database' => 'KEBOOLA_PROD',
                    'schema' => 'KEBOOLA_PROD',
                    'user' => 'KEBOOLA_PROD_1111',
                    '#password' => 'password',
                ],
            ],
        ];
    }

    /**
     * @return Generator<string, array<string, mixed>>
     */
    public function invalidConfigsData(): Generator
    {
        yield 'empty config' => [
            'configData' => [],
            'expectedError' => 'The child config "parameters" under "root" must be configured.',
        ];

        yield 'empty parameters' => [
            'configData' => [
                'parameters' => [],
            ],
            'expectedError' => 'The child config "models" under "root.parameters" must be configured.',
        ];

        yield 'empty models node' => [
            'configData' => [
                'parameters' => [
                    'models' => [],
                ],
            ],
            'expectedError' => 'The path "root.parameters.models" should have at least 1 element(s) defined.',
        ];

        yield 'empty model' => [
            'configData' => [
                'parameters' => [
                    'models' => [''],
                ],
            ],
            'expectedError' => 'The path "root.parameters.models.0" cannot contain an empty value, but got "".',
        ];

        yield 'empty table name' => [
            'configData' => [
                'parameters' => [
                    'tableName' => '',
                    'models' => ['SELECT * FROM table1;', 'SELECT 1 FROM table2;'],
                ],
            ],
            'expectedError' => 'The path "root.parameters.tableName" cannot contain an empty value, but got "".',
        ];
    }
}
