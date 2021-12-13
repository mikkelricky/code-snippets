<?php

namespace MikkelRicky\CodeSnippets\Command;

use MikkelRicky\CodeSnippets\Snippets;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

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
            ->addOption('update-files', null, InputOption::VALUE_NONE, 'Update files')
            ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $updateFiles = $input->getOption('update-files');
        $filenames = $input->getArgument('files');
        assert(is_array($filenames));
        $this->snippets->setLogger(new ConsoleLogger($output));
        foreach ($filenames as $filename) {
            try {
                $content = $this->snippets->process($filename);
                if ($updateFiles) {
                    $io->info(sprintf('Updating file %s', $filename));
                    file_put_contents($filename, $content);
                } else {
                    $output->write($content);
                }
            } catch (\Throwable $t) {
                $output->writeln($t->getMessage());
            }
        }

        return Command::SUCCESS;
    }
}
