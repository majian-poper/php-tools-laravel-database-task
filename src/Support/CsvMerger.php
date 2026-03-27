<?php

namespace PHPTools\LaravelDatabaseTask\Support;

class CsvMerger
{
    /**
     * Merge multiple CSV files into one.
     *
     * @param string[] $filePaths
     * @param string $destinationPath
     * @return void
     */
    public static function merge(array $filePaths, string $destinationPath): void
    {
        $output = new \SplFileObject($destinationPath, 'w');

        foreach ($filePaths as $index => $filePath) {
            $input = new \SplFileObject($filePath, 'r');

            if ($index > 0) {
                $input->fgetcsv();
            }

            while (! $input->eof()) {
                $line = $input->fgets();

                $line && $output->fwrite($line);
            }
        }
    }
}
