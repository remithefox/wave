<?php

namespace RemiTheFox\Wave;

interface WaveInterface extends \ArrayAccess, \Iterator
{
    /**
     * Checks if file is writable
     * @return bool
     */
    public function isWritable(): bool;

    /**
     * Returns number of channels
     * @return int
     */
    public function getNumberOfChannels(): int;

    /**
     * Returns sample rate
     * @return int
     */
    public function getSampleRate(): int;

    /**
     * Returns bytes per second
     * @return int
     */
    public function getBytesPerSecond(): int;

    /**
     * Returns bytes per sample for all channels
     * @return int
     */
    public function getBytesPerSampleAllChannels(): int;

    /**
     * Returns bits per sample
     * @return int
     */
    public function getBitsPerSample(): int;

    /**
     * Returns bytes per sample
     * @return int
     */
    public function getBytesPerSample(): int;

    /**
     * Returns number of samples
     * @return int
     */
    public function getNumberOfSamples(): int;

    /**
     * Returns current sample number
     * @return int
     */
    public function getPosition(): int;

    /**
     * Goes to supplied sample
     * @param int $position
     * @return self
     */
    public function seek(int $position): self;

    /**
     * Calculates sample number for specified time
     * @param float $minutes
     * @param float $seconds
     * @param int $samples
     * @return int
     */
    public function time(float $minutes, float $seconds, int $samples = 0): int;

    /**
     * Returns current sample and goes one sample forward
     * @return int[]|float[]
     */
    public function read(): array;

    /**
     * Write sample and goes one sample forward
     * @param int[]|float[] $sample
     * @return self
     */
    public function write(array $sample): self;

    /**
     * Saves file
     * @return self
     */
    public function save(): self;

    /**
     * Closes file
     * @return void
     */
    public function close(): void;

    /* \ArrayAccess methods */

    /**
     * @inheritdoc
     * @param int $offset
     */
    public function offsetExists(mixed $offset): bool;

    /**
     * @inheritdoc
     * @param int $offset
     * @return int[]|float[]
     */
    public function offsetGet(mixed $offset): array;

    /**
     * @inheritdoc
     * @param int $offset
     * @param int[]|float[] $value
     */
    public function offsetSet(mixed $offset, mixed $value): void;

    /**
     * @inheritdoc
     * @param int $offset
     */
    public function offsetUnset(mixed $offset): void;

    /* \Iterator methods */
    /**
     * @inheritdoc
     * @return int[]|float[]
     */
    public function current(): mixed;

    /**
     * @inheritdoc
     */
    public function next(): void;

    /**
     * @inheritdoc
     * @return int
     */
    public function key(): int;

    /**
     * @inheritdoc
     */
    public function valid(): bool;

    /**
     * @inheritdoc
     */
    public function rewind(): void;

    /* Generators */
    /**
     * Create generator which yields sample values
     * @param int $from
     * @return \Generator
     */
    public function toGenerator(int $from = 0): \Generator;

    /**
     * Write samples from generator, array or any iterable object
     * @param iterable<int[]|float[]> $generator
     * @return self
     */
    public function fromGenerator(iterable $generator): self;

}