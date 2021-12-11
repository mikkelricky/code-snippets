<?php

namespace MikkelRicky\CodeSnippets\Command;

use MikkelRicky\CodeSnippets\Snippets;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

class CodeSnippetsCommand extends Command
{
    protected static $defaultName = 'code-snippets';
    protected static $defaultDescription = 'Extract code snippets from source files';

    public function __construct(private Snippets $snippets)
    {
        parent::__construct(null);
    }

    protected function configure(): void
    {
        $this
            ->addArgument('files', InputArgument::REQUIRED|InputArgument::IS_ARRAY, 'The files to process')
            ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $filenames = $input->getArgument('files');
        assert(is_array($filenames));
        $this->snippets->setLogger(new ConsoleLogger($output));
        foreach ($filenames as $filename) {
            $content = $this->snippets->process($filename);
            $output->write($content);
        }

        return Command::SUCCESS;
    }
}
