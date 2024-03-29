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
     * @param array{
     *          parameters: array{
     *              tableName?: string,
     *              bucketId?: string,
     *              models: list<array{
     *                  code: string
     *              }>,
     *              authorization?: array{
     *                  host: string,
     *                  warehouse: string,
     *                  database: string,
     *                  schema: string,
     *                  user: string,
     *                  "#password": string,
     *                  region?: string
     *              }
     *          }
     *      } $configData
     * @dataProvider validConfigsData
     */
    public function testValidConfig(array $configData): void
    {
        $config = new Config($configData, new ConfigDefinition());
        unset($configData['parameters']['authorization']['region']); // ignored in config definition
        $this->assertEquals($configData, $config->getData());
    }

    /**
     * @param  array{
     *          parameters: array{
     *              tableName?: string,
     *              bucketId?: string,
     *              returnAllResults: bool,
     *              models: list<array{
     *                  code: string
     *              }>,
     *              authorization: array{
     *                  host: string,
     *                  warehouse: string,
     *                  database: string,
     *                  schema: string,
     *                  user: string,
     *                  "#password": string,
     *                  region?: string
     *              }
     *          }
     *     } $configData
     * @dataProvider validActionConfigsData
     */
    public function testValidActionConfig(array $configData): void
    {
        $config = new Config($configData, new ActionConfigDefinition());
        unset($configData['parameters']['authorization']['region']); // ignored in config definition
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
     * @return Generator<string, array{
     *     configData: array{
     *         parameters: array{
     *             tableName?: string,
     *             bucketId?: string,
     *             models: list<array{
     *                 code: string
     *             }>,
     *             authorization?: array{
     *                 host: string,
     *                 warehouse: string,
     *                 database: string,
     *                 schema: string,
     *                 user: string,
     *                 "#password": string,
     *                 region?: string
     *             }
     *         }
     *     }
     * }>
     */
    public function validConfigsData(): Generator
    {
        yield 'config with table and bucket specified' => [
            'configData' => [
                'parameters' => [
                    'tableName' => 'tableName',
                    'bucketId' => 'bucketId',
                    'models' => [
                            ['code' => 'SELECT * FROM table1;'],
                    ],
                ],
            ],
        ];

        yield 'config with one model' => [
            'configData' => [
                'parameters' => [
                    'models' => [
                        ['code' => 'SELECT * FROM table1;'],
                    ],
                ],
            ],
        ];

        yield 'config with two models' => [
            'configData' => [
                'parameters' => [
                    'models' => [
                        ['code' => 'SELECT * FROM table1;'],
                        ['code' => 'SELECT * FROM table2;'],
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
            ],
        ];

        yield 'config with region' => [
            'configData' => [
                'parameters' => [
                    'models' => [
                        ['code' => 'SELECT * FROM table1;'],
                        ['code' => 'SELECT * FROM table2;'],
                    ],
                    'authorization' => [
                        'host' => 'kebooladev.snowflake.com',
                        'warehouse' => 'KEBOOLA_PROD',
                        'region' => 'US',
                        'database' => 'KEBOOLA_PROD',
                        'schema' => 'KEBOOLA_PROD',
                        'user' => 'KEBOOLA_PROD_1111',
                        '#password' => 'password',
                    ],
                ],
            ],
        ];
    }

    /**
     * @return Generator<string, array{
     *     configData: array{
     *         parameters: array{
     *             tableName?: string,
     *             bucketId?: string,
     *             returnAllResults: bool,
     *             models: list<array{
     *                 code: string
     *             }>,
     *             authorization: array{
     *                 host: string,
     *                 warehouse: string,
     *                 database: string,
     *                 schema: string,
     *                 user: string,
     *                 "#password": string,
     *                 region?: string
     *             }
     *         }
     *     }
     * }>
     */
    public function validActionConfigsData(): Generator
    {
        yield 'config with table and bucket specified' => [
            'configData' => [
                'parameters' => [
                    'tableName' => 'tableName',
                    'bucketId' => 'bucketId',
                    'returnAllResults' => true,
                    'models' => [
                        ['code' => 'SELECT * FROM table1;'],
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
            ],
        ];

        yield 'config with one model' => [
            'configData' => [
                'parameters' => [
                    'models' => [
                        ['code' => 'SELECT * FROM table1;',],
                    ],
                    'returnAllResults' => true,
                    'authorization' => [
                        'host' => 'kebooladev.snowflake.com',
                        'warehouse' => 'KEBOOLA_PROD',
                        'database' => 'KEBOOLA_PROD',
                        'schema' => 'KEBOOLA_PROD',
                        'user' => 'KEBOOLA_PROD_1111',
                        '#password' => 'password',
                    ],
                ],
            ],
        ];

        yield 'config with two models' => [
            'configData' => [
                'parameters' => [
                    'returnAllResults' => false,
                    'models' => [
                        ['code' => 'SELECT * FROM table1;'],
                        ['code' => 'SELECT * FROM table2;'],
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
            ],
        ];

        yield 'config with region' => [
            'configData' => [
                'parameters' => [
                    'returnAllResults' => false,
                    'models' => [
                        ['code' => 'SELECT * FROM table1;'],
                        ['code' => 'SELECT * FROM table2;'],
                    ],
                    'authorization' => [
                        'host' => 'kebooladev.snowflake.com',
                        'warehouse' => 'KEBOOLA_PROD',
                        'region' => 'US',
                        'database' => 'KEBOOLA_PROD',
                        'schema' => 'KEBOOLA_PROD',
                        'user' => 'KEBOOLA_PROD_1111',
                        '#password' => 'password',
                    ],
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

        yield 'empty model code' => [
            'configData' => [
                'parameters' => [
                    'models' => [['code' => '']],
                ],
            ],
            'expectedError' => 'The path "root.parameters.models.0.code" cannot contain an empty value, but got "".',
        ];

        yield 'empty table name' => [
            'configData' => [
                'parameters' => [
                    'tableName' => '',
                    'models' => [['code' => 'SELECT * FROM table1;']],
                ],
            ],
            'expectedError' => 'The path "root.parameters.tableName" cannot contain an empty value, but got "".',
        ];
    }
}
