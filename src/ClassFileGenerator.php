<?php

namespace DTL\OapiScg;

use DTL\OapiScg\Generate\SourceFile;
use DTL\OapiScg\Model\ClassModel;
use PhpParser\Builder;
use PhpParser\Node\Stmt\Class_;

final class ClassFileGenerator
{
    public function generate(ClassModel $model): SourceFile
    {
        $stmts = [];
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
            $parameter->setType($property->phpType->nativeTypeString());
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
