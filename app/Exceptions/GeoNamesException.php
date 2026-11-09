<?php

namespace App\Exceptions;

use Exception;

/**
 * GeoNames couldn't answer: it was unreachable, or it reported an error such as a used-up request limit.
 */
class GeoNamesException extends Exception {}
