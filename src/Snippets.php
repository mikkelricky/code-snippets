<?php

namespace MikkelRicky\CodeSnippets;

use DOMDocument;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\OptionsResolver\OptionsResolver;

class Snippets implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var array<int, mixed>
     */
    private array $options = [];

    public function __construct()
    {
        $this->setLogger(new NullLogger());
        $this->setOptions([]);
    }

    /**
     * @param array<int, mixed> $options
     */
    public function setOptions(array $options): void
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);

        $this->options = array_merge($this->options, $resolver->resolve($options));
    }

    public function process(string $filename): string
    {
        assert($this->logger instanceof LoggerInterface);
        $this->logger->info(sprintf('Processing file %s', $filename));
        $filepath = realpath($filename);
        if (false === $filepath || !file_exists($filepath)) {
            throw new \InvalidArgumentException(sprintf('File %s does not exist', $filename));
        }
        $content = file_get_contents($filepath);
        if (false === $content) {
            throw new \RuntimeException(sprintf('Cannot read file %s', $filename));
        }
        $parts = $this->getParts($content);
        foreach ($parts as &$part) {
            if ($this->isTextSnippetStart($part)) {
                $info = $this->parseTextSnippetStart($part);
                $part = $this->getSnippetContent($info, $filepath);
            }
        }

        return implode('', $parts);
    }

    /**
     * @param string $content
     * @return array<int, string>
     */
    private function getParts(string $content): array
    {
        $trimmedParts = [];

        $parts = preg_split('/^(.*(?:end-)?text-snippet.*)$/m', $content, -1, PREG_SPLIT_DELIM_CAPTURE);
        if (is_array($parts)) {
            // Throw away content between text-snippet and end-text-snippet and end-text-snippet
            for ($i = 0, $iMax = count($parts); $i < $iMax; $i++) {
                $trimmedParts[] = $parts[$i];
                if ($this->isTextSnippetStart($parts[$i])) {
                    while ($i < $iMax-1 && !$this->isTextSnippetEnd($parts[$i+1])) {
                        $i++;
                    }
                    if ($i < $iMax-1 && $this->isTextSnippetEnd($parts[$i+1])) {
                        $i++;
                    }
                }
            }
        }

        return $trimmedParts;
    }

    private function isTextSnippetStart(string $s): bool
    {
        return (bool)preg_match('/(^|[^[[:alpha:]-])text-snippet/', $s);
    }

    private function isTextSnippetEnd(string $s): bool
    {
        return (bool)preg_match('/end-text-snippet/', $s);
    }

    /**
     * @param string $source
     * @return array<string, mixed>
     */
    private function parseTextSnippetStart(string $source): array
    {
        $spec = [
            'source' => $source,
        ];

        $s = $source;
        // Find and remove any prefix.
        if (preg_match('/^(.+)text-snippet(.*)/', $s, $matches)) {
            $spec['prefix'] = $matches[1];
            $s = $matches[2];
        }

        // Find and remove any suffix.
        if (preg_match('/^(.+?)([^[:alnum:])"]+)$/', $s, $matches)) {
            $s = $matches[1];
            $spec['suffix'] = $matches[2];
        }

        // Remove optional parentheses around arguments.
        $s = trim($s, '()');

        // Parse parameters as HTML attributes.
        // @see https://html.spec.whatwg.org/multipage/common-microsyntaxes.html#boolean-attributes
        $doc = new DOMDocument();

        libxml_use_internal_errors(true);
        // A space is added before `/>` to help the HTML parser.
        $doc->loadHTML('<html ' . $s . ' />');

        if (!empty(libxml_get_errors())) {
            throw new \InvalidArgumentException(sprintf('Cannot parse %s', $source));
        }

        if (isset($doc->documentElement->attributes)) {
            foreach ($doc->documentElement->attributes as $attribute) {
                $spec['parameters'][$attribute->name] = $this->isBooleanParameter($attribute->name) ? true : $attribute->value;
            }
        }

        if (!isset($spec['parameters']['src'])) {
            throw new \InvalidArgumentException(sprintf('Parameter "src" is required in %s', $source));
        }

        return $spec;
    }

    /**
     * @var array|string[]
     */
    private array $booleanParameterNames = ['strip-leading-spaces'];

    private function isBooleanParameter(string $name): bool
    {
        return in_array($name, $this->booleanParameterNames, true);
    }

    /**
     * @param array<string, mixed|array> $spec
     * @param string $baseFilename
     * @return string
     */
    private function getSnippetContent(array $spec, string $baseFilename): string
    {
        assert(isset($spec['parameters']) && is_array($spec['parameters']));
        assert(isset($spec['parameters']['src']) && is_string($spec['parameters']['src']));
        $url = $spec['parameters']['src'];
        if (!filter_var($url, FILTER_VALIDATE_URL) && 0 !== strpos($url, '/')) {
            $url = 'file://'.dirname($baseFilename).'/'.$url;
        }
        $content = @file_get_contents($url);
        if (false === $content) {
            throw new \RuntimeException(sprintf('Cannot read file %s (%s)', $spec['parameters']['src'], $url));
        }

        $lines = explode(PHP_EOL, $content);
        $from = $spec['parameters']['from'] ?? $spec['parameters']['start'] ?? null;
        $to = $spec['parameters']['to'] ?? $spec['parameters']['end'] ?? null;

        // Compute 1-based $fromLine and $toLine.
        if (is_numeric($from)) {
            $fromLine = (int)$from;
        } else {
            $fromLine = 1;
            foreach ($lines as $index => $line) {
                if (false !== strpos($line, $from)) {
                    $fromLine = $index+1;
                    break;
                }
            }
        }

        // to relative to from.
        if (preg_match('/^\+(\d+)$/', $to, $matches)) {
            $toLine = $fromLine + (int)$matches[1];
        } elseif (is_numeric($to)) {
            $toLine = (int)$to;
        } else {
            $toLine = count($lines)+1;
            foreach ($lines as $index => $line) {
                if ($index >= $fromLine-1 && false !== strpos($line, $to)) {
                    $toLine = $index+1;
                    break;
                }
            }
        }

        $snippetLines = array_slice($lines, $fromLine-1, $toLine-$fromLine+1);
        if ($spec['parameters']['strip-leading-spaces'] ?? false) {
            $leadingSpace = null;
            foreach ($snippetLines as $line) {
                if (preg_match('/^\s+/', $line, $matches)) {
                    if (null === $leadingSpace || strlen($matches[0]) < strlen($leadingSpace)) {
                        $leadingSpace = $matches[0];
                    }
                }
            }
            if (!empty($leadingSpace)) {
                $snippetLines = array_map(static fn ($line) => preg_replace('/^'.preg_quote($leadingSpace, '/').'/', '', $line), $snippetLines);
            }
        }
        $snippet = implode(PHP_EOL, $snippetLines);

        $prefix = $spec['prefix'] ?? '';
        $suffix = $spec['suffix'] ?? '';

        $snippetStart = $spec['source'];
        $snippetEnd = $prefix.'end-text-snippet'.$suffix;

        // Code delimiters
        [$codeDelimiterStart, $codeDelimiterEnd] = match (pathinfo($baseFilename, PATHINFO_EXTENSION)) {
            'md' => ['```%lang%', '```'],
            default => ['', ''],
        };

        $replacements = [
            '%lang%' => $spec['parameters']['lang'] ?? pathinfo($url, PATHINFO_EXTENSION),
        ];
        $snippetDelimiterStart = str_replace(array_keys($replacements), array_values($replacements), $codeDelimiterStart);
        $snippetDelimiterEnd = str_replace(array_keys($replacements), array_values($replacements), $codeDelimiterEnd);

        return implode(PHP_EOL, [
            $snippetStart,
            $snippetDelimiterStart,
            $snippet,
            $snippetDelimiterEnd,
            $snippetEnd,
        ]);
    }

    protected function configureOptions(OptionsResolver $resolver): void
    {
    }
}
