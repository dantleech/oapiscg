<?php

declare(strict_types=1);


namespace DTL\OapiScg\Console;

use DTL\OapiScg\Generator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class GenerateCommand extends Command
{
    const NAME = 'generate';
    const ARG_SPEC_PATH = 'spec-path';
    const ARG_OUT_PATH = 'out-path';
    const OPT_NAMESPACE = 'namespace';
    const OPT_INLINE_LEVEL = 'inline-level';
    const ARG_COMPONENTS = 'components';

    public function configure(): void
    {
        $this->setName(self::NAME);

        $this->addArgument(self::ARG_SPEC_PATH, InputArgument::REQUIRED, 'Path to OpenAPI spec (JSON file)');
        $this->addArgument(self::ARG_OUT_PATH, InputArgument::REQUIRED, 'Path in which to generate the code');
        $this->addArgument(self::ARG_COMPONENTS, InputArgument::IS_ARRAY, 'Components to generate');
        $this->addOption(self::OPT_NAMESPACE, null, InputOption::VALUE_REQUIRED, 'Namespace in which to generate code');
        $this->addOption(self::OPT_INLINE_LEVEL, null, InputOption::VALUE_REQUIRED, 'Level at which to start using array-shapes instead of classes', 2);
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var string $specPath */
        $specPath = $input->getArgument(self::ARG_SPEC_PATH);
        /** @var string $outPath */
        $outPath = $input->getArgument(self::ARG_OUT_PATH);
        /** @var list<string> $components */
        $components = $input->getArgument(self::ARG_COMPONENTS);
        /** @var null|string $namespace */
        $namespace = $input->getOption(self::OPT_NAMESPACE);
        /** @var null|string $inlineLevel */
        $inlineLevel = $input->getOption(self::OPT_INLINE_LEVEL);

        $generator = Generator::new(
            $specPath,
            $outPath,
            namespace: $namespace ?? '',
            inlineLevel: (int)$inlineLevel
        );

        $output->writeln(sprintf('Writing to directory: %s', $outPath));
        $output->writeln('');
            
        $start = microtime(true);
        $count = 0;
        foreach ($generator->generate(...$components) as $result) {
            $count++;
            $output->writeln(sprintf('<fg=cyan>%4d bytes</> <fg=red>></> %s',$result->written, implode('<fg=cyan>.</>', explode('/', $result->path))));
        }

        $output->writeln('');
        $message = sprintf('<fg=black;bg=green> Generated %s classes in %s seconds </>', $count, number_format(microtime(true) - $start, 4));
        $output->writeln($message);
        $output->writeln('');

        return 0;
    }
}
