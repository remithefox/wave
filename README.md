# remithefox/wave

A PHP library that helps you create WAVE files

## Installation

### Composer

```bash
$ composer require remithefox/wave
```

## Usage

### Creating new wave file

You can create wave file by static method `Wave::createNew()`. e.g.:

```php
use RemiTheFox\Wave\AbstractFloatWave;
use RemiTheFox\Wave\Wave;

$wave = Wave::createNew(
    __DIR__ . '/sound.wav', // filename
    2,                      // number of channels 
    44100,                  // sample rate
    16                      // bits per sample
);

// Wave object can be also decorated with FloatDecorator
$floatWave = AbstractFloatWave::decorate($wave); 
```

More comfortable way to create new wave file is using builder. e.g.:

```php
use RemiTheFox\Wave\Wave;

$builder = Wave::builder()
    ->setNumberOfChannels(2)
    ->setSampleRate(44100)
    ->setBitsPerSample(16)
    ->setFloatDecorator(true);

$wave = $builder->create(__DIR__ . '/sound.wav');
```

builder setters:

| setters                 | default | significance                                                                                                       |
|:------------------------|--------:|:-------------------------------------------------------------------------------------------------------------------|
| `setNumberOfChannels()` |     `2` | number of channels                                                                                                 |
| `setSampleRate()`       | `44100` | number of samples per seconds<sup>1</sup>                                                                          |
| `setBitsPerSample()`    |    `16` | bits per sample (highly recommend to use 8 or 16)                                                                  |
| `setFloatDecorator()`   | `false` | use float decorator (wave object will be packed in decorator that calculate sample values to float in range -1..1) |

1. Sample rate must be at least 2 times grater than the highest frequency to avoid aliasing (Nyquist frequency).
   Most often sampling frequency is 44.1kHz (44 100Hz) and this sample rate is recommended for music. For human speech
   sampling frequency must be at least 8kHz (8 000Hz).

### Opening existing files

To open exiting file use method `Wave::createFromFile()`

```php
use RemiTheFox\Wave\AbstractFloatWave;
use RemiTheFox\Wave\Wave;

$wave = Wave::createFromFile(__DIR__ . 'existing-file.wav');

// Wave object can be also decorated with FloatDecorator
$floatWave = AbstractFloatWave::decorate($wave); 
```

### Float decorator

Whether you use the float decorator or not, you will get an object that implements the interface
`RemiTheFox\Wave\WaveInterface`. If you use decorator you can read and write float values from -1 to 1,
otherwise you can read and write integer values from 0 to 255 for 8-bit files or from 0 to 65535 for 16-bit files.

### Getters

| getter                           | type   | significance                                              |
|:---------------------------------|:-------|:----------------------------------------------------------|
| `isWritable()`                   | `bool` | returns true if file is writable                          |
| `getNumberOfChannels()`          | `int`  | returns number of channels                                |
| `getSampleRate()`                | `int`  | returns number of sample per second                       |
| `getBytesPerSecond()`            | `int`  | returns number of data bytes per second                   |
| `getBytesPerSampleAllChannels()` | `int`  | returns number of bytes per sample for all channels       |
| `getBitsPerSample()`             | `int`  | returns number of bits of sample value for single channel |
| `getBytesPerSample()`            | `int`  | returns number of bytes per sample for single channel     |
| `getNumberOfSamples()`           | `int`  | returns number of samples                                 |
| `getPosition()`                  | `int`  | returns number of current sample                          | 

### Navigation in file

#### Seeking

To seek use method `WaveInterface::seek()`

```php
// ...
/** @var \RemiTheFox\Wave\WaveInterface $wave */
$wave->seek(2137); // goes to sample number 2137
```

NOTICE: first sample has number 0.

#### Getting current position

to get current position use method `WaveInterface::getPosition()`

```php
// ...
/** @var \RemiTheFox\Wave\WaveInterface $wave */
$position = $wave->getPosition(); // returns current position
```

NOTICE: first sample has number 0.

#### Human friendly time

