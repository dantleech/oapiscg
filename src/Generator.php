<?php

namespace DTL\OapiScg;

use DTL\OapiScg\Builder;
use Generator as PhpGenerator;
use PhpParser\PrettyPrinter\Standard;

final class Generator
{
    public function __construct(
        private Builder $builder,
        private ClassFileGenerator $generator,
        private Dumper $dumper,
    )
    {
    }

    public static function new(
        string $openApiUri,
        string $outputPath,
        string $namespace = '',
    ): self
    {
        if (file_exists($openApiUri)) {
            $openApiUri = realpath($openApiUri);
            if (false === $openApiUri) {
                throw new \RuntimeException(sprintf(
                    'Could not get realpath for "%s"',
                    $openApiUri
                ));
            }
        }
        $finder = SchemaFinder::fromJsonSpec($openApiUri);

        $builder = new Builder($finder, $namespace);
        $generator = new ClassFileGenerator($namespace);
        $dumper = new Dumper(new Standard(), $outputPath);

        return new self($builder, $generator, $dumper);
    }

    /**
     * @return PhpGenerator<DumpReport>
     */
    public function generate(string ...$names): PhpGenerator
    {
        $models = $this->builder->generate(...$names);
        foreach ($models as $model) {
            $file = $this->generator->generate($model);
            yield $this->dumper->dump($file);
        }
    }
}
