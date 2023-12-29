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

        // trying to restrict only single quote keyword
        // if (!empty($keyword) && 
        // (($keyword[0] === '"' && $keyword[strlen($keyword) - 1] === '"') ||
        //  ($keyword[0] === "'" && $keyword[strlen($keyword) - 1] === "'"))) {
        //     $this->error("Please avoid wrapping the string with quotes. Example: php artisan method:search $keyword");
        //     return;
        // }

        $vendorOption = $this->option('vendor');
        $storageOption = $this->option('storage');

        // Use grep to search for the keyword in PHP files excluding the vendor folder
        if ($vendorOption||$storageOption) {
            if (!$vendorOption) {
                $process = new Process(['grep', '-r', '--include=*.php','--exclude-dir=vendor', $keyword, base_path()]);
            }
            if (!$storageOption) {
                $process = new Process(['grep', '-r', '--include=*.php','--exclude-dir=storage', $keyword, base_path()]);
            }
            if ($vendorOption&&$storageOption) {
                $process = new Process(['grep', '-r', '--include=*.php', $keyword, base_path()]);
            }
           
        }
        else{
            $process = new Process(['grep', '-r', '--include=*.php', '--exclude-dir=vendor', '--exclude-dir=storage', $keyword, base_path()]);
        }
    
        
        try {
            $process->mustRun();

            // Get the output and display it
            $output = $process->getOutput();
            $this->info($output);
        } catch (ProcessFailedException $exception) {
            $this->error('Search failed. ' . $exception->getMessage());
        }
    }
}
