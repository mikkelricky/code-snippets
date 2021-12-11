<?php

namespace MikkelRicky\CodeSnippets;

use PHPUnit\Framework\TestCase;

class SnippetsTest extends TestCase
{
    /**
     * @dataProvider snippetsProvider
     */
    public function testSnippets(string $inputFilename, string $expectedFilename): void
    {
        $snippets = new Snippets();
        $actual = $snippets->process($inputFilename);
        $this->assertStringEqualsFile($expectedFilename, $actual);
    }

    /**
     * @dataProvider idempotenceProvider
     */
    public function testIdempotence(string $inputFilename): void
    {
        $snippets = new Snippets();
        $actual = $snippets->process($inputFilename);
        $this->assertStringEqualsFile($inputFilename, $actual);
    }

    /**
     * @return array<int, string>[]
     */
    public function snippetsProvider(): iterable
    {
        $inputFilenames = glob(__DIR__ . '/assets/input/*.*');

        return false === $inputFilenames
            ? []
            : array_map(
                static fn ($filename) => [$filename, str_replace('/input/', '/expected/', $filename)],
                $inputFilenames
            );
    }

    /**
     * @return array<int, string>[]
     */
    public function idempotenceProvider(): array
    {
        $inputFilenames = glob(__DIR__ . '/assets/expected/*.*');
        return false === $inputFilenames
            ? []
            : array_map(
                static fn ($filename) => [$filename],
                $inputFilenames
            );
    }
}
