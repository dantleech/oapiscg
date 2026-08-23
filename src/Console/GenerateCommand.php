<?php

declare(strict_types=1);


namespace DTL\OapiScg\Console;


use DTL\OapiScg\ConfigLoader;

use DTL\OapiScg\Generator;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class GenerateCommand extends Command
{
    const NAME = 'generate';
    const ARG_NAME = 'name';


    public function __construct(private ConfigLoader $loader)
    {
        parent::__construct();
    }

    public function configure(): void
    {
        $this->setName(self::NAME);
        $this->addArgument(self::ARG_NAME, InputArgument::REQUIRED, 'Name of generation config');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var string $name */
        $name = $input->getArgument(self::ARG_NAME);
        $configs = $this->loader->load();

        $config = $configs->get($name);

        $generator = Generator::new($config);

        $output->writeln(sprintf('Writing to directory: %s', $config->outPath));
        $output->writeln('');
            
        $start = microtime(true);
        $count = 0;
        foreach ($generator->generate(...$config->components) as $result) {
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
