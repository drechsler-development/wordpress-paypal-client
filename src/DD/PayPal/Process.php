<?php

namespace DD\PayPal;

use DD\Exceptions\ValidationException;
use DD\PayMent\PayPal\PayPalAddress;
use DD\PayMent\PayPal\PayPalAmount;
use DD\PayMent\PayPal\PayPalApplicationContext;
use DD\PayMent\PayPal\PayPalBreakDown;
use DD\PayMent\PayPal\PayPalItem;
use DD\PayMent\PayPal\PayPalName;
use DD\PayMent\PayPal\PayPalOrder;
use DD\PayMent\PayPal\PayPalPurchaseUnit;
use DD\PayMent\PayPal\PayPalRequestBody;
use DD\PayMent\PayPal\PayPalShipping;
use DD\PayMent\PayPal\PayPalValues;
use DD\PayPal\Exception\AlreadyCapturedException;
use PayPalCheckoutSdk\Core\PayPalHttpClient;
use PayPalCheckoutSdk\Core\ProductionEnvironment;
use PayPalCheckoutSdk\Core\SandboxEnvironment;
use PayPalCheckoutSdk\Orders\OrdersCaptureRequest;
use PayPalCheckoutSdk\Orders\OrdersCreateRequest;
use PayPalCheckoutSdk\Orders\OrdersGetRequest;
use PayPalHttp\HttpException;
use PayPalHttp\IOException;
use PHPMailer\PHPMailer\Exception;

class Process {

	### PUBLIC ###

	public string $currency  = 'EUR';

	/*
	 * string $standardErrorMessage contains the standard error message that is shown to the buyer if something went wrong with the PayPal payment.
	 */
	public string $standardErrorMessage = '"PayPal steht momentan nicht zur Verfügung. Bitte überweisen Sie den Betrag laut Aufforderung in der Email, die wir Ihnen gerade geschickt haben!"';

	/*
	 * string $confirmationUrl contains the confirmation URL from PayPal after the buyer has confirmed the payment.
	 */
	public string $confirmationUrl = '';

	/*
	 * bool $debugMode contains the information if the debug mode is enabled. Standard is false
	 */
	public bool $debugMode = false;

	### PRIVATE ###

	/**
	 * @var PayPalHttpClient $PayPalClient contains the PayPal Client
	 */
	private PayPalHttpClient $PayPalClient;

	/**
	 * @var bool
	 */
	private bool $usePayPalSandbox;

	/**
	 * @var array $debug contains the debug information
	 */
	public array $debug = [];

	/**
	 * Process constructor.
	 * It will create a new PayPalClient based on the provided API Client- and Secret and the environment either sandbox or production.
	 *
	 * @param string $payPalClientId
	 * @param string $payPalClientSecret
	 * @param bool   $usePayPalSandbox
	 */
	public function __construct (string $payPalClientId, string $payPalClientSecret, bool $usePayPalSandbox) {

		$this->usePayPalSandbox = $usePayPalSandbox;

		if ($this->usePayPalSandbox) {
			$environment = new SandboxEnvironment($payPalClientId, $payPalClientSecret);
		} else {
			$environment = new ProductionEnvironment($payPalClientId, $payPalClientSecret);
		}

		$this->PayPalClient = new PayPalHttpClient($environment);
	}

