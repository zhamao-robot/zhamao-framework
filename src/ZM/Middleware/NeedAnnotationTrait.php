<?php

declare(strict_types=1);

namespace ZM\Middleware;

use ZM\Annotation\AnnotationBase;

trait NeedAnnotationTrait
{
    protected ?AnnotationBase $annotation = null;

    public function setAnnotation(AnnotationBase $annotation): void
    {
        $this->annotation = $annotation;
    }

    public function getAnnotation(): ?AnnotationBase
    {
        return $this->annotation;
    }
}
