<?php

declare(strict_types=1);

namespace Aenzenith\ElektraWeb\Exceptions;

use RuntimeException;

/**
 * Root of every exception thrown by this package.
 *
 * Catch this when you do not care which ElektraWeb module failed.
 */
class ElektraWebException extends RuntimeException {}
