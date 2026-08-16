<?php

namespace DTL\OapiScg;

use DTL\OapiScg\Generate\SourceFile;
use DTL\OapiScg\Model\ClassModel;
use DTL\OapiScg\Model\Type\ClassType;
use PhpParser\Builder;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Namespace_;

final class ClassFileGenerator
{
    public function __construct()
    {
    }

    public function generate(ClassModel $model): SourceFile
    {
        $stmts = [];
        if ($model->name->namespace() !== '') {
            $stmts[] = new Namespace_(new Name($model->name->namespace()));
        }
        $stmts[] = $this->generateClass($model);

        return new SourceFile($model->name->shortName(), $stmts);
    }

    private function generateClass(ClassModel $model): Class_
    {
        $class = new Builder\Class_($model->name->shortName());
        $class->makeFinal();
        $class->makeReadonly();
        $ctor = new Builder\Method('__construct');
        foreach ($model->properties as $property) {
            $parameter = new Builder\Param($property->name);
            $parameter->makePublic();
            $propertyType = $property->phpType;
            $parameter->setType($propertyType->nativeTypeString());
            $ctor->addParam($parameter);
        
        }
        $ctor->setDocComment($this->ctorDocComment($model));
        $class->addStmt($ctor);
        
        return $class->getNode();
    }

    private function ctorDocComment(ClassModel $model): string
    {
        $comment = ['/**'];
        foreach ($model->properties as $property) {
            $comment[] = sprintf(' * @param %s $%s', $property->phpType->phpDocString(), $property->name);
        }
        $comment[] = ' */';
        return implode("\n", $comment);

    }
}
