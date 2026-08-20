<?php

declare(strict_types=1);


namespace DTL\OapiScg;

use DTL\OapiScg\Generate\SourceFile;
use DTL\OapiScg\Model\ClassModel;
use DTL\OapiScg\Model\PropertyModel;

use PhpParser\Builder;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Namespace_;

final class ClassFileGenerator
{
    public function __construct(string $namespacePrefix = '')
    {
    }

    public function generate(ClassModel $model): SourceFile
    {
        $stmts = [];
        if ($model->name->namespace() !== '') {
            $stmts[] = new Namespace_(new Name($model->name->namespace()));
        }
        $stmts[] = $this->generateClass($model);


        return new SourceFile($this->resolveRelativePath($model), $stmts);
    }

    private function generateClass(ClassModel $model): Class_
    {
        $class = new Builder\Class_($model->name->shortName());
        $class->makeFinal();

        $comment = [];
        if ($model->description !== null) {
            $comment[] = '/**';
            $comment[] = sprintf(' * %s', $model->description);
            $comment[] = ' */';
            $class->setDocComment(implode("\n", $comment));
        }

        //$class->extend('\\Spatie\\LaravelData\\Data');
        $ctor = new Builder\Method('__construct');

        foreach ($this->orderProperties($model->properties) as $property) {
            $parameter = new Builder\Param($property->name);
            $parameter->makePublic();
            $propertyType = $property->phpType;
            $parameter->setType($propertyType->nativeTypeString());
            if ($property->default !== null) {
                $parameter->setDefault($property->default->value);
            }
            $ctor->addParam($parameter);
        
        }
        $ctorDocComment = $this->ctorDocComment($model);

        if ($ctorDocComment !== null) {
            $ctor->setDocComment($ctorDocComment);
        }

        $class->addStmt($ctor);
        
        return $class->getNode();
    }

    private function ctorDocComment(ClassModel $model): ?string
    {
        $comment = ['/**'];
        foreach ($model->properties as $property) {
            if ($property->phpType->phpDocString() === $property->phpType->nativeTypeString() && $property->description === null) {
                continue;
            }
            $comment[] = sprintf(
                ' * @param %s $%s%s',
                $property->phpType->phpDocString(),
                $property->name,
                $property->description ? ' ' . $property->description : '',
            );
        }

        if (count($comment) === 1) {
            return null;
        }

        $comment[] = ' */';
        return implode("\n", $comment);

    }

    /**
     * @param array<array-key,PropertyModel> $properties
     * @return array<array-key,PropertyModel>
     */
    private function orderProperties(array $properties): array
    {
        // ensure that required properties (i.e. those without default values)
        // are positioned before optional ones (ones that do have default values).
        usort($properties, static fn (PropertyModel $property1, PropertyModel $property2) => $property1->default === null ? -1 : 1);
        return $properties;
    }

    private function resolveRelativePath(ClassModel $model): string
    {
        return $model->name->shortName();
    }
}
