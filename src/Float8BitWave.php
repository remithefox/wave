<?php

namespace RemiTheFox\Wave;

class Float8BitWave extends AbstractFloatWave
{
    private const BIT_PER_SAMPLE = 8;
    private const MIN_VALUE = 0x00;
    private const MAX_VALUE = 0xff;
    private const MULTIPLIER = 0x80;

    /**
     * @inheritdoc
     */
    static protected function validateBitPerSample(Wave $wave): bool
    {
        return self::BIT_PER_SAMPLE == $wave->getBitsPerSample();
    }

    /**
     * @inheritdoc
     */
    protected function floatToInt(float $floatValue): int
    {
        return max(self::MIN_VALUE, min(self::MAX_VALUE, (int)(($floatValue + 1) * self::MULTIPLIER)));
    }

    /**
     * @inheritdoc
     */
    protected function intToFloat(int $intValue): float
    {
        return ($intValue / self::MULTIPLIER) - 1;
    }
}