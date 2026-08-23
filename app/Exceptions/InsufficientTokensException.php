<?php

namespace App\Exceptions;

use Exception;

class InsufficientTokensException extends Exception
{
    public function __construct(
        string $message = 'You’ve used this month’s free review links. Buy tokens to send more.',
        public readonly int $freeLimit = 5,
        public readonly int $freeUsed = 5,
        public readonly int $tokenBalance = 0,
        public readonly int $tokensNeeded = 1,
    ) {
        parent::__construct($message);
    }
}
