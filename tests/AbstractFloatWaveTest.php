<?php

declare(strict_types=1);

namespace tests\RemiTheFox\Wave;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RemiTheFox\Wave\AbstractFloatWave;
use RemiTheFox\Wave\Float8BitWave;
use RemiTheFox\Wave\Wave;

abstract class AbstractFloatWaveTest extends TestCase
{
    /** common tests */
    final public function testGetNumberOfChannels(): void
    {
        $wave = $this->makeWaveMock();
        $floatWave = $this->makeFloatWaveObject($wave);

        $wave->method('getNumberOfChannels')
            ->willReturn(2);

        $this->assertSame(2, $floatWave->getNumberOfChannels());
    }

    final public function testGetSampleRate(): void
    {
        $wave = $this->makeWaveMock();
        $floatWave = $this->makeFloatWaveObject($wave);

        $wave->method('getSampleRate')
            ->willReturn(44100);

        $this->assertSame(44100, $floatWave->getSampleRate());
    }

    final public function testGetBytesPerSecond(): void
    {
        $wave = $this->makeWaveMock();
        $floatWave = $this->makeFloatWaveObject($wave);

        $wave->method('getBytesPerSecond')
            ->willReturn(88200);

        $this->assertSame(88200, $floatWave->getBytesPerSecond());
    }

    final public function testGetBytesPerSampleAllChannels(): void
    {
        $wave = $this->makeWaveMock();
        $floatWave = $this->makeFloatWaveObject($wave);

        $wave->method('getBytesPerSampleAllChannels')
            ->willReturn(2);

        $this->assertSame(2, $floatWave->getBytesPerSampleAllChannels());
    }

    final public function testGetBytesPerSample(): void
    {
        $wave = $this->makeWaveMock();
        $floatWave = $this->makeFloatWaveObject($wave);

        $wave->method('getBytesPerSample')
            ->willReturn(1);

        $this->assertSame(1, $floatWave->getBytesPerSample());
    }

    final public function testGetNumberOfSamples(): void
    {
        $wave = $this->makeWaveMock();
        $floatWave = $this->makeFloatWaveObject($wave);

        $wave->method('getNumberOfSamples')
            ->willReturn(128);

        $this->assertSame(128, $floatWave->getNumberOfSamples());
    }

    final public function testGetPosition(): void
    {
        $wave = $this->makeWaveMock();
        $floatWave = $this->makeFloatWaveObject($wave);

        $wave->method('getPosition')
            ->willReturn(10);

        $this->assertSame(10, $floatWave->getPosition());
    }

    final public function testSeek(): void
    {
        $wave = $this->makeWaveMock();
        $floatWave = $this->makeFloatWaveObject($wave);

        $wave->expects($this->once())
            ->method('seek')
            ->with(10);

        $floatWave->seek(10);
    }

    public function testTime(): void
    {
        $wave = $this->makeWaveMock();
        $floatWave = $this->makeFloatWaveObject($wave);

        $wave->expects($this->once())
            ->method('time')
            ->with(21, 37, 0)
            ->willReturn(57197700);

        $sampleNumber = $floatWave->time(21, 37);
        $this->assertEquals(57197700, $sampleNumber, 'Wrong number of sample');

        $wave->close();
    }

    public function testTimeWithSampleOffset(): void
    {
        $wave = $this->makeWaveMock();
        $floatWave = $this->makeFloatWaveObject($wave);

        $wave->expects($this->once())
            ->method('time')
            ->with(1, 30, 50)
            ->willReturn(3969050);

        $sampleNumber = $floatWave->time(1, 30, 50);
        $this->assertEquals(3969050, $sampleNumber, 'Wrong number of sample');

        $wave->close();
    }


    final public function testSave(): void
    {
        $wave = $this->makeWaveMock();
        $floatWave = $this->makeFloatWaveObject($wave);

        $wave->expects($this->once())
            ->method('save');

        $floatWave->save();
    }

    final public function testClose(): void
    {
        $wave = $this->makeWaveMock();
        $floatWave = $this->makeFloatWaveObject($wave);

        $wave->expects($this->once())
            ->method('close');

        $floatWave->close();
    }

    final public function testOffsetExistsForExistingOffset(): void
    {
        $wave = $this->makeWaveMock();
        $floatWave = $this->makeFloatWaveObject($wave);

        $wave->expects($this->once())
            ->method('offsetExists')
            ->with(12)
            ->willReturn(true);

        $this->assertTrue($floatWave->offsetExists(12), 'offsetExists(12) returned false for existing offset');
    }

    final public function testOffsetExistsForNonExistingOffset(): void
    {
        $wave = $this->makeWaveMock();
        $floatWave = $this->makeFloatWaveObject($wave);

        $wave->expects($this->once())
            ->method('offsetExists')
            ->with(16)
            ->willReturn(false);

        $this->assertFalse($floatWave->offsetExists(16), 'offsetExists(16) returned true for non-existing offset');
    }

    final public function testOffsetUnset(): void
    {
        $wave = $this->makeWaveMock();
        $floatWave = $this->makeFloatWaveObject($wave);

        $wave->expects($this->once())
            ->method('offsetUnset')
            ->with(2137);

        $floatWave->offsetUnset(2137);
    }

    final public function testNext(): void
    {
        $wave = $this->makeWaveMock();
        $floatWave = $this->makeFloatWaveObject($wave);

        $wave->expects($this->once())
            ->method('next');

        $floatWave->next();
    }

    final public function testKey(): void
    {
        $wave = $this->makeWaveMock();
        $floatWave = $this->makeFloatWaveObject($wave);

        $wave->method('key')
            ->willReturn(621);

        $this->assertEquals(621, $floatWave->key(), 'method key return 621');
    }

    final public function testValidWhenTrue(): void
    {
        $wave = $this->makeWaveMock();
        $floatWave = $this->makeFloatWaveObject($wave);

        $wave->method('valid')
            ->willReturn(true);

        $this->assertTrue($floatWave->valid(), 'method valid should return true for valid');
    }

    final public function testValidWhenFalse(): void
    {
        $wave = $this->makeWaveMock();
        $floatWave = $this->makeFloatWaveObject($wave);

        $wave->method('valid')
            ->willReturn(false);

        $this->assertFalse($floatWave->valid(), 'method valid should return true for valid');
    }

    final public function testRewind(): void
    {
        $wave = $this->makeWaveMock(8);
        $floatWave = new Float8BitWave($wave);

        $wave->expects($this->once())
            ->method('rewind');

        $floatWave->rewind();
    }

    /** Test required to implement in bitrate context */
    abstract public function testGetBitsPerSample();

    abstract public function testRead(): void;

    abstract public function testWrite(): void;

    abstract public function testWriteNegativeOne(): void;

    abstract public function testWritePositiveOne(): void;

    abstract public function testOffsetGet(): void;

    abstract public function testOffsetSet(): void;

    abstract public function testCurrent(): void;

    abstract public function testToGenerator(): void;

    abstract public function testFromGenerator(): void;

    /** Mock and testing object supplying methods */
    abstract protected function makeWaveMock(): Wave|MockObject;

    abstract protected function makeFloatWaveObject(Wave $wave): AbstractFloatWave;

    /**  */
    protected function exampleGenerator(iterable $samples): \Generator
    {
        foreach ($samples as $key => $sample) {
            yield $key => $sample;
        }
    }
}
