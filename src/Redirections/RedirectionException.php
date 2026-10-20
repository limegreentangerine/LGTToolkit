<?php

namespace LgtToolkit\Redirections;

/**
 * Thrown when a redirection fails validation; the message is safe to show to admins.
 */
class RedirectionException extends \InvalidArgumentException {}
