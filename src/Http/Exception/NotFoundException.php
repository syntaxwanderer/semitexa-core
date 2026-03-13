<?php

declare(strict_types=1);

namespace Semitexa\Core\Http\Exception;

use Semitexa\Core\Http\HttpStatus;

/**
 * Thrown when a resource is not found. The system will treat this as HTTP 404
 * and map it to a JSON/HTML/XML response via ExceptionMapper (not via the
 * error.404 route).
 *
 * @deprecated Use Semitexa\Core\Exception\NotFoundException instead.
 */
class NotFoundException extends \Semitexa\Core\Exception\DomainException
{
    public function __construct(string $message = 'Not Found')
    {
        parent::__construct($message);
    }

    public function getStatusCode(): HttpStatus
    {
        return HttpStatus::NotFound;
    }
}
