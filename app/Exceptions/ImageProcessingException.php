<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Raised when an upload cannot be processed into a stored image.
 *
 * Carries a message fit to show an author, so the CMS can surface a field
 * error instead of a 500.
 */
class ImageProcessingException extends RuntimeException {}
