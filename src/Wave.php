<?php

namespace RemiTheFox\Wave;

use RemiTheFox\Wave\Exception\CannotCreateFileException;
use RemiTheFox\Wave\Exception\CannotOpenFileException;
use RemiTheFox\Wave\Exception\FileIsNotReadableException;
use RemiTheFox\Wave\Exception\FileIsNotWritableException;
use RemiTheFox\Wave\Exception\FileNotFoundException;
use RemiTheFox\Wave\Exception\FormatNotSupportedException;
use RemiTheFox\Wave\Exception\HeaderCorruptedException;
use RemiTheFox\Wave\Exception\HeaderDataInconsistentException;

class Wave implements WaveInterface
{
    private const WF_RIFF_MARKER_POS = 0x00;
    private const WF_FILE_SIZE_POS = 0x04;
    private const WF_WAVE_MARKER_POS = 0x08;
    private const WF_FMT_MARKER_POS = 0x0c;
    private const WF_LENGTH_OF_FORMAT_DATA_POS = 0x10;
    private const WF_TYPE_OF_FORMAT_POS = 0x14;
    private const WF_NUMBER_OF_CHANNELS_POS = 0x16;
    private const WF_SAMPLE_RATE_POS = 0x18;
    private const WF_BYTES_PER_SECOND_POS = 0x1c;
    private const WF_BYTES_PER_SAMPLE_ON_ALL_CHANNELS_POS = 0x20;
    private const WF_BITS_PER_SAMPLE_POS = 0x22;
    private const WF_DATA_MARKER_POS = 0x24;
    private const WF_DATA_SIZE_POS = 0x28;
    private const WF_DATA_START_POS = 0x2c;

    private const WF_RIFF_MARKER_LEN = 0x04;
    private const WF_FILE_SIZE_LEN = 0x04;
    private const WF_WAVE_MARKER_LEN = 0x04;
    private const WF_FMT_MARKER_LEN = 0x04;
    private const WF_LENGTH_OF_FORMAT_DATA_LEN = 0x04;
    private const WF_TYPE_OF_FORMAT_LEN = 0x02;
    private const WF_NUMBER_OF_CHANNELS_LEN = 0x02;
    private const WF_SAMPLE_RATE_LEN = 0x04;
    private const WF_BYTES_PER_SECOND_LEN = 0x04;
    private const WF_BYTES_PER_SAMPLE_ON_ALL_CHANNELS_LEN = 0x02;
    private const WF_BITS_PER_SAMPLE_LEN = 0x02;
    private const WF_DATA_MARKER_LEN = 0x04;
    private const WF_DATA_SIZE_LEN = 0x04;

    private const WF_HEADER_SIZE = 0x2c;

    /** @var resource */
    private $fileHandle;

    /** @var bool */
    private bool $writable;

    /** @var int */
    private int $numberOfChannels;

    /** @var int */
    private int $sampleRate;

    /** @var int */
    private int $bytesPerSecond;

    /** @var int */
    private int $bytesPerSampleAllChannels;

    /** @var int */
    private int $bitsPerSample;

    /** @var int */
    private int $bytesPerSample;

    /** @var bool */
    private bool $closed = false;

    private function __construct()
    {
    }

    public function __destruct()
    {
        $this->close();
    }

    /**
     * @return WaveBuilder
     */
    static public function builder(): WaveBuilder
    {
        return new WaveBuilder();
    }

    /**
     * @param string $filePath
     * @return static
     * @throws CannotOpenFileException
     * @throws FileIsNotReadableException
     * @throws FileNotFoundException
     * @throws FormatNotSupportedException
     * @throws HeaderCorruptedException
     * @throws HeaderDataInconsistentException
     */
    static public function createFromFile(string $filePath): self
    {
        if (!file_exists($filePath)) {
            throw new FileNotFoundException('File not found');
        }

        if (!is_readable($filePath)) {
            throw new FileIsNotReadableException('File is not readable');
        }

        $writable = is_writable($filePath);

        $fh = fopen($filePath, $writable ? 'r+' : 'r');
        if (false === $fh) {
            throw new CannotOpenFileException('Cannot open file');
        }

        $wave = new self();
        $wave->fileHandle = $fh;
        $wave->writable = $writable;
        $wave->loadHeader();
        return $wave;
    }

