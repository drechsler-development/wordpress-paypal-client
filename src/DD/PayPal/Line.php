<?php

namespace DD\PayPal;

class Line {

	/**
	 * @var string $referenceId This is normally the ID of the item in the actual shop system. This is not used yet
	 */
	public string $referenceId = '';

	/**
	 * @var string $name The name of the item
	 */
	public string $name = '';

	/**
	 * @var string $description The description of the item
	 */
	public string $description = '';

	/**
	 * @var float $quantity The quantity of the item
	 */
	public float $quantity = 0;

	/**
	 * @var float $unitPrice The (net)price of the item
	 */
	public float $unitPrice = 0;

	/**
	 * @var float $tax The tax of the item
	 */
	public float $taxPercent = 0;

	public function __construct () {
	}

	public function GetNetAmount (bool $rounded): float {

		$value = $this->unitPrice;
		if ($rounded) {
			$value = self::RoundValue ($value);
		}
		return $value;

	}

	public function GetTaxAmount (bool $rounded): float {

		$value = $this->GetGrossAmount (false) - $this->GetNetAmount (false);
		if ($rounded) {
			$value = self::RoundValue ($value);
		}
		return $value;

	}

	public function GetGrossAmount (bool $rounded): float {

		$value = $this->unitPrice * (1 + ($this->taxPercent / 100));
		if ($rounded) {
			$value = self::RoundValue ($value);
		}
		return $value;

	}

	public function GetLineNetAmount (bool $rounded): float {

		$value = $this->GetNetAmount (false) * $this->quantity;
		if ($rounded) {
			$value = self::RoundValue ($value);
		}
		return $value;

	}

	public function GetLineTaxAmount (bool $rounded): float {

		$value = $this->GetTaxAmount(false) * $this->quantity;
		if ($rounded) {
			$value = self::RoundValue ($value);
		}
		return $value;

	}

	public function GetLineGrossAmount (bool $rounded): float {

		$value = $this->GetGrossAmount(false) * $this->quantity;
		if ($rounded) {
			$value = self::RoundValue ($value);
		}
		return $value;

	}

	public function GetTotalNetAmount (bool $rounded): float {

		$value = $this->GetGrossAmount(false) * $this->quantity;
		if ($rounded) {
			$value = self::RoundValue ($value);
		}
		return $value;

	}

	public function GetTotalTaxAmount (bool $rounded): float {

		$value = $this->GetGrossAmount(false) * $this->quantity;
		if ($rounded) {
			$value = self::RoundValue ($value);
		}
		return $value;

	}

	public function GetTotalGrossAmount (bool $rounded): float {

		$value = $this->GetGrossAmount(false) * $this->quantity;
		if ($rounded) {
			$value = self::RoundValue ($value);
		}
		return $value;

	}

	private static function RoundValue(float $value): float {

		return round($value, 2);
	}

}
