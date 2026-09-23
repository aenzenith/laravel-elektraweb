<?php

declare(strict_types=1);

namespace Aenzenith\ElektraWeb\Exceptions;

final class UnknownModuleException extends ElektraWebException
{
    public static function named(string $name): self
    {
        return new self(sprintf('ElektraWeb module [%s] is not registered.', $name));
    }
}
