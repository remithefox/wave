<?php

declare(strict_types=1);

namespace tests\RemiTheFox\Wave;

require_once __DIR__ . '/AbstractFloatWaveTest.php';

use PHPUnit\Framework\MockObject\MockObject;
use RemiTheFox\Wave\AbstractFloatWave;
use RemiTheFox\Wave\Float8BitWave;
use RemiTheFox\Wave\Wave;

final class Float8BitWaveTest extends AbstractFloatWaveTest
{
    public function testGetBitsPerSample(): void
    {
        $wave = $this->makeWaveMock();
        $floatWave = $this->makeFloatWaveObject($wave);

        $this->assertSame(8, $floatWave->getBitsPerSample());
    }

    public function testRead(): void
    {
        $wave = $this->makeWaveMock();
        $floatWave = $this->makeFloatWaveObject($wave);

        $wave->method('read')
            ->willReturn([0x40]);

        $this->assertSame([-0.5], $floatWave->read());
    }

    public function testWrite(): void
    {
        $wave = $this->makeWaveMock();
        $floatWave = $this->makeFloatWaveObject($wave);

        $wave->expects($this->once())
            ->method('write')
            ->with([0x40]);

        $floatWave->write([-0.5]);
    }

    public function testWriteNegativeOne(): void
    {
        $wave = $this->makeWaveMock();
        $floatWave = $this->makeFloatWaveObject($wave);

        $wave->expects($this->once())
            ->method('write')
            ->with([0x00]);

        $floatWave->write([-1]);
    }

    public function testWritePositiveOne(): void
    {
        $wave = $this->makeWaveMock();
        $floatWave = $this->makeFloatWaveObject($wave);

        $wave->expects($this->once())
            ->method('write')
            ->with([0xff]);

        $floatWave->write([1]);
    }

    public function testOffsetGet(): void
    {
        $wave = $this->makeWaveMock();
        $floatWave = $this->makeFloatWaveObject($wave);

        $wave->expects($this->once())
            ->method('offsetGet')
            ->with(16)
            ->willReturn([0xc0, 0x80]);

        $sample = $floatWave->offsetGet(16);

        $this->assertEquals(0.5, $sample[0], 'Sample for channel 0 is different than 0.5');
        $this->assertEquals(0, $sample[1], 'Sample for channel 1 is different than 0');
    }

    public function testOffsetSet(): void
    {
        $wave = $this->makeWaveMock();
        $floatWave = $this->makeFloatWaveObject($wave);

        $wave->expects($this->once())
            ->method('offsetSet')
            ->with(32, [0xc0, 0x80]);

        $floatWave->offsetSet(32, [0.5, 0]);
    }

    public function testCurrent(): void
    {
        $wave = $this->makeWaveMock();
        $floatWave = $this->makeFloatWaveObject($wave);

        $wave->method('current')
            ->willReturn([0xc0, 0x40]);

        $sample = $floatWave->current();

        $this->assertEquals(0.5, $sample[0], 'sample on channel 0 is different than 0.5');
        $this->assertEquals(-0.5, $sample[1], 'sample on channel 1 is different than -0.5');
    }

    public function testToGenerator(): void
    {
        $wave = $this->makeWaveMock();
        $floatWave = $this->makeFloatWaveObject($wave);

        $exampleIntValues = [
            2 => [0, 0x40],
            3 => [0x80, 0xc0],
        ];

        $exampleFloatValues = [
            2 => [-1, -0.5],
            3 => [0, 0.5],
        ];

        $wave->method('toGenerator')
            ->with(2)
            ->willReturn($this->exampleGenerator($exampleIntValues));

        $generator = $floatWave->toGenerator(2);

        $keys = [];
        foreach ($generator as $key => $value) {
            $this->assertArrayHasKey($key, $exampleFloatValues, 'Unexpected key: ' . $key);
            $this->assertEquals($exampleFloatValues[$key], $value, 'Wrong sample values for key: ' . $key);
            $keys[] = $key;
        }
        $this->assertEquals(array_keys($exampleFloatValues), $keys, 'Not all keys yielded by generator');
    }

    public function testFromGenerator(): void
    {
        $wave = $this->makeWaveMock();
        $floatWave = $this->makeFloatWaveObject($wave);

        $expected = [
            [0x00, 0x40],
            [0x80, 0xc0]];
        $matcher = $this->exactly(2);

        $wave->expects($matcher)
            ->method('write')
            ->willReturnCallback(function ($value) use ($matcher, $expected, $wave) {
                $callNumber = $matcher->numberOfInvocations();
                $this->assertEquals($expected[$callNumber - 1][0], $value[0], 'wrong value writing on channel 0 in call: ' . $callNumber);
                $this->assertEquals($expected[$callNumber - 1][1], $value[1], 'wrong value writing on channel 1 in call: ' . $callNumber);
                return $wave;
            });

        $generator = $this->exampleGenerator($exampleFloatValues = [
            2 => [-1, -0.5],
            3 => [0, 0.5],
        ]);

        $floatWave->fromGenerator($generator);
    }

    protected function makeWaveMock(int $bitrate = 8): Wave|MockObject
    {
        $wave = $this->createMock(Wave::class);

        $wave->method('getBitsPerSample')
            ->willReturn($bitrate);

        return $wave;
    }

    protected function makeFloatWaveObject(Wave $wave): Float8BitWave
    {
        return new Float8BitWave($wave);
    }

}