<?php

namespace PHPTools\LaravelDatabaseTask\Models;

use Spatie\MediaLibrary\MediaCollections\Models\Media;

class DatabaseTaskFile extends Media
{
    public function toTempFileObject(): \SplTempFileObject
    {
        $tempFile = new \SplTempFileObject;

        $resource = $this->stream();

        while (! \feof($resource)) {
            $tempFile->fwrite(\fread($resource, 8192));
        }

        \fclose($resource);

        $tempFile->rewind();

        return $tempFile;
    }
}
