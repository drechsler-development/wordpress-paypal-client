<?php

namespace DD\PayPal;

use DD\PayPal\Exception\ValidationException;

class Contact
{
	private string $firstName = '';
	private string $lastName  = '';
	private string $email     = '';
	private string $mobile   = '';
	private string $postCode = '';
	private string $city     = '';
	private string $street = '';
	private string $number = '';
	private string $address = '';
	private string $countryCode = '';

	/**
	 * Contact constructor.
	 */
	public function __construct () {

	}

	/**
	 * @return string
	 */
	public function getFirstName (): string {
		return $this->firstName;
	}

	/**
	 * @param string $firstName
	 *
	 * @return void
	 */
	public function setFirstName (string $firstName): void {
		$this->firstName = $firstName;
	}

	/**
	 * @return string
	 */
	public function getLastName (): string {
		return $this->lastName;
	}

	/**
	 * @param string $lastName
	 *
	 * @return void
	 */
	public function setLastName (string $lastName): void {
		$this->lastName = $lastName;
	}

	/**
	 * @return string
	 */
	public function getEmail (): string {
		return $this->email;
	}

	/**
	 * @param string $email
	 *
	 * @return void
	 * @throws ValidationException
	 */
	public function setEmail (string $email): void {

		//Sanitize email
		//make lowercase
		$email = strtolower($email);
		if($email != filter_var($email, FILTER_SANITIZE_EMAIL)){
			throw new ValidationException('Invalid email address');
		}
		$this->email = $email;
	}

	/**
	 * @return string
	 */
	public function getMobile (): string {
		return $this->mobile;
	}

	/**
	 * @param string $mobile
	 *
	 * @return void
	 */
	public function setMobile (string $mobile): void {

		$this->mobile = $mobile;
	}

	/**
	 * @return string
	 */
	public function getPostCode (): string {
		return $this->postCode;
	}

	/**
	 * @param string $postCode
	 *
	 * @return void
	 */
	public function setPostCode (string $postCode): void {
		$this->postCode = $postCode;
	}

	/**
	 * @return string
	 */
	public function getCity (): string {
		return $this->city;
	}

	/**
	 * @param string $city
	 *
	 * @return void
	 */
	public function setCity (string $city): void {
		$this->city = $city;
	}

	/**
	 * @return string
	 */
	public function getStreet (): string {
		return $this->street;
	}

	/**
	 * @param string $street
	 *
	 * @return void
	 */
	public function setStreet (string $street): void {
		$this->street = $street;
	}

	/**
	 * @return string
	 */
	public function getNumber (): string {
		return $this->number;
	}

	/**
	 * @param string $number
	 *
	 * @return void
	 */
	public function setNumber (string $number): void {
		$this->number = $number;
	}

	/**
	 * returns the contacts address as a string. If address is empty, it will be tried to be created by the street and the number
	 * @return string
	 */
	public function getAddress (): string {
		if(empty($this->address)) {
			$this->address = $this->street . ' ' . $this->number;
		}
		return $this->address;
	}

	/**
	 * sets the contacts address.
	 * If street and number are empty, the address will be split by the first comma or space and set the street and the number
	 *
	 * @param string $address
	 *
	 * @return void
	 */
	public function setAddress (string $address): void {
		$this->address = $address;
		if(empty($this->street) && empty($this->number)) {
			$this->SplitAddress ();
		}
	}

	/**
	 * @return string a valid countr ycode with two characters
	 */
	public function getCountryCode (): string {
		return $this->countryCode;
	}

	/**
	 * @param string $countryCode a valid country code with two characters
	 *
	 * @return void
	 */
	public function setCountryCode (string $countryCode): void {
		$this->countryCode = $countryCode;
	}

	/**
	 * Sets the street and the number by splitting the address by the first comma or space
	 *
	 * @return void
	 */
	private function SplitAddress () : void {

		if(empty($this->address)) {
			return;
		}

		$commaPos = strrpos ($this->address, ',');
		$spacePos = strrpos ($this->address, ' ');

		$delimiterPos = $commaPos !== false ? $commaPos : $spacePos;

		if ($delimiterPos === false) {
			return;
		}

		$street = trim (substr ($this->address, 0, $delimiterPos));
		$number = trim (substr ($this->address, $delimiterPos + 1));

		$this->street = $street;
		$this->number = $number;

	}

	/**
	 * returns the full name of the contact concatenated with a space of the first and the last name
	 *
	 * @return string
	 */
	public function getFullName () : string {
		return $this->firstName . ' ' . $this->lastName;
	}

}
