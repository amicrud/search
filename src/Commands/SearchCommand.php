<?php

namespace Search\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class SearchCommand extends Command
{
    protected $signature = 'amicrud:search {keyword} {--vendor} {--storage} {--replace=}';

    protected $description = 'Search for a method, class, or variable in the project, optionally replacing it';

    public function handle()
    {
        $keyword = $this->argument('keyword');
        $replaceWith = $this->option('replace');

        $vendorOption = $this->option('vendor');
        $storageOption = $this->option('storage');

        $grepCommand = ['grep', '-rn', '--include=*.php'];

        if (!$vendorOption) {
            $grepCommand[] = '--exclude-dir=vendor';
        }

        if (!$storageOption) {
            $grepCommand[] = '--exclude-dir=storage';
        }

        $grepCommand[] = $keyword;
        $grepCommand[] = base_path();

        try {
            $process = new Process($grepCommand);
            $process->mustRun();

            $output = $process->getOutput();

            if (empty($output)) {
                $this->info('No matches found for the keyword.');
                return;
            }

            $this->formatOutput($output);

            if ($replaceWith !== null) {
                $this->performReplacement($output, $keyword, $replaceWith);
            }

        } catch (ProcessFailedException $exception) {
            $this->error('Keyword not found');
        } catch (\Exception $exception) {
            $this->error('An unexpected error occurred: ' . $exception->getMessage());
        }
    }

    protected function formatOutput($output)
    {
        $lines = explode(PHP_EOL, $output);
        foreach ($lines as $line) {
            if (empty($line)) continue;

            preg_match('/^(.*?):(\d+):(.*)$/', $line, $matches);
            if (count($matches) == 4) {
                [$fullMatch, $filePath, $lineNumber, $preview] = $matches;

                $this->line("File: <fg=green>{$filePath}</>");
                $this->line("Line: <fg=yellow>{$lineNumber}</>");
                $this->line("Preview: <fg=blue>{$preview}</>");
                $this->line(str_repeat('-', 80));
            }
        }
    }

    protected function performReplacement($output, $keyword, $replaceWith)
    {
        $lines = explode(PHP_EOL, trim($output));

        foreach ($lines as $line) {
            if (empty($line)) continue;

            preg_match('/^(.*?):(\d+):/', $line, $matches);
            if (count($matches) == 3) {
                $filePath = $matches[1];

                if (file_exists($filePath)) {
                    $fileContents = file_get_contents($filePath);
                    $updatedContents = str_replace($keyword, $replaceWith, $fileContents);

                    file_put_contents($filePath, $updatedContents);
                    $this->info("Replaced '{$keyword}' with '{$replaceWith}' in file: {$filePath}");
                }
            }
        }
    }
}
