<?php

namespace Search\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class SearchCommand extends Command
{
    // The name and signature of the console command.
    protected $signature = 'amicrud:search {keyword} {--vendor} {--storage} {--replace=}';

    // The console command description.
    protected $description = 'Search for a method, class, or variable in the project, optionally replacing it';

    /**
     * Execute the console command.
     * 
     * This command executes a grep command in the base path of the laravel project 
     * searching for the keyword passed as argument. The command will also exclude 
     * vendor and storage directories if the corresponding options are given.
     * 
     * If the --replace option is given, the command will also replace the keyword with 
     * the given value in all the files found.
     *
     * @return void
     */
    
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

    /**
     * Formats and outputs the search results.
     *
     * This function processes the given raw output string from the search command,
     * extracting file paths, line numbers, and preview texts from each line.
     * It then formats and displays each result in a readable format with color
     * coding for better visibility.
     *
     * @param string $output The raw output string from the search command.
     */

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

    /**
     * Replaces occurrences of a keyword with a specified value in files listed in the output.
     *
     * This function parses the output from the search command to extract file paths,
     * and then replaces all instances of the keyword with the provided replacement text
     * within those files. It updates the files and logs a message indicating each successful
     * replacement.
     *
     * @param string $output The raw output string from the search command containing file paths.
     * @param string $keyword The keyword to be replaced in the files.
     * @param string $replaceWith The text to replace the keyword with.
     */

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
