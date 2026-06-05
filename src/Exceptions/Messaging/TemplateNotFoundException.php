<?php

declare(strict_types=1);

namespace Polis\Exceptions\Messaging;

use RuntimeException;

/**
 * Thrown when EmailTemplateRenderingService can't resolve a template key —
 * neither a DB row nor an in-code DefaultEmailTemplates fallback exists.
 *
 * This is typically a programmer error: either the key is misspelled in the
 * caller, or a new template was added without a code default and no DB row
 * has been seeded for it yet.
 */
class TemplateNotFoundException extends RuntimeException {}
