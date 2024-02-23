<?php

namespace DD\PayPal;

class Header {

	/**
	 * @var string $referenceId This is normally the ID of the order in the shop
	 */
	public string $referenceId = '';

	/**
	 * @var string $currencyCode The 3 digit currency code representing the currency of the complete order
	 */
	public string $currencyCode = 'EUR';

	/**
	 * @var string $brandName contains the brand name that is shown to the buyer in the PayPal checkout.
	 */
	public string $brandName = '';

}
