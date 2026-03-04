<?php

declare(strict_types=1);

namespace Semitexa\Core\Attributes;

use Attribute;

/**
 * Explicitly marks a class as request-scoped (mutable).
 * The container will clone a prototype of this class per request
 * and inject RequestContext (Request, Session, CookieJar, etc.).
 *
 * Use when the class needs request-scoped state but its name
 * does not contain 'Handler' or 'Listener'.
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class AsMutable
{
}
