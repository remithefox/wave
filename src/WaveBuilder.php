<?php

namespace RemiTheFox\Wave;

use RemiTheFox\Wave\Exception\FloatDecoratorNotFound;

class WaveBuilder
{
    private int $numberOfChannels = 2;
    private int $sampleRate = 44100;
    private int $bitsPerSample = 16;
    private bool $floatDecorator = false;

    /**
     * @return int
     */
    public function getNumberOfChannels(): int
    {
        return $this->numberOfChannels;
    }

    /**
     * @param int $numberOfChannels
     * @return self
     */
    public function setNumberOfChannels(int $numberOfChannels): self
    {
        $this->numberOfChannels = $numberOfChannels;
        return $this;
    }

    /**
     * @return int
     */
    public function getSampleRate(): int
    {
        return $this->sampleRate;
    }

    /**
     * @param int $sampleRate
     * @return self
     */
    public function setSampleRate(int $sampleRate): self
    {
        $this->sampleRate = $sampleRate;
        return $this;
    }

    /**
     * @return int
     */
    public function getBitsPerSample(): int
    {
        return $this->bitsPerSample;
    }

    /**
     * @param int $bitsPerSample
     * @return self
     */
    public function setBitsPerSample(int $bitsPerSample): self
    {
        $this->bitsPerSample = $bitsPerSample;
        return $this;
    }

    /**
     * @return bool
     */
    public function isFloatDecorator(): bool
    {
        return $this->floatDecorator;
    }

    /**
     * @param bool $floatDecorator
     * @return self
     */
    public function setFloatDecorator(bool $floatDecorator): self
    {
        $this->floatDecorator = $floatDecorator;
        return $this;
    }

    /**
     * @param string $filePath
     * @return WaveInterface
     * @throws Exception\CannotCreateFileException
     * @throws Exception\FileIsNotWritableException
     * @throws Exception\NotApplicableBitPerSampleException
     * @throws FloatDecoratorNotFound
     */
    public function create(string $filePath): WaveInterface
    {
        $wave = Wave::createNew(
            $filePath,
            $this->numberOfChannels,
            $this->sampleRate,
            $this->bitsPerSample
        );
        if ($this->floatDecorator) {
            return AbstractFloatWave::decorate($wave);
        }
        return $wave;
    }
}