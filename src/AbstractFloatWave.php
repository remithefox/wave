<?php

namespace RemiTheFox\Wave;

use RemiTheFox\Wave\Exception\FloatDecoratorNotFound;
use RemiTheFox\Wave\Exception\NotApplicableBitPerSampleException;

abstract class AbstractFloatWave implements WaveInterface
{
    /** @var Wave */
    private Wave $wave;

    /**
     * @param Wave $wave
     * @throws NotApplicableBitPerSampleException
     */
    public function __construct(Wave $wave)
    {
        if (!static::validateBitPerSample($wave)) {
            throw new NotApplicableBitPerSampleException('Wave bit per sample is not compatible with class');
        }
        $this->wave = $wave;
    }

    /**
     * @inheritdoc
     */
    public function isWritable(): bool
    {
        return $this->wave->isWritable();
    }

    /**
     * @inheritdoc
     */
    public function getNumberOfChannels(): int
    {
        return $this->wave->getNumberOfChannels();
    }

    /**
     * @inheritdoc
     */
    public function getSampleRate(): int
    {
        return $this->wave->getSampleRate();
    }

    /**
     * @inheritdoc
     */
    public function getBytesPerSecond(): int
    {
        return $this->wave->getBytesPerSecond();
    }

    /**
     * @inheritdoc
     */
    public function getBytesPerSampleAllChannels(): int
    {
        return $this->wave->getBytesPerSampleAllChannels();
    }

    /**
     * @inheritdoc
     */
    public function getBitsPerSample(): int
    {
        return $this->wave->getBitsPerSample();
    }

    /**
     * @inheritdoc
     */
    public function getBytesPerSample(): int
    {
        return $this->wave->getBytesPerSample();
    }

    /**
     * @inheritdoc
     */
    public function getNumberOfSamples(): int
    {
        return $this->wave->getNumberOfSamples();
    }

    /**
     * @inheritdoc
     */
    public function getPosition(): int
    {
        return $this->wave->getPosition();
    }

    /**
     * @inheritdoc
     */
    public function seek(int $position): self
    {
        $this->wave->seek($position);
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function time(float $minutes, float $seconds, int $samples = 0): int
    {
        return $this->wave->time($minutes, $seconds, $samples);
    }

    /**
     * @inheritdoc
     * @return float[]
     */
    public function read(): array
    {
        return $this->intArrayToFloatArray($this->wave->read());
    }

    /**
     * @inheritdoc
     * @param float[] $sample
     */
    public function write(array $sample): self
    {
        $this->wave->write($this->floatArrayToIntArray($sample));
        return $this;
    }

    /**
     * @inheritdoc
     * @return self
     */
    public function save(): self
    {
        $this->wave->save();
        return $this;
    }

    /**
     * @inheritdoc
     * @return void
     */
    public function close(): void
    {
        $this->wave->close();
    }

    /**
     * @inheritdoc
     * @param int $offset
     * @return bool
     */
    public function offsetExists(mixed $offset): bool
    {
        return $this->wave->offsetExists($offset);
    }

    /**
     * @inheritdoc
     * @return float[]
     */
    public function offsetGet(mixed $offset): array
    {
        return $this->intArrayToFloatArray($this->wave->offsetGet($offset));
    }

    /**
     * @inheritdoc
     * @param float[] $value
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->wave->offsetSet($offset, $this->floatArrayToIntArray((array)$value));
    }

    /**
     * @inheritdoc
     */
    public function offsetUnset(mixed $offset): void
    {
        $this->wave->offsetUnset($offset);
    }

    /**
     * @inheritdoc
     * @return float[]
     */
    public function current(): array
    {
        return $this->intArrayToFloatArray($this->wave->current());
    }

    /**
     * @inheritdoc
     */
    public function next(): void
    {
        $this->wave->next();
    }

    /**
     * @inheritdoc
     */
    public function key(): int
    {
        return $this->wave->key();
    }

    /**
     * @inheritdoc
     */
    public function valid(): bool
    {
        return $this->wave->valid();
    }

    /**
     * @inheritdoc
     */
    public function rewind(): void
    {
        $this->wave->rewind();
    }

    /**
     * @inheritdoc
     * @param int $from
     * @return \Generator<int, float[]>
     */
    public function toGenerator(int $from = 0): \Generator
    {
        foreach ($this->wave->toGenerator($from) as $key => $intValues) {
            yield $key => $this->intArrayToFloatArray($intValues);
        }
    }

    /**
     * @inheritdoc
     */
    public function fromGenerator(iterable $generator): self
    {
        foreach ($generator as $floatValues) {
            $this->wave->write($this->floatArrayToIntArray((array)$floatValues));
        }
        return $this;
    }

    /**
     * Calculates float array to int array
     * @param float[] $floatValues
     * @return int[]
     */
    protected function floatArrayToIntArray(array $floatValues): array
    {
        $intValues = [];
        foreach ($floatValues as $floatValue) {
            $intValues[] = $this->floatToInt($floatValue);
        }
        return $intValues;
    }

    /**
     * Calculates int array to float array
     * @param int[] $intValues
     * @return float[]
     */
    function intArrayToFloatArray(array $intValues): array
    {
        $floatValues = [];
        foreach ($intValues as $intValue) {
            $floatValues[] = $this->intToFloat($intValue);
        }
        return $floatValues;
    }

    /**
     * Checks if bit per sample is applicable for decorator
     * @param Wave $wave
     * @return bool
     */
    static abstract protected function validateBitPerSample(Wave $wave): bool;

    /**
     * Calculates float to int
     * @param float $floatValue
     * @return int
     */
    abstract protected function floatToInt(float $floatValue): int;

    /**
     * Calculates int to float
     * @param int $intValue
     * @return float
     */
    abstract protected function intToFloat(int $intValue): float;

    /**
     * Decorates Wave object
     * @param Wave $wave
     * @return self
     * @throws FloatDecoratorNotFound
     * @throws NotApplicableBitPerSampleException
     */
    static public function decorate(Wave $wave): self
    {
        return match ($wave->getBitsPerSample()) {
            8 => new Float8BitWave($wave),
            16 => new Float16BitWave($wave),
            default => throw new FloatDecoratorNotFound('Float decorator for chosen bit per sample not exists')
        };
    }
}
