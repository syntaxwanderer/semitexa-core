<?php

declare(strict_types=1);

namespace Semitexa\Core\Http\Exception;

use Exception;

/**
 * Thrown when a resource is not found. The system will treat this as HTTP 404
 * and may dispatch to the route named error.404 (if registered), so modules
 * like core-frontend can render a custom 404 page.
 */
class NotFoundException extends Exception
{
}
