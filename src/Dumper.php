<?php

declare(strict_types=1);


namespace DTL\OapiScg;

use DTL\OapiScg\Generate\SourceFile;
use PhpParser\PrettyPrinter;

final class Dumper
{
    public function __construct(private PrettyPrinter $printer, private string $outputPath)
    {
    }

    public function dump(SourceFile $file): DumpReport
    {
        $path = sprintf('%s/%s.php', $this->outputPath, $file->name);
        $content = $this->printer->prettyPrintFile($file->stmts);
        if (!file_exists(dirname($path))) {
            if (false === mkdir(dirname($path), 0o744, true)) {
                throw new \RuntimeException(sprintf(
                    'Could not created directory: %s',
                    dirname($path)
                ));
            }
        }

        $written = file_put_contents($path, $content);

        if (false === $written) {
            throw new \RuntimeException(sprintf('Could not write file: %s', $path));
        }

        return new DumpReport($path, $written);
    }
}
