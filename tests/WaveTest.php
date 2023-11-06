<?php

declare(strict_types=1);

namespace tests\RemiTheFox\Wave;

use PHPUnit\Framework\TestCase;
use RemiTheFox\Wave\Exception\HeaderDataInconsistentException;
use RemiTheFox\Wave\Wave;

final class WaveTest extends TestCase
{
    private string $tempFile;

    protected function setUp(): void
    {
        $this->tempFile = tempnam(sys_get_temp_dir(), 'wav');
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempFile)) {
            unlink($this->tempFile);
        }
    }

    public function testSimpleCreateEmptyFile()
    {
        $wave = Wave::createNew($this->tempFile, 1, 44100, 8);
        $wave->close();
        $actualWaveContent = file_get_contents($this->tempFile);
        $expectedWaveContent = $this->waveHeaderPattern(
            44, //header size
            1,
            44100,
            44100,
            1,
            8,
            0 //no data
        );
        $this->assertEquals($expectedWaveContent, $actualWaveContent, 'Generated wave file content is different that expected');
    }

    public function testOpenEmptyFile()
    {
        $waveContent = $this->waveHeaderPattern(
            44, //header size
            1,
            44100,
            44100,
            1,
            8,
            0 //no data
        );
        file_put_contents($this->tempFile, $waveContent);
        $wave = Wave::createFromFile($this->tempFile);
        $this->assertWaveParameters(
            $wave,
            1,
            44100,
            44100,
            1,
            8,
            0
        );
        $wave->close();
    }

    public function testCalculationHeaderValues()
    {
        $wave = Wave::createNew($this->tempFile, 2, 22050, 16);
        $wave->close();
        $actualWaveContent = file_get_contents($this->tempFile);
        $expectedWaveContent = $this->waveHeaderPattern(
            44,
            2,
            22050,
            88200, // 2 channels x 2 bytes x 22050 samples per second
            4, // 2 channels x 2 bytes
            16,
            0 //no data
        );
        $this->assertEquals($expectedWaveContent, $actualWaveContent, 'Generated wave file content is different that expected');
    }

    public function testOpenFileWithWrongBytesPerSecond()
    {
        $waveContent = $this->waveHeaderPattern(
            44, //header size
            2,
            44100,
            44100, // Wrong bytes per second
            2,
            8,
            0 //no data
        );
        file_put_contents($this->tempFile, $waveContent);
        $this->expectException(HeaderDataInconsistentException::class);
        Wave::createFromFile($this->tempFile);
    }

    public function testOpenFileWithWrongBytesPerSampleAllChannels()
    {
        $waveContent = $this->waveHeaderPattern(
            44, //header size
            2,
            44100,
            88200,
            4, // Wrong bytes per sample all channels
            8,
            0 //no data
        );
        file_put_contents($this->tempFile, $waveContent);
        $this->expectException(HeaderDataInconsistentException::class);
        Wave::createFromFile($this->tempFile);
    }

    public function testOpenWithWrongDataSize()
    {
        $waveContent = $this->waveHeaderPattern(
            48, // header size + wrong data size
            2,
            44100,
            88200,
            2,
            8,
            4 // wrong data size (correct is 0)
        );
        file_put_contents($this->tempFile, $waveContent);
        $this->expectException(HeaderDataInconsistentException::class);
        Wave::createFromFile($this->tempFile);
    }

    public function testOpenWithWrongDataSizeAndActualFileSize()
    {
        $waveContent = $this->waveHeaderPattern(
            44, // header size
            2,
            44100,
            88200,
            2,
            8,
            4 // wrong data size (correct is 0)
        );
        file_put_contents($this->tempFile, $waveContent);
        $this->expectException(HeaderDataInconsistentException::class);
        Wave::createFromFile($this->tempFile);
    }

    public function testOpenFileWithWrongFileSize()
    {
        $waveContent = $this->waveHeaderPattern(
                50, // wrong file size (correct is 48)
                2,
                44100,
                88200,
                2,
                8,
                4 // correct data size
            )
            . "\x00\x00\x00\x00";
        file_put_contents($this->tempFile, $waveContent);
        $this->expectException(HeaderDataInconsistentException::class);
        Wave::createFromFile($this->tempFile);
    }

    public function testOpenFileWithDataSizeWhichIsNotDivisibleByBytesPerSampleAllChannels()
    {
        $waveContent = $this->waveHeaderPattern(
                50, // header size + wrong data size
                2,
                44100,
                176400,
                4,
                16,
                6 // correct data size
            )
            . "\x00\x00\x00\x00\x00\x00";
        file_put_contents($this->tempFile, $waveContent);
        $this->expectException(HeaderDataInconsistentException::class);
        Wave::createFromFile($this->tempFile);
    }

    public function testReadingSamplesFromFile()
    {
        $exampleValues = [
            [1, 2],
            [3, 4],
            [5, 6],
        ];
        $waveContent = $this->waveHeaderPattern(
                56, //44 (header size) + (data size)
                2,
                44100,
                176400,
                4,
                16,
                12 //no data
            )
            . $this->generate16bitWaveData(2, $exampleValues);
        file_put_contents($this->tempFile, $waveContent);
        $wave = Wave::createFromFile($this->tempFile);
        foreach ($exampleValues as $key => $expectedValue) {
            $actualValue = $wave->read();
            $this->assertEquals($expectedValue, $actualValue, 'Wrong sample read: ' . $key);
        }
        $wave->close();
    }

    public function testReadingSamplesFromFileViaArrayAccess()
    {
        $exampleValues = [
            [1, 2],
            [3, 4],
            [5, 6],
        ];
        $waveContent = $this->waveHeaderPattern(
                56, //44 (header size) + (data size)
                2,
                44100,
                176400,
                4,
                16,
                12 //no data
            )
            . $this->generate16bitWaveData(2, $exampleValues);
        file_put_contents($this->tempFile, $waveContent);
        $wave = Wave::createFromFile($this->tempFile);
        foreach ($exampleValues as $key => $expectedValue) {
            $this->assertEquals($expectedValue, $wave[$key], 'Wrong sample read: ' . $key);
        }
        $wave->close();
    }

    public function testReadingViaGenerator()
    {
        $waveContent = $this->waveHeaderPattern(
                56, //44 (header size) + (data size)
                2,
                44100,
                176400,
                4,
                16,
                12 //no data
            ) . $this->generate16bitWaveData(2, [
                [1, 2],
                [3, 4],
                [5, 6],
            ]);
        file_put_contents($this->tempFile, $waveContent);
        $wave = Wave::createFromFile($this->tempFile);
        $generator = $wave->toGenerator(1);
        $expectedValues = [
            1 => [3, 4],
            2 => [5, 6],
        ];
        $keys = [];
        foreach ($generator as $key => $value) {
            $this->assertArrayHasKey($key, $expectedValues, 'Unexpected key: ' . $key);
            $this->assertEquals($expectedValues[$key], $value, 'Wrong sample values for key: ' . $key);
            $keys[] = $key;
        }
        $this->assertEquals(array_keys($expectedValues), $keys, 'Not all keys yielded by generator');
    }


    public function testSeek()
    {
        $exampleValues = [
            [1, 2],
            [3, 4],
            [5, 6],
        ];
        $waveContent = $this->waveHeaderPattern(
                56, //44 (header size) + (data size)
                2,
                44100,
                176400,
                4,
                16,
                12
            )
            . $this->generate16bitWaveData(2, $exampleValues);
        file_put_contents($this->tempFile, $waveContent);
        $wave = Wave::createFromFile($this->tempFile);

        $this->assertEquals(0, $wave->getPosition(), 'Wrong initial position');

        $wave->seek(2);
        $this->assertEquals(2, $wave->getPosition(), 'Wrong position after seek');

        $actualValue = $wave->read();
        $this->assertEquals($exampleValues[2], $actualValue, 'Wrong sample returned');

        $wave->close();
    }

    public function testTime()
    {
        $wave = Wave::createNew($this->tempFile, 2, 44100);

        $sampleNumber = $wave->time(21, 37);
        $this->assertEquals(57197700, $sampleNumber, 'Wrong number of sample');

        $wave->close();
    }

    public function testTimeWithSampleOffset()
    {
        $wave = Wave::createNew($this->tempFile, 2, 44100);

        $sampleNumber = $wave->time(1, 30, 50);
        $this->assertEquals(3969050, $sampleNumber, 'Wrong number of sample');

        $wave->close();
    }

    public function testWriteOnExistingFile()
    {
        $waveHeaderContent = $this->waveHeaderPattern(
            56, //44 (header size) + (data size)
            2,
            44100,
            176400,
            4,
            16,
            12 //no data
        );
        $waveContent = $waveHeaderContent . $this->generate16bitWaveData(2, [
                [1, 2],
                [3, 4],
                [5, 6],
            ]);
        file_put_contents($this->tempFile, $waveContent);
        $wave = Wave::createFromFile($this->tempFile);

        $wave->seek(1);
        $wave->write([11, 12]);

        $wave->seek(1);
        $readValue = $wave->read();
        $this->assertEquals([11, 12], $readValue, 'Wrong sample read');

        $wave->save();
        $actualNewWaveContent = file_get_contents($this->tempFile);
        $expectedNewWaveContent = $waveHeaderContent . $this->generate16bitWaveData(2, [
                [1, 2],
                [11, 12],
                [5, 6],
            ]);

        $this->assertEquals($expectedNewWaveContent, $actualNewWaveContent);

        $wave->close();
    }

    public function testWriteToNewFile()
    {
        $wave = Wave::builder()
            ->setBitsPerSample(16)
            ->setNumberOfChannels(2)
            ->setSampleRate(44100)
            ->create($this->tempFile);
        $wave->write([1, 2])
            ->write([3, 4])
            ->write([5, 6])
            ->close();

        $actualNewWaveContent = file_get_contents($this->tempFile);
        $expectedNewWaveContent = $this->waveHeaderPattern(
                56, //44 (header size) + (data size)
                2,
                44100,
                176400,
                4,
                16,
                12 //no data
            ) . $this->generate16bitWaveData(2, [
                [1, 2],
                [3, 4],
                [5, 6],
            ]);

        $this->assertEquals($expectedNewWaveContent, $actualNewWaveContent);
    }

    public function testWriteToNewFileViaArrayAccess()
    {
        $wave = Wave::builder()
            ->setBitsPerSample(16)
            ->setNumberOfChannels(2)
            ->setSampleRate(44100)
            ->create($this->tempFile);
        $wave[] = [1, 2];
        $wave[] = [3, 4];
        $wave[] = [5, 6];
        $wave->close();

        $actualNewWaveContent = file_get_contents($this->tempFile);
        $expectedNewWaveContent = $this->waveHeaderPattern(
                56, //44 (header size) + (data size)
                2,
                44100,
                176400,
                4,
                16,
                12 //no data
            ) . $this->generate16bitWaveData(2, [
                [1, 2],
                [3, 4],
                [5, 6],
            ]);

        $this->assertEquals($expectedNewWaveContent, $actualNewWaveContent);
    }

    public function testWriteToNewFileViaGenerator()
    {
        $exampleValues = [
            [1, 2],
            [3, 4],
            [5, 6],
        ];
        Wave::builder()
            ->setBitsPerSample(16)
            ->setNumberOfChannels(2)
            ->setSampleRate(44100)
            ->create($this->tempFile)
            ->fromGenerator($this->exampleGenerator($exampleValues))
            ->close();

        $actualNewWaveContent = file_get_contents($this->tempFile);
        $expectedNewWaveContent = $this->waveHeaderPattern(
                56, //44 (header size) + (data size)
                2,
                44100,
                176400,
                4,
                16,
                12 //no data
            ) . $this->generate16bitWaveData(2, $exampleValues);

        $this->assertEquals($expectedNewWaveContent, $actualNewWaveContent);
    }

    private function exampleGenerator(iterable $samples): \Generator
    {
        foreach ($samples as $key => $sample) {
            yield $key => $sample;
        }
    }

    private function assertWaveParameters(
        Wave $wave,
        int  $numberOfChannels,
        int  $sampleRate,
        int  $bytesPerSecond,
        int  $bytesPerSampleAllChannels,
        int  $bitsPerSample,
        int  $numberOfSamples
    ): void
    {
        $this->assertEquals($numberOfChannels, $wave->getNumberOfChannels(), 'Wrong number of channels');
        $this->assertEquals($sampleRate, $wave->getSampleRate(), 'Wrong sample rate');
        $this->assertEquals($bytesPerSecond, $wave->getBytesPerSecond(), 'Wrong bytes per second');
        $this->assertEquals($bytesPerSampleAllChannels, $wave->getBytesPerSampleAllChannels(), 'Wrong bytes per sample all channels');
        $this->assertEquals($bitsPerSample, $wave->getBitsPerSample(), 'Wrong bits per sample');
        $this->assertEquals($numberOfSamples, $wave->getNumberOfSamples(), 'Wrong number of samples');
    }

    private function waveHeaderPattern(
        int $fileSize,
        int $numberOfChannels,
        int $sampleRate,
        int $bytesPerSecond,
        int $bytesPerSampleAllChannels,
        int $bitsPerSample,
        int $dataSize,
    )
    {
        $fileSizeString = '';
        for ($i = 0; $i < 4; $i++) {
            $fileSizeString .= chr($fileSize & 0xff);
            $fileSize >>= 8;
        }

        $numberOfChannelsString = '';
        for ($i = 0; $i < 2; $i++) {
            $numberOfChannelsString .= chr($numberOfChannels & 0xff);
            $numberOfChannels >>= 8;
        }
        $sampleRateString = '';
        for ($i = 0; $i < 4; $i++) {
            $sampleRateString .= chr($sampleRate & 0xff);
            $sampleRate >>= 8;
        }

        $bytesPerSecondString = '';
        for ($i = 0; $i < 4; $i++) {
            $bytesPerSecondString .= chr($bytesPerSecond & 0xff);
            $bytesPerSecond >>= 8;
        }

        $bytesPerSampleAllChannelsString = '';
        for ($i = 0; $i < 2; $i++) {
            $bytesPerSampleAllChannelsString .= chr($bytesPerSampleAllChannels & 0xff);
            $bytesPerSampleAllChannels >>= 8;
        }

        $bitsPerSampleString = '';
        for ($i = 0; $i < 2; $i++) {
            $bitsPerSampleString .= chr($bitsPerSample & 0xff);
            $bitsPerSample >>= 8;
        }

        $dataSizeString = '';
        for ($i = 0; $i < 4; $i++) {
            $dataSizeString .= chr($dataSize & 0xff);
            $dataSize >>= 8;
        }

        return 'RIFF'
            . $fileSizeString
            . "WAVEfmt \x10\x00\x00\x00\x01\x00"
            . $numberOfChannelsString
            . $sampleRateString
            . $bytesPerSecondString
            . $bytesPerSampleAllChannelsString
            . $bitsPerSampleString
            . 'data'
            . $dataSizeString;
    }

    private function generate8bitWaveData(
        int   $numberOfChannels,
        array $data
    ): string
    {
        $waveData = '';
        foreach ($data as $samples) {
            $samples = (array)$samples;
            for ($i = 0; $i < $numberOfChannels; $i++) {
                $waveData .= chr($samples[$i] & 0xff);
            }
        }
        return $waveData;
    }

    private function generate16bitWaveData(
        int   $numberOfChannels,
        array $data
    ): string
    {
        $waveData = '';
        foreach ($data as $samples) {
            $samples = (array)$samples;
            for ($i = 0; $i < $numberOfChannels; $i++) {
                $waveData .= chr($samples[$i] & 0xff) . chr(($samples[$i] >> 8) & 0xff);
            }
        }
        return $waveData;
    }
}
