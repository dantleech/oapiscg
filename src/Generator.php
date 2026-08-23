<?php

declare(strict_types=1);


namespace DTL\OapiScg;


use Generator as PhpGenerator;
use PhpParser\PrettyPrinter\Standard;

final class Generator
{
    public function __construct(
        private Builder $builder,
        private ClassFileGenerator $generator,
        private Dumper $dumper,
        private ModelVisitors $visitor,
    )
    {
    }

    public static function new(Config $config): self
    {
        if (substr($config->outPath, 0, 1) !== '/') {
            $cwd = getcwd();
            if (false === $cwd) {
                throw new \RuntimeException('Could not resolve CWD');
            }
            $outputPath = $cwd . '/' . $config->outPath;
        }

        $finder = SchemaFinder::fromJsonSpec($config->specPath);

        $builder = new Builder(
            $finder,
            namespace: $config->namespace,
            inlineLevel: $config->inlineLevel
        );
        $generator = new ClassFileGenerator(namespacePrefix: $config->namespace, astVisitors: $config->astVisitors);
        $dumper = new Dumper(new Standard(), $config->outPath);

        return new self(
            $builder,
            $generator,
            $dumper,
            new ModelVisitors($config->modelVisitors)
        );
    }

    /**
     * @return PhpGenerator<DumpReport>
     */
    public function generate(string ...$names): PhpGenerator
    {
        $models = $this->builder->generate(...$names);
        foreach ($models as $model) {
            $this->visitor->visit($model);
            $file = $this->generator->generate($model);
            yield $this->dumper->dump($file);
        }
    }
}
