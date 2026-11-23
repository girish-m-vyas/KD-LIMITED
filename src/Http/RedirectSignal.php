<?php
declare(strict_types=1);

namespace KD\Http;

/** Internal: unwinds the stack after a redirect header has been sent. */
final class RedirectSignal extends \RuntimeException {}
