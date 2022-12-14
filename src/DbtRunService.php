<?php

declare(strict_types=1);

namespace NoCodeDbtTransformation;

use Keboola\Component\UserException;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class DbtRunService
{

    private string $projectPath;

    public function __construct(string $projectPath)
    {
        $this->projectPath = $projectPath;
    }

    /**
     * @param array<string> $modelNames
     * @throws \Keboola\Component\UserException
     */
    public function run(array $modelNames = [], string $target = 'kbc_prod'): string
    {
        try {
            $command = $this->prepareCommand($this->getSelectParameter($modelNames), $target);
            $process = new Process($command, $this->projectPath, getenv());
            $process->mustRun();
            return $process->getOutput();
        } catch (ProcessFailedException $e) {
            throw new UserException($this->getErrorMessagesFromOutput($e->getProcess()->getOutput()));
        }
    }

    /**
     * @param array<int, string> $modelNames
     * @return array<int, string>
     */
    protected function getSelectParameter(array $modelNames): array
    {
        $selectParameter = [];
        if (!empty($modelNames)) {
            $selectParameter = ['--select', ...$modelNames];
        }

        return $selectParameter;
    }

    /**
     * @param array<int, string> $selectParameter
     * @return array<int, string>
     */
    protected function prepareCommand(array $selectParameter, string $target): array
    {
        return [
            'dbt',
            '--log-format',
            'json',
            '--warn-error',
            'run',
            '-t',
            $target,
            ...$selectParameter,
            '--profiles-dir',
            $this->projectPath,
        ];
    }

    protected function getErrorMessagesFromOutput(string $output): string
    {
        preg_match_all('~\{(?:[^{}]|(?R))*}~', $output, $messages);

        $errors = [];
        foreach (reset($messages) as $messageJson) {
            $message = json_decode($messageJson, true);
            if ($message === null) {
                return $output;
            }
            if (is_array($message) && $message['level'] === 'error') {
                $errors[] = $message['msg'];
            }
        }

        return implode("\r\n", $errors);
    }
}