Position of some specified time depends on file sample rate, so if you want to go to sample on 1:30 you need to go to
sample number Fs*90. It isn't comfortable, so you can use method `WaveInterface::time()` e.g.:

```php
// ...
/** @var \RemiTheFox\Wave\WaveInterface $wave */
$wave->seek($wave->time(1, 30)); // goes to 1:30 regardless of sampling frequency
```

method takes 2 or 3 arguments:

| argument | type  | required | significance      |
|:---------|:------|:---------|:------------------|
| minutes  | float | YES      | number of minutes |
| seconds  | float | YES      | number of seconds |
| samples  | int   | NO       | offset in samples |

### Reading

To read use method `WaveInterface::read()` e.g.:

```php
// ...
/** @var \RemiTheFox\Wave\WaveInterface $wave */
$sample = $wave->read();
```

Method returns array of values of all channels in single sample and goes one sample forward.

### Writing

To write use method `WaveInterface::write()` e.g.:

```php
// ...
/** @var \RemiTheFox\Wave\WaveInterface $wave */
$sample = $wave->write([0,1]); // write one sample and go one sample forward
```

Method write values of all channels in single sample and goes one sample forward.
Values should be supplied as array.

### Array access and iterating

`\RemiTheFox\Wave\WaveInterface` extends `\ArrayAccess` and `\Iterator` so you can use it as array.

#### Array access

```php
// ...
/** @var \RemiTheFox\Wave\WaveInterface $wave */
$sample = $wave[420]; // reads sample number 420
$wave[420] = [0, 0];  // replaces sample number 420
$wave[] = [0, 0];     // adds sample at the end of file
```

#### Iterating

```php
// ...
/** @var \RemiTheFox\Wave\WaveInterface $wave */
foreach ($wave as $sampleNumber => $sampleValues) {
    // ...
}
```

### Generators

You can use method `WaveInterface::toGenerator()` to create generator which yields samples.
Method can be called with start sample number in argument or without arguments (starts from file begin). e.g.:

```php
// ...
/** @var \RemiTheFox\Wave\WaveInterface $wave */

$generator = $wave->toGenerator(21);
foreach ($generator as $sampleNumber => $sampleValues){
    // ...
}
```

You can also use some other generator (or any other iterable type) to write samples using method `WaveInterface::toGenerator()`. e.g.:

```php
// ...
function tone(int $sampleRate, float $frequency, int $length, float $volume = 1): \Generator
{
    for ($i = 0; $i < $length; $i++) {
        $sample = $volume * sin(2 * pi() * $frequency * $i / $sampleRate);
        yield [$sample];
    }
}

/** @var \RemiTheFox\Wave\WaveInterface $wave */

$sampleRate = $wave->getSampleRate();
$frequency = 1000; // 1kHz
$length = $wave->time(0, 5);

$generator = tone($sampleRate, $frequency, $length);
$wave->fromGenerator($generator);
```

### Exceptions

All exceptions are in namespace `\RemiTheFox\Wave\Exception` implements
`\RemiTheFox\Wave\Exception\WaveExceptionInterface`.

| exception                          | significance                                                                           |
|:-----------------------------------|:---------------------------------------------------------------------------------------|
| CannotCreateFileException          | Cannot create file. It can be caused for example by permissions or lack of disk space. |
| CannotOpenFileException            | Error during opening file.                                                             |
| FileIsNotReadableException         | Cannot open file. File is not readable.                                                |
| FileIsNotWritableException         | Cannot write to file. File is not writable.                                            |
| FileNotFoundException              | File not exists.                                                                       |
| FloatDecoratorNotFound             | Cannot decorate file other than 8-bit and 16-bit.                                      |
| FormatNotSupportedException        | Wave file format is other than PCM.                                                    |
| HeaderCorruptedException           | Wave file header is corrupted. Header markers not found.                               |
| HeaderDataInconsistentException    | Wave file header data is inconsistent.                                                 |
| NotApplicableBitPerSampleException | Tried to decorate Wave object using wrong decorator.                                   | 


long text? ASCII-fox:

```text
 /\-/\
(=^w^=)
 )   (
```