    /**
     * @param string $filePath
     * @param int $numberOfChannels
     * @param int $sampleRate
     * @param int $bitsPerSample
     * @return static
     * @throws CannotCreateFileException
     * @throws FileIsNotWritableException
     */
    static public function createNew(
        string $filePath,
        int    $numberOfChannels = 2,
        int    $sampleRate = 44100,
        int    $bitsPerSample = 16
    ): self
    {
        $fh = fopen($filePath, 'w+');
        if (false === $fh) {
            throw new CannotCreateFileException('Cannot create file');
        }

        $wave = new self();
        $wave->fileHandle = $fh;
        $wave->writable = true;
        $wave->numberOfChannels = $numberOfChannels;
        $wave->sampleRate = $sampleRate;
        $wave->bytesPerSampleAllChannels = ceil($bitsPerSample / 8) * $numberOfChannels;
        $wave->bytesPerSecond = $sampleRate * $wave->bytesPerSampleAllChannels;
        $wave->bitsPerSample = $bitsPerSample;
        $wave->bytesPerSample = ceil($bitsPerSample / 8);
        $wave->saveHeader();

        return $wave;
    }

    /**
     * @inheritdoc
     */
    public function isWritable(): bool
    {
        return $this->writable;
    }

    /**
     * @inheritdoc
     */
    public function getNumberOfChannels(): int
    {
        return $this->numberOfChannels;
    }

    /**
     * @inheritdoc
     */
    public function getSampleRate(): int
    {
        return $this->sampleRate;
    }

    /**
     * @inheritdoc
     */
    public function getBytesPerSecond(): int
    {
        return $this->bytesPerSecond;
    }

    /**
     * @inheritdoc
     */
    public function getBytesPerSampleAllChannels(): int
    {
        return $this->bytesPerSampleAllChannels;
    }

    /**
     * @inheritdoc
     */
    public function getBitsPerSample(): int
    {
        return $this->bitsPerSample;
    }

    /**
     * @inheritdoc
     */
    public function getBytesPerSample(): int
    {
        return $this->bytesPerSample;
    }

    /**
     * @inheritdoc
     */
    public function getNumberOfSamples(): int
    {
        return (int)((fstat($this->fileHandle)['size'] - self::WF_HEADER_SIZE) / $this->bytesPerSampleAllChannels);
    }

    /**
     * @inheritdoc
     */
    public function getPosition(): int
    {
        return (int)((ftell($this->fileHandle) - self::WF_HEADER_SIZE) / $this->bytesPerSampleAllChannels);
    }

