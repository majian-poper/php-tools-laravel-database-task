<?php

namespace PHPTools\LaravelDatabaseTask\Models;

use Spatie\MediaLibrary\MediaCollections\Models\Media;

class DatabaseTaskFile extends Media
{
    public function toTempFileObject(): \SplTempFileObject
    {
        return $this->writeTo(new \SplTempFileObject);
    }

    public function writeTo(\SplFileObject $file): \SplFileObject
    {
        if (! $file->isWritable()) {
            throw new \RuntimeException('The provided file is not writable.');
        }

        $resource = $this->stream();

        while (! \feof($resource)) {
            $file->fwrite(\fread($resource, 8192));
        }

        \fclose($resource);

        $file->rewind();

        return $file;
    }
}