	/**
	 * Creates an Order with Header, lines as an array of Line-Objects, the buyer Contact to create an order in PayPal.
	 * The successPage and cancelPage are optional and can be used to redirect the buyer after the payment is done or canceled.
	 * If the successPage is empty the current URL is used.
	 * If the cancelPage is empty the current URL is used.
	 *
	 * @param Header  $Header      The Header of the Order.
	 * @param Contact $Contact     The Buyer Contact.
	 * @param Line[]  $orderLines  An array of OrderLines.
	 * @param string  $successPage The URL to redirect after Payment is done to capture the order. If empty the current URL is used.
	 * @param string  $cancelPage  The URL to redirect after Payment is canceled. If empty the current URL is used.
	 *
	 * @return void
	 * @throws ValidationException
	 * @throws Exception
	 */
	public function CreateOrder (
		Header  $Header,
		Contact $Contact,
		array   $orderLines,
		string  $successPage = '',
		string  $cancelPage = ''
	): void {

		$errorArray = [];
		$PayPalItems	  = [];

        ##################
		### Validation ###
        ##################

        ### OrderHeader Validation
		if (empty($Header->referenceId)) {
			$errorArray[] = 'The referenceId field in the order header is empty.';
		}

		if (empty($Header->currencyCode)) {
            $errorArray[] = 'The currencyCode field in the order header is empty.';
		}

        ### Contact Validation
		if (empty($Contact->getAddress())) {
			$errorArray[] = 'Either address or street and number in the Contact must be set.';
		}

		if (empty($Contact->getCity())) {
			$errorArray[] = 'The city field in the Contact object is empty.';
		}

		if (empty($Contact->getPostCode ())) {
			$errorArray[] = 'The postCode field in the Contact object is empty.';
		}

		if (empty($Contact->getFirstName ())) {
			$errorArray[] = 'The firstName field in the Contact object is empty.';
		}

		if (empty($Contact->getLastName ())) {
			$errorArray[] = 'The lastName field in the Contact object is empty.';
		}

        ### OrderLines
        $i=1;
		foreach ($orderLines as $OrderLines) {

            if (!$OrderLines instanceof Line) {
				$errorArray[] .= 'The order lines are not from the type ' . Line::class;
			}

            if(empty($OrderLines->name)){
				$errorArray[] .= 'The name field in the order line ' . $i . ' is empty.';
			}

			if(empty($OrderLines->description)){
				$errorArray[] .= 'The description field in the order line ' . $i . ' is empty.';
			}

			if(empty($OrderLines->unitPrice)){
				$errorArray[] .= 'The unitPrice field in the order line ' . $i . ' is empty.';
			}

			if(empty($OrderLines->quantity)){
				$errorArray[] .= 'The quantity field in the order line ' . $i . ' is empty.';
			}

			$i++;

		}

		reset ($orderLines);

        if(!empty($errorArray)){
            $message = implode("<br>", $errorArray);
			throw new ValidationException($message);
		}

		// creates a POST request to /v2/checkout/orders
		$request = new OrdersCreateRequest();
		$request->prefer ('return=representation');

		//Shipping Node
		$PayPalAddress                 = new PayPalAddress();
		$PayPalAddress->address_line_1 = $Contact->getAddress ();
		$PayPalAddress->admin_area_2   = $Contact->getCity ();
		$PayPalAddress->postal_code    = $Contact->getPostCode ();
		$PayPalAddress->country_code   = 'DE';

		$PayPalName            = new PayPalName();
		$PayPalName->full_name = $Contact->getFullName ();

		$PayPalShipping          = new PayPalShipping();
		$PayPalShipping->type    = PayPalShipping::SHIPPING;
		$PayPalShipping->name    = (array)$PayPalName;
		$PayPalShipping->address = (array)$PayPalAddress;

		//Items

		$totalNetAmount = 0;
		$totalTaxAmount = 0;
		$totalGrossAmount    = 0;

		foreach ($orderLines as $OrderLine) {

			if (!$OrderLine instanceof Line) {
				throw new ValidationException("The order line are not from the type " . Line::class . " This error shuóuld never appear as we already checked this before. Please contact the developer.");
			}

			$UnitAmount                = new PayPalAmount();
			$UnitAmount->value         = $OrderLine->GetLineNetAmount (true);
			$totalNetAmount			+= $UnitAmount->value;
			$UnitAmount->currency_code = $Header->currencyCode;

			$Tax                = new PayPalAmount();
			$Tax->currency_code = $Header->currencyCode;
			$Tax->value         = $OrderLine->GetLineTaxAmount (true);
			$totalTaxAmount		+= $Tax->value;

			$PayPalItem              = new PayPalItem();
			$PayPalItem->name        = $OrderLine->name;
			$PayPalItem->description = $OrderLine->description;
			$PayPalItem->unit_amount = (array)$UnitAmount;
			$PayPalItem->tax         = (array)$Tax;

			$PayPalItem->quantity = $OrderLine->quantity;

			$totalGrossAmount += $OrderLine->GetLineNetAmount (true) + $OrderLine->GetLineTaxAmount (true);

			$PayPalItems[] = $PayPalItem;

		}

		//Format total amounts
		$totalNetAmount = number_format($totalNetAmount, 2, '.', '');
		$totalTaxAmount = number_format($totalTaxAmount, 2, '.', '');
		$totalGrossAmount    = number_format($totalGrossAmount, 2, '.', '');

		//Amount

		$TotalNetAmount                = new PayPalValues();
		$TotalNetAmount->currency_code = $this->currency;
		$TotalNetAmount->value         = "$totalNetAmount";

		$TaxTotal                = new PayPalValues();
		$TaxTotal->currency_code = $Header->currencyCode;
		$TaxTotal->value         = "$totalTaxAmount";

		$Breakdown             = new PayPalBreakDown();
		$Breakdown->item_total = (array)$TotalNetAmount;
		$Breakdown->tax_total  = (array)$TaxTotal;

		$TotalGrossAmount                = new PayPalAmount();
		$TotalGrossAmount->value         = "$totalGrossAmount";
		$TotalGrossAmount->currency_code = $Header->currencyCode;
		$TotalGrossAmount->breakdown     = (array)$Breakdown;

		//PayPalPurchaseUnit
		$PurchaseUnit               = new PayPalPurchaseUnit();
		$PurchaseUnit->amount       = (array)$TotalGrossAmount;
		$PurchaseUnit->reference_id = "$Header->referenceId";
		$PurchaseUnit->items        = $PayPalItems;
		$PurchaseUnit->shipping     = (array)$PayPalShipping;

		//PayPalApplicationContext
		$PayPalApplicationContext              = new PayPalApplicationContext();

		//Check if the successPage and cancelPage are set with the correct format and protocoll
		if(!empty($successPage)){
			$successPage = str_contains($successPage, 'http') ? $successPage : "https://$_SERVER[HTTP_HOST]$successPage";
		}

		if(!empty($cancelPage)){
			$cancelPage = str_contains($cancelPage, 'http') ? $cancelPage : "https://$_SERVER[HTTP_HOST]$cancelPage";
		}

		$PayPalApplicationContext->return_url  = $successPage ?? "https://$_SERVER[HTTP_HOST]";
		$PayPalApplicationContext->cancel_url  = $cancelPage ?? "https://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
		$PayPalApplicationContext->brand_name  = $Header->brandName;
		$PayPalApplicationContext->user_action = PayPalApplicationContext::PAY_NOW;

		$PayPalOrder                      = new PayPalOrder();
		$PayPalOrder->intent              = PayPalOrder::CAPTURE;
		$PayPalOrder->application_context = (array)$PayPalApplicationContext;
		$PurchaseUnits                    = [];
		$PurchaseUnits[]                  = (array)$PurchaseUnit;
		$PayPalOrder->purchase_units      = $PurchaseUnits;

		$PayPalRequestBody = new PayPalRequestBody($PayPalOrder);

		$this->debug[] = $PayPalOrder;

		//Todo: check which one is valid
		//$requestBody       = (array)$PayPalOrder; //$PayPalRequestBody->requestBody;
		//$request->body = $requestBody;
		$request->body = $PayPalRequestBody->requestBody;

		try {

			// Call API with your client and get a response for your call
			$response = $this->PayPalClient->execute ($request);

			if ($response->statusCode == 201) {
				$token = $response->result->id ?? '';
				$link  = $response->result->links[1]->href ?? '';
				if ($link != '' && $token != '') {
					$this->confirmationUrl = $link;
				} else {
					$statusCode = $response->statusCode;
					$this->HandleErrorMessage ("1::StatusCode: " . $statusCode . " Error: PayPal did not return a confirmation URL. Please contact the owner of this page or the appropriate developer.");
				}
			} else {
				$statusCode = $response->statusCode;
				$message = $response->result->message ?? '';
				$message = self::IsJson ($message);
				$this->HandleErrorMessage ("2::StatusCode: " . $statusCode . " Error: " . $message);
			}

		} catch (HttpException $e) {

			$message = self::IsJson ($e->getMessage ());

			$statusCode = $e->statusCode ?? 'n/a';
			$this->HandleErrorMessage ("3::StatusCode: " . $statusCode . " Error: " . $message);

		} catch (IOException $e) {

			$statusCode = $e->statusCode ?? 'n/a';
			$this->HandleErrorMessage("4::StatusCode: " . $statusCode . " Error: " . $e->getMessage ());

		}

	}