    /**
     * @inheritdoc
     */
    public function seek(int $position): self
    {
        fseek($this->fileHandle, self::WF_HEADER_SIZE + ($this->bytesPerSampleAllChannels * $position));
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function time(float $minutes, float $seconds, int $samples = 0): int
    {
        return (int)(($minutes * 60 + $seconds) * $this->sampleRate + $samples);
    }

    /**
     * @inheritdoc
     * @return int[]
     */
    public function read(): array
    {
        $sample = [];
        $rawSample = fread($this->fileHandle, $this->bytesPerSampleAllChannels);
        for ($i = 0; $i < $this->numberOfChannels; $i++) {
            $sample[] = self::bytesToInt(substr($rawSample, $i * $this->bytesPerSample, $this->bytesPerSample));
        }
        return $sample;
    }

    /**
     * @inheritdoc
     * @param int[] $sample
     */
    public function write(array $sample): self
    {
        $rawSample = '';
        for ($i = 0; $i < $this->numberOfChannels; $i++) {
            $rawSample .= self::intToBytes($this->bytesPerSample, (int)($sample[$i] ?? 0));
        }
        fwrite($this->fileHandle, $rawSample);
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function save(): self
    {
        $pos = ftell($this->fileHandle);
        $this->updateHeaderSizes();
        fflush($this->fileHandle);
        fseek($this->fileHandle, $pos);
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function close(): void
    {
        if ($this->closed) {
            return;
        }
        $this->updateHeaderSizes();
        fclose($this->fileHandle);
        $this->closed = true;
    }

    /* \ArrayAccess methods */

    /**
     * @inheritdoc
     */
    public function offsetExists(mixed $offset): bool
    {
        if ($offset != (int)$offset) {
            return false;
        }
        return $offset < $this->getNumberOfSamples();
    }

    /**
     * @inheritdoc
     * @return int[]
     */
    public function offsetGet(mixed $offset): array
    {
        $pos = ftell($this->fileHandle);
        fseek($this->fileHandle, self::WF_HEADER_SIZE + ($this->bytesPerSampleAllChannels * $offset));
        $sample = $this->read();
        fseek($this->fileHandle, $pos);
        return $sample;
    }

    /**
     * @inheritdoc
     * @param int[] $value
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $pos = ftell($this->fileHandle);
        fseek($this->fileHandle, $offset ? self::WF_HEADER_SIZE + ($this->bytesPerSampleAllChannels * $offset) : fstat($this->fileHandle)['size']);
        $this->write((array)$value);
        fseek($this->fileHandle, $pos);
    }

    /**
     * @inheritdoc
     */
    public function offsetUnset(mixed $offset): void
    {
        $pos = ftell($this->fileHandle);
        fseek($this->fileHandle, self::WF_HEADER_SIZE + ($this->bytesPerSampleAllChannels * $offset));
        $this->write([]);
        fseek($this->fileHandle, $pos);
    }

    /* \Iterator methods */
    /**
     * @inheritdoc
     * @return int[]
     */
    public function current(): array
    {
        $pos = ftell($this->fileHandle);
        $sample = $this->read();
        fseek($this->fileHandle, $pos);
        return $sample;
    }

    /**
     * @inheritdoc
     * @return void
     */
    public function next(): void
    {
        $pos = ftell($this->fileHandle);
        fseek($this->fileHandle, $pos + $this->bytesPerSampleAllChannels);
    }

    /**
     * @inheritdoc
     */
    public function key(): int
    {
        return $this->getPosition();
    }

    /**
     * @inheritdoc
     */
    public function valid(): bool
    {
        return ftell($this->fileHandle) < fstat($this->fileHandle)['size'];
    }

    /**
     * @inheritdoc
     */
    public function rewind(): void
    {
        $this->seek(0);
    }

    /* Generators */
    /**
     * @inheritdoc
     * @return \Generator<int, int[]>
     */
    public function toGenerator(int $from = 0): \Generator
    {
        $this->seek($from);
        $size = $this->getNumberOfSamples();
        while (!feof($this->fileHandle)) {
            $pos = $this->getPosition();
            if ($pos >= $size) {
                break;
            }
            yield $pos => $this->read();
        }
    }

    /**
     * @inheritdoc
     * @param iterable<int[]> $generator
     */
    public function fromGenerator(iterable $generator): self
    {
        foreach ($generator as $value) {
            $this->write((array)$value);
        }
        return $this;
    }

    /**
     * Updates file size and data size in file header
     * @return void
     */
    private function updateHeaderSizes()
    {
        $filesize = fstat($this->fileHandle)['size'];
        fseek($this->fileHandle, self::WF_FILE_SIZE_POS);
        fwrite($this->fileHandle, self::intToBytes(self::WF_FILE_SIZE_LEN, $filesize));
        fseek($this->fileHandle, self::WF_DATA_SIZE_POS);
        fwrite($this->fileHandle, self::intToBytes(self::WF_DATA_SIZE_LEN, $filesize - self::WF_HEADER_SIZE));
    }

    /**
     * Loads values from file header
     * @return void
     * @throws FormatNotSupportedException
     * @throws HeaderCorruptedException
     * @throws HeaderDataInconsistentException
     */
    private function loadHeader(): void
    {
        fseek($this->fileHandle, 0);
        $header = fread($this->fileHandle, self::WF_HEADER_SIZE);

        /** Checking header markers */
        if (
            substr($header, self::WF_RIFF_MARKER_POS, self::WF_RIFF_MARKER_LEN) !== 'RIFF'
            || substr($header, self::WF_WAVE_MARKER_POS, self::WF_WAVE_MARKER_LEN) !== 'WAVE'
            || substr($header, self::WF_FMT_MARKER_POS, self::WF_FMT_MARKER_LEN) !== 'fmt '
            || substr($header, self::WF_LENGTH_OF_FORMAT_DATA_POS, self::WF_LENGTH_OF_FORMAT_DATA_LEN) !== "\x10\x00\x00\x00"
        ) {
            throw new HeaderCorruptedException('Wave file header is corrupted');
        }

        /** Checking format */
        if (substr($header, self::WF_TYPE_OF_FORMAT_POS, self::WF_TYPE_OF_FORMAT_LEN) !== "\x01\x00") {
            throw new FormatNotSupportedException('Wave file format other than PCM are not supported yet');
        }

        /** Checking data marker */
        if (substr($header, self::WF_DATA_MARKER_POS, self::WF_DATA_MARKER_LEN) !== 'data') {
            throw new HeaderCorruptedException('Wave file header is corrupted');
        }

        /** Reading header data */
        $fileSize = self::bytesToInt(substr($header, self::WF_FILE_SIZE_POS, self::WF_FILE_SIZE_LEN));
        $this->numberOfChannels = self::bytesToInt(substr($header, self::WF_NUMBER_OF_CHANNELS_POS, self::WF_NUMBER_OF_CHANNELS_LEN));
        $this->sampleRate = self::bytesToInt(substr($header, self::WF_SAMPLE_RATE_POS, self::WF_SAMPLE_RATE_LEN));
        $this->bytesPerSecond = self::bytesToInt(substr($header, self::WF_BYTES_PER_SECOND_POS, self::WF_BYTES_PER_SECOND_LEN));
        $this->bytesPerSampleAllChannels = self::bytesToInt(substr($header, self::WF_BYTES_PER_SAMPLE_ON_ALL_CHANNELS_POS, self::WF_BYTES_PER_SAMPLE_ON_ALL_CHANNELS_LEN));
        $this->bitsPerSample = self::bytesToInt(substr($header, self::WF_BITS_PER_SAMPLE_POS, self::WF_BITS_PER_SAMPLE_LEN));
        $dataSize = self::bytesToInt(substr($header, self::WF_DATA_SIZE_POS, self::WF_DATA_SIZE_LEN));

        /** Checking header data consistence */
        if (
            $this->sampleRate * $this->bytesPerSampleAllChannels != $this->bytesPerSecond
            || ceil($this->bitsPerSample / 8) * $this->numberOfChannels != $this->bytesPerSampleAllChannels
            || $fileSize != $dataSize + self::WF_HEADER_SIZE
            || $fileSize != fstat($this->fileHandle)['size']
            || $dataSize % $this->bytesPerSampleAllChannels > 0
        ) {
            throw new HeaderDataInconsistentException('Wave file header data is inconsistent');
        }

        $this->bytesPerSample = ceil($this->bitsPerSample / 8);
        fseek($this->fileHandle, self::WF_DATA_START_POS);
    }

    /**
     * Saves header
     * @return void
     * @throws FileIsNotWritableException
     */
    private function saveHeader(): void
    {
        if (!$this->writable) {
            throw new FileIsNotWritableException('Wave file is not writable');
        }

        $fileSize = max(fstat($this->fileHandle)['size'], self::WF_HEADER_SIZE);
        $dataSize = $fileSize - self::WF_HEADER_SIZE;

        $header =
            'RIFF'
            . self::intToBytes(self::WF_FILE_SIZE_LEN, $fileSize)
            . "WAVEfmt \x10\x00\x00\x00\x01\x00"
            . self::intToBytes(self::WF_NUMBER_OF_CHANNELS_LEN, $this->numberOfChannels)
            . self::intToBytes(self::WF_SAMPLE_RATE_LEN, $this->sampleRate)
            . self::intToBytes(self::WF_BYTES_PER_SECOND_LEN, $this->bytesPerSecond)
            . self::intToBytes(self::WF_BYTES_PER_SAMPLE_ON_ALL_CHANNELS_LEN, $this->bytesPerSampleAllChannels)
            . self::intToBytes(self::WF_BITS_PER_SAMPLE_LEN, $this->bitsPerSample)
            . 'data'
            . self::intToBytes(self::WF_DATA_MARKER_LEN, $dataSize);

        fseek($this->fileHandle, 0);
        fwrite($this->fileHandle, $header);
        fseek($this->fileHandle, self::WF_DATA_START_POS);
    }

    /**
     * Translate data bytes to integer
     * @param string $data
     * @return int
     */
    private static function bytesToInt(string $data): int
    {
        $value = 0;
        for ($i = 0; $i < strlen($data); $i++) {
            $value |= ord(substr($data, $i, 1)) << ($i << 3);
        }
        return $value;
    }

    /**
     * Translate integer to data bytes
     * @param int $bytes
     * @param int $value
     * @return string
     */
    private static function intToBytes(int $bytes, int $value): string
    {
        $data = '';
        for ($i = 0; $i < $bytes; $i++) {
            $data .= chr($value & 0xff);
            $value >>= 8;
        }
        return $data;
    }
}