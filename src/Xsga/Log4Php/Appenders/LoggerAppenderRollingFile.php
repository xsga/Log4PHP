<?php

declare(strict_types=1);

namespace Xsga\Log4Php\Appenders;

use Xsga\Log4Php\LoggerException;

final class LoggerAppenderRollingFile extends LoggerAppenderFile
{
    private const int COMPRESS_CHUNK_SIZE = 102400;

    protected int $maxFileSize = 10485760;
    protected int $maxBackupIndex = 1;
    protected bool $compress = false;

    public function __construct(string $name = '')
    {
        parent::__construct($name);
    }

    private function rollOver(): void
    {
        if ($this->maxBackupIndex > 0) {
            $file = $this->file . '.' . $this->maxBackupIndex;

            if (file_exists($file) && !unlink($file)) {
                throw new LoggerException("log4php: Unable to delete oldest backup file from \"$file\".");
            }

            $this->renameArchievedLogs($this->file);
            $this->moveToBackup($this->file);
        }

        if ($this->fp === null) {
            throw new LoggerException('log4php: File pointer is not valid.');
        }

        if (!is_resource($this->fp)) {
            throw new LoggerException('log4php: File pointer is not a valid resource.');
        }

        ftruncate($this->fp, 0);
        rewind($this->fp);
    }

    private function moveToBackup(string $source): void
    {
        if ($this->compress) {
            $target = $source . '.1.gz';
            $this->compressFile($source, $target);
            return;
        }

        $target = $source . '.1';
        if (!copy($source, $target)) {
            throw new LoggerException("log4php: Unable to copy file to backup: $target.");
        }
    }

    private function compressFile(string $source, string $target): void
    {
        $target = "compress.zlib://$target";

        $fin = fopen($source, 'rb');
        if (!$fin) {
            throw new LoggerException("log4php: Unable to open file for reading: $source.");
        }

        $fout = fopen($target, 'wb');
        if (!$fout) {
            throw new LoggerException("log4php: Unable to open file for writing: $target.");
        }

        while (!feof($fin)) {
            $chunk = fread($fin, self::COMPRESS_CHUNK_SIZE);
            if ($chunk === false) {
                throw new LoggerException('log4php: Failed reading from file for compression.');
            }
            if (fwrite($fout, $chunk) === false) {
                throw new LoggerException('log4php: Failed writing to compressed file.');
            }
        }

        fclose($fin);
        fclose($fout);
    }

    private function renameArchievedLogs(string $fileName): void
    {
        for ($i = ($this->maxBackupIndex - 1); $i >= 1; $i--) {
            $source = $fileName . '.' . $i;
            if ($this->compress) {
                $source .= '.gz';
            }

            if (file_exists($source)) {
                $target = $fileName . '.' . ($i + 1);
                if ($this->compress) {
                    $target .= '.gz';
                }
                if (!rename($source, $target)) {
                    throw new LoggerException("log4php: Unable to rename file to backup: $target.");
                }
            }
        }
    }

    private function getFileSize(): int
    {
        clearstatcache(true, $this->file);

        $fileLocation = realpath($this->file);

        if ($fileLocation === false) {
            return 0;
        }

        $fileSize = filesize($fileLocation);
        if ($fileSize === false) {
            return 0;
        }

        return $fileSize;
    }

    protected function write(?string $string): void
    {
        if ($this->fp === null && !$this->openFile()) {
            return;
        }

        /** @var resource $fp */
        $fp = $this->fp;
        if (flock($fp, LOCK_EX)) {
            if (fwrite($fp, $string === null ? '' : $string) === false) {
                $this->warn('Failed writing to file. Closing appender.');
                $this->closed = true;
            }

            if ($this->getFileSize() > $this->maxFileSize) {
                try {
                    $this->rollOver();
                } catch (LoggerException $ex) {
                    $this->warn('Rollover failed: ' . $ex->getMessage() . '. Closing appender.');
                    $this->closed = true;
                }
            }

            flock($fp, LOCK_UN);

            return;
        }

        $this->warn('Failed locking file for writing. Closing appender.');
        $this->closed = true;
    }

    public function activateOptions(): void
    {
        parent::activateOptions();

        if ($this->compress && !extension_loaded('zlib')) {
            $this->warn("The 'zlib' extension is required for file compression. Disabling compression.");
            $this->compress = false;
        }
    }

    public function setMaxBackupIndex(int|string $maxBackupIndex): void
    {
        $this->setPositiveInteger('maxBackupIndex', $maxBackupIndex);
    }

    public function getMaxBackupIndex(): int
    {
        return $this->maxBackupIndex;
    }

    public function setMaxFileSize(string $maxFileSize): void
    {
        $this->setFileSize('maxFileSize', $maxFileSize);
    }

    public function getMaxFileSize(): int
    {
        return $this->maxFileSize;
    }

    public function setCompress(bool $compress): void
    {
        $this->setBoolean('compress', $compress);
    }

    public function getCompress(): bool
    {
        return $this->compress;
    }
}