	/**
	 * This method captures the order with the provided token for further processes in your ERP or wherever you want to use that piece of order information.
	 *
	 * @param string $token The token of the order we will get back in the buyers payment response to capture the order.
	 * @param bool   $ignoreAlreadyCaptured
	 *
	 * @return string the capture id of the order
	 * @throws Exception
	 * @throws HttpException
	 * @throws IOException
	 * @throws AlreadyCapturedException
	 */
	public function CaptureOrder (string $token, bool $ignoreAlreadyCaptured = false) : string {

		$captureId = '';

		try {

			$request = new OrdersCaptureRequest($token);
			$request->prefer ('return=representation');
			$response = $this->PayPalClient->execute ($request);

			$captureId = $response->result->purchase_units[0]->payments->captures[0]->id ?? '';

			//Todo: Why do we do another request here?
			$request  = new OrdersGetRequest($token);
			$this->PayPalClient->execute ($request);

		} catch (HttpException $e) {

			$statusCode = $e->statusCode;
			if ($statusCode == 422) {

				try {

					$request  = new OrdersGetRequest($token);
					$response = $this->PayPalClient->execute ($request);

					$captureId = $response->result->purchase_units[0]->payments->captures[0]->id ?? '';

					if (!empty($captureId)) {
                        if(!$ignoreAlreadyCaptured) {
	                        throw new AlreadyCapturedException;
                        }
					} else {
						$this->HandleErrorMessage ($response->result->message);
					}

				} catch (Exception $e) {
					$this->HandleErrorMessage ($e->getMessage ());
				}

			}else{
				$this->HandleErrorMessage ($e->getMessage ());
			}

		} catch (IOException $e) {

			$this->HandleErrorMessage ($e->getMessage ());
		}

		return $captureId;

	}

	/**
	 * Handles the error message.
	 * If the debug mode is enabled (in Production) or if we are in Sandbox mode the error message will be shown as it is.
	 * If the debug mode is disabled,and we are in Production a standard error message without any details will be shown to the buyer.
	 * Standard setting of the debug mode is disabled.
	 *
	 * @param string $message
	 *
	 * @return void
	 * @throws Exception
	 */
	private function HandleErrorMessage (string $message): void {

		if($this->usePayPalSandbox || $this->debugMode){
			throw new Exception($message);
		}else{
			throw new Exception($this->standardErrorMessage);
		}
	}

	/**
	 * Checks if the message is a JSON string and if so it will be transformed to an array and returns a formatted string based on the PHP print_r method.
	 *
	 * @param string $message
	 *
	 * @return string
	 */
	private static function IsJson (string $message): string {

		if (str_contains ($message, '{')) {

			$message = json_decode ($message, true);
			$message = "<pre>" . print_r ($message, true) . "</pre>";

		}

		return $message;
	}

}
