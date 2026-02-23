<?php

namespace PHPTools\LaravelDatabaseTask\Models;

use Spatie\MediaLibrary\MediaCollections\Models\Media;

class DatabaseTaskFile extends Media
{
    public function getFileObject(): \SplFileObject
    {
        try {
            $bytes = \file_put_contents($tmp = \tempnam(\sys_get_temp_dir(), config('app.name') . '-'), $this->stream());

            if ($bytes === false) {
                throw new \RuntimeException('Cannot write temp file.');
            }
        } catch (\Throwable $e) {
            throw new \RuntimeException($e->getMessage());
        }

        return new \SplFileObject($bytes, 'r');
    }
}
