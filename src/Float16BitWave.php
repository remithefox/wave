<?php

namespace RemiTheFox\Wave;

class Float16BitWave extends AbstractFloatWave
{
    private const BIT_PER_SAMPLE = 16;
    private const MIN_POSITIVE_VALUE = 0x0000;
    private const MAX_POSITIVE_VALUE = 0x7fff;
    private const MIN_NEGATIVE_VALUE = 0x8000;
    private const MAX_NEGATIVE_VALUE = 0xffff;
    private const MULTIPLIER = 0x8000;

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
        if ($floatValue < 0) {
            $floatValue += 2;
            return max(self::MIN_NEGATIVE_VALUE, min(self::MAX_NEGATIVE_VALUE, (int)($floatValue * self::MULTIPLIER)));
        }
        return max(self::MIN_POSITIVE_VALUE, min(self::MAX_POSITIVE_VALUE, (int)($floatValue * self::MULTIPLIER)));
    }

    /**
     * @inheritdoc
     */
    protected function intToFloat(int $intValue): float
    {
        $floatValue = ($intValue / self::MULTIPLIER);
        return $floatValue >= 1 ? $floatValue - 2 : $floatValue;
    }

}