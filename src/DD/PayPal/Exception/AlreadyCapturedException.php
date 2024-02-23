<?php

namespace DD\PayPal\Exception;

use Exception;

class AlreadyCapturedException extends Exception {

	public function errorMessage (): string {

		return $this->getMessage ();

	}

}
