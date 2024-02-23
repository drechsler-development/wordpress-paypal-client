<?php

namespace DD\PayPal\Exception;

use Exception;

class ValidationException extends Exception {

	public function errorMessage (): string {

		return $this->getMessage ();

	}

}
