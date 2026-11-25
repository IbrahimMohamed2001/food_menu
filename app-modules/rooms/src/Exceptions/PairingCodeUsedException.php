<?php

namespace Modules\Rooms\Exceptions;

use Exception;

final class PairingCodeUsedException extends Exception
{
    public function __construct()
    {
        parent::__construct('The pairing code has already been used.');
    }
}
