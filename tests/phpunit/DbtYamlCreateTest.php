<?php

declare(strict_types=1);

namespace NoCodeDbtTransformation\Tests;

use NoCodeDbtTransformation\DbtYamlCreateService\DbtProfilesYamlCreateService;
use NoCodeDbtTransformation\DbtYamlCreateService\DbtSourceYamlCreateService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class DbtYamlCreateTest extends TestCase
{
    protected string $dataDir = __DIR__ . '/../../data';
    protected string $providerDataDir = __DIR__ . '/data';

    public function tearDown(): void
    {
        $fs = new Filesystem();
        $finder = new Finder();
        $fs->remove($finder->in($this->dataDir));
    }

    public function testCreateProfileYaml(): void
    {
        $service = new DbtProfilesYamlCreateService();
        $service->dumpYaml($this->dataDir, []);

        self::assertFileEquals(
            sprintf('%s/expectedProfiles.yml', $this->providerDataDir),
            sprintf('%s/profiles.yml', $this->dataDir),
        );
    }

    public function testCreateSourceYaml(): void
    {
        $service = new DbtSourceYamlCreateService();

        $tablesData = [
            'bucket-1' => ['tables' => [['name' => 'table1', 'primaryKey' => ['id']]]],
            'bucket-2' => ['tables' => [
                ['name' => 'table2', 'primaryKey' => ['vatId']],
                ['name' => 'tableWithCompoundPrimaryKey', 'primaryKey' => ['id', 'vatId']],
            ]],
            'linked-bucket' => ['tables' => [
                ['name' => 'linkedTable', 'primaryKey' => []],
            ], 'projectId' => '9090'],
        ];

        $service->dumpYaml(
            $this->dataDir,
            $tablesData,
        );

        foreach ($tablesData as $bucket => $tables) {
            self::assertFileEquals(
                sprintf('%s/models/_sources/%s.yml', $this->providerDataDir, $bucket),
                sprintf('%s/models/_sources/%s.yml', $this->dataDir, $bucket),
            );
        }
    }
}
