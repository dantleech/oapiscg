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
        private ModelVisitor $visitor,
    )
    {
    }

    public static function new(
        string $openApiUri,
        string $outputPath,
        string $namespace = '',
        int $inlineLevel = 2,
        ?ModelVisitor $visitor = null,
    ): self
    {
        if (substr($outputPath, 0, 1) !== '/') {
            $cwd = getcwd();
            if (false === $cwd) {
                throw new \RuntimeException('Could not resolve CWD');
            }
            $outputPath = $cwd . '/' . $outputPath;
        }

        $finder = SchemaFinder::fromJsonSpec($openApiUri);

        $builder = new Builder(
            $finder,
            namespace: $namespace,
            inlineLevel: $inlineLevel
        );
        $generator = new ClassFileGenerator(namespacePrefix: $namespace);
        $dumper = new Dumper(new Standard(), $outputPath);

        return new self($builder, $generator, $dumper, $visitor ?? new ModelVisitor([]));
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
