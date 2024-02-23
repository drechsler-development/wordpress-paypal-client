# # (Wordpress) PayPal Client

This PayPal Client can be used to integrate PayPal into your WordPress website.
Of course it can be used for other purposes as well, but it was firstly developed with the intention to be used for a WordPress website.
It is just simplify the way of the integration already provided in the official PayPal Package

# Dependencies

This package requires other packages as below:

- drechsler-development/class-library
- paypal/paypal-checkout-sdk

## Usage

It can simply be used using composer as below:

    composer require drechsler-development/wordpress-paypal-client

Or if you prefer a composer.json file add the project name in the require segment as below:

    {  
	    "require": {  
			"drechsler-development/wordpress-paypal-client": "v1.*"
		}
	}

## Create an Order

Assuming you have all necassary variables (and apropriate values) somewhere caught in your script already, you only need to pass it to the appropriate method(s).
Required variables are:

````php
    /*your payPal Client ID you will get from your PayPal developer console at https://developer.paypal.com/dashboard/ */
    $payPalClientId = ''; //put in your PayPal Client ID 
    
    /*your PayPal Client Secret you will get from your PayPal developer console at https://developer.paypal.com/dashboard/ */
    $payPalClientSecret = ''; //put in your PayPal Client Secret
    
    //boolean value to define if you want to use the PayPal Sandox for testing (true) or PayPal Production (false)
    $payPalSandbox = true; // or false for production
    
    /* optional a reference ID that will be used for a reference for example in your ERP- or shop system */ 
    $referenceId = 'ABC123';
    /* Contact Details like */
    $firstName = 'John';
    $lastName = 'Doe';
    $email = 'john.doe@drechsler-development.de';
    $tel = '1234567890';
    $postCode = '12345';
    $city = 'Musterstadt';
    /* if you will pass street and number, address will become not necassary, as the Contact class method behind will concatenate the street and number with a space and will set that as the address. As well as you will pass rthe address it will be set as street and number, as long it contains a space as a divider between street and number */
    $street = 'Musterstraße';
    $number = '123';
    $address = 'Musterstraße 123'; //optional, if street and number are set, this will be ignored
    /* Items */
    $myItems[]; 
    /* each item should have */
    $item['name'] = 'Item Name'
    $item['quantity'] = 2;
    $item['netAmount'] = 100.00; //This is the net amount of the item (aka 'unit amount')
    $item['taxPercent'] = 19;
    $item['description'] = 'My beautiful item'; /* optional */
````
In this example I am using the file ajax.php and call it via a POST request from another script via JavaScript/jQuery or whatever you are prefer.

````php
<?php

use DD\Mailer\Mailer;
use DD\PayPal\Header;  
use DD\PayPal\Line;  
use DD\PayPal\Process;  
use DD\PayPal\Contact;

require_once __DIR__ . '/../vendor/autoload.php'; //or whereever your autoload.php is located

//Initialisze the response array and set the error to an empty string, to check in the calling script if all went well or if we have errors
$responseArray['error'] = '';

//Create PayPal object
$PayPal = new Process($payPalClientId, $payPalClientSecret, $payPalSandbox);  

//Create PayPal Header object
$OrderHeader = new Header();  
$OrderHeader->referenceId = $salesHeaderId;  
$OrderHeader->currencyCode = 'EUR';  

//Create Buyer (Contact) object
$Contact = new PayPalContact();
$Contact->setFirstName ($firstName);  
$Contact->setLastName ($lastName);  
$Contact->setEmail ($email);  
$Contact->setMobile ($tel);  
$Contact->setPostCode ($postCode);  
$Contact->setCity ($city);  
$Contact->setStreet ($street);  
$Contact->setNumber ($number);  
//$Contact->setAddress ($address ?? ''); //not necassary if street and number are set
 
//Declare an empty array to add (push) Line objects to it
$items = [];  
foreach ($myItems as $item) {
    $ItemLine = new Line();  
    $ItemLine->name = $item['name'];  
    $ItemLine->quantity = $item['quantity'];
    $ItemLine->unitPrice = $item['netAmount'] ?? 0;  
    $ItemLine->taxPercent = $item['taxPercent'] ?? 0;  
    $ItemLine->description = $item['description'] ?? '';  
    //Add created Item to the items array
    $items[] = $ItemLine;  
}
$successPage = '/success', 
$cancelPage = ''; //If empty it will take the original HTTP_HOST from the global $_SERVER array 

try {
    //Process (Post) a PayPal Order to PayPal API
    $PayPal->CreateOrder ($OrderHeader, $Contact, $items, $successPage, $cancelPage ?? '');  
  
    //if all went well we will get back a confirmation url
    // we need to use it to redirect the user to that page in the success method of our promise or ajax call
    // So that the user can log in into PayPal and finish the payment process  
    $responseArray['confirmationUrl'] = $PayPal->confirmationUrl ?? '';  
    $responseArray['debug'] = $PayPal->debug; //the $PayPal->debug array is only available in SandBox Box mode

}catch(Exception $e){
    
    //if something went wrong, The class will throw an error, we can catch and process in our way we want
    $responseArray['error'] = $e->getMessage();
    
}
    
//Return the response array to the calling script
echo json_encode($responseArray);
?>

````
