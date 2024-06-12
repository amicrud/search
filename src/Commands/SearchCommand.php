<?php

namespace Search\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class SearchCommand extends Command
{
    protected $signature = 'amicrud:search {keyword} {--vendor} {--storage}';

    protected $description = 'Search for a method, class, or variable in the project';

    public function handle()
    {
        $keyword = $this->argument('keyword');

        $vendorOption = $this->option('vendor');
        $storageOption = $this->option('storage');

        // Use grep to search for the keyword in PHP files excluding the vendor folder
        if ($vendorOption || $storageOption) {
            if (!$vendorOption) {
                $process = new Process(['grep', '-rn', '--include=*.php', '--exclude-dir=vendor', $keyword, base_path()]);
            }
            if (!$storageOption) {
                $process = new Process(['grep', '-rn', '--include=*.php', '--exclude-dir=storage', $keyword, base_path()]);
            }
            if ($vendorOption && $storageOption) {
                $process = new Process(['grep', '-rn', '--include=*.php', $keyword, base_path()]);
            }
        } else {
            $process = new Process(['grep', '-rn', '--include=*.php', '--exclude-dir=vendor', '--exclude-dir=storage', $keyword, base_path()]);
        }

        try {
            $process->mustRun();

            // Get the output and display it
            $output = $process->getOutput();
            if (empty($output)) {
                $this->info('No matches found for the keyword.');
            } else {
                return $this->formatOutput($output);
                // $this->info($output);
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
            if (empty($line)) {
                continue;
            }

            preg_match('/^(.*?):(\d+):(.*)$/', $line, $matches);
            if (count($matches) == 4) {
                $filePath = $matches[1];
                $lineNumber = $matches[2];
                $preview = trim($matches[3]);

                $this->line(str_repeat('-', 80));
                $this->line(str_repeat('-', 80));
                $this->line("File: <fg=green>{$filePath}</>");
                $this->line("Line: <fg=yellow>{$lineNumber}</>");
                $this->line("Preview: <fg=blue>{$preview}</>");
                $this->line(str_repeat('-', 80));
                $this->line(str_repeat('-', 80));
            }
        }
    }
}
