﻿<?php

/* To Run this sample you need at least PHP 4 installed. Last version of PHP 5 recomended.
Depending on your PHP version you may need to install the following libaries:
	libxml (included as of PHP 5.1.0)
		SimpleXML extension needs libxml extension and PHP 5. If you have a previous version you will have to work with DOM XML
	libcurl (included as of PHP 4.0.2) (take a look at http://php.net/manual/en/curl.requirements.php for more information about the versions)
*/

// The UDI Auth token identifies the submitting system, and the account that it is submitting to. 
//  These tokens are issues by OMX account administrators, usually filtered to specific IP addresses and APIs to be permitted.
//  The sample token used here is a token for the OMX Test Drive Account - please contact Support with any questions about this account or to request access.
define("TOKEN", "");

function PostRequest($url, $data) {
	
	$error_num = 0;
	$error_desc = "";
	
	$handler = curl_init();
	curl_setopt($handler, CURLOPT_URL, $url);
	curl_setopt($handler, CURLOPT_POST, true);
	curl_setopt($handler, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($handler, CURLOPT_POSTFIELDS, $data); 
	curl_setopt($handler, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($handler, CURLOPT_TIMEOUT, 60);
	curl_setopt($handler, CURLOPT_HTTPHEADER, array (
        "Content-Type: text/xml; charset=utf-8",
        "Content-length: " . strlen($data),
		"Connection: close"
    ));
	$response = curl_exec($handler);
		
	if (curl_errno($handler)) {
		$error_num = curl_errno($handler);
        $error_desc = 'cURL ERROR -> ' . curl_errno($handler) . ': ' . curl_error($handler);
    } else {
        $returnCode = (int)curl_getinfo($handler, CURLINFO_HTTP_CODE);
	
		$error_num = $returnCode;
        switch($returnCode){
            case 200:
				$error_num = 0;
                break;
			case 400:
                $error_desc = 'ERROR -> 400 Bad Request';
                break;
            case 404:
                $error_desc = 'ERROR -> 404 Not Found';
                break;
			case 500:
                $error_desc = 'ERROR -> 500 Internal Server Error';
                break;
            default:
                $error_desc = 'HTTP ERROR -> ' . $returnCode;
                break;
        }
    }	
	
	curl_close($handler);
	
	return array($response, $error_num, $error_desc);
}

function CreateElement($xml, $name, $value, $attr, $attrValue) {
	
    $element = $xml->createElement($name);
    if ($attr != "") {
        $elementAttr = $xml->createAttribute($attr);
        $elementAttrValue = $xml->createTextNode(htmlspecialchars($attrValue, ENT_QUOTES, "UTF-8"));
        $elementAttr->appendChild($elementAttrValue);
        $element->appendChild($elementAttr);
    }
    if ($value != "") {
        $elementValue = $xml->createTextNode(htmlspecialchars($value, ENT_QUOTES, "UTF-8"));
        $element->appendChild($elementValue);
    }
	
    return $element;
}
//xml version="1.0" encoding="UTF-8"
//<UDOARequest version="2.00">
//  <UDIParameter>
//    <Parameter key="UDIAuthToken">YourToken</Parameter>
//    <Parameter key="QueueFlag">True</Parameter>
//    <Parameter key="Keycode">JTESTKEY</Parameter>
//  </UDIParameter>
//  <Header>
//    <StoreCode>WEBSTORE01</StoreCode>
//    <OrderID>003465-A</OrderID>
//    <OrderDate>2003-04-01 22:15:10</OrderDate>
//    <OriginType>3</OriginType>
//  </Header>
//  <Customer>
//    <Address type="BillTo">
//      <TitleCode>0</TitleCode>
//      <Firstname>Bill</Firstname>
//      <Lastname>Thomas</Lastname>
//      <Address1>251 West 30th St</Address1>
//      <Address2>Apt 12E</Address2>
//      <City>New York</City>
//      <State>NY</State>
//      <ZIP>10001</ZIP>
//      <TLD>US</TLD>
//    </Address>
//  </Customer>
//  <ShippingInformation>
//    <MethodCode>0</MethodCode>
//  </ShippingInformation>
//  <Payment type="1">
//    <CardNumber>4111111111111111</CardNumber>
//    <CardExpDateMonth>09</CardExpDateMonth>
//    <CardExpDateYear>2011</CardExpDateYear>
//  </Payment>
//  <OrderDetail>
//    <LineItem>
//      <ItemCode>APPLE</ItemCode>
//      <Quantity>1</Quantity>
//    </LineItem>
//  </OrderDetail>
//</UDOARequest>

// Create the XML Request
$xml = new DOMDocument('1.0', 'UTF-8');
$xml->formatOutput = true;

$root = CreateElement($xml, 'UDOARequest', '', 'version', '2.00');

$udiparam = CreateElement($xml, 'UDIParameter', '', '', '');

//UDI Auth Token - this identifies the system sending the request, and the account that the request is for.
$udiparam->appendChild(CreateElement($xml, 'Parameter', TOKEN, 'key', 'UDIAuthToken'));

//QueueFlag determines whether OMX will Queue any problem orders for manual resolution within the OMX interface, 
// or return the errors to the requesting system and not accept the order.
$udiparam->appendChild(CreateElement($xml, 'Parameter', 'True', 'key', 'QueueFlag'));

//Keycode - this is also known as the Offer Code
$udiparam->appendChild(CreateElement($xml, 'Parameter', 'JTESTKEY', 'key', 'Keycode'));

$root->appendChild($udiparam);

$header = CreateElement($xml, 'Header', '', '', '');

//The StoreCode tells OMX what submitting system the Order ID relates to, so that it can check for duplicate
// submissions (a single order ID, received several times, with the same store code).
$header->appendChild(CreateElement($xml, 'StoreCode', 'WEBSTORE01', '', ''));

//The OrderID is the submitting system's order identifier/reference - this is used to easily locate orders in OMX 
// given the front-end order identifier (eg for customer service calls immediately after the web order is placed),
// and also to detect duplicate submissions of the same order - for the same OrderID and the same StoreCode.
$header->appendChild(CreateElement($xml, 'OrderID', '003465-A', '', ''));

//The date of the customer's order - this may differ substantially from teh submission date, in case of queued submissions.
$header->appendChild(CreateElement($xml, 'OrderDate', '2003-04-01 22:15:10', '', ''));

//The order channel (internet, phone, etc)
$header->appendChild(CreateElement($xml, 'OriginType', '3', '', ''));

$root->appendChild($header);

$customer = CreateElement($xml, 'Customer', '', '', '');
$address = CreateElement($xml, 'Address', '', 'type', 'BillTo');
$address->appendChild(CreateElement($xml, 'TitleCode', '0', '', ''));
$address->appendChild(CreateElement($xml, 'Firstname', 'Bill', '', ''));
$address->appendChild(CreateElement($xml, 'Lastname', 'Thomas', '', ''));
$address->appendChild(CreateElement($xml, 'Address1', '251 West 30th St', '', ''));
$address->appendChild(CreateElement($xml, 'Address2', 'Apt 12E', '', ''));
$address->appendChild(CreateElement($xml, 'City', 'New York', '', ''));
$address->appendChild(CreateElement($xml, 'State', 'NY', '', ''));
$address->appendChild(CreateElement($xml, 'ZIP', '10001', '', ''));
$address->appendChild(CreateElement($xml, 'TLD', 'US', '', ''));
$customer->appendChild($address);
$root->appendChild($customer);

$shippingInfo = CreateElement($xml, 'ShippingInformation', '', '', '');
$methodCode = CreateElement($xml, 'MethodCode', '0', '', '');
$shippingInfo->appendChild($methodCode);
$root->appendChild($shippingInfo);

$paymentType = CreateElement($xml, 'Payment', '', 'type', '1');
$paymentType->appendChild(CreateElement($xml, 'CardNumber', '4111111111111111', '', ''));
$paymentType->appendChild(CreateElement($xml, 'CardExpDateMonth', '09', '', ''));
$paymentType->appendChild(CreateElement($xml, 'CardExpDateYear', '2011', '', ''));
$root->appendChild($paymentType);

$orderDetail = CreateElement($xml, 'OrderDetail', '', '', '');
$lineItem = CreateElement($xml, 'LineItem', '', '', '');
$lineItem->appendChild(CreateElement($xml, 'ItemCode', 'APPLE', '', ''));
$lineItem->appendChild(CreateElement($xml, 'Quantity', '1', '', ''));
$orderDetail->appendChild($lineItem);
$root->appendChild($orderDetail);

$xml->appendChild($root);

// Send the Request to OMX
list($response, $error_num, $error_desc) = PostRequest(
    "https://api.omx.ordermotion.com/hdde/xml/udi.asp",
    $xml->saveXML()
);

// Handle the HTTP errors
if ($error_num != 0) {
    echo $error_desc . "<br \>";
	if ($error_num == 500) {
		// Handle critical OMX errors
		$xml_response = simplexml_load_string($response);
		
		if ($xml_response) {
			if ($xml_response->xpath('ErrorData/Error')){
				foreach ($xml_response->ErrorData[0]->Error as $error_desc)
					echo $error_desc . "<br \>";
			}
			if ($xml_response->xpath('/Result/Reference')){
				echo "PX error reference: " . $xml_response->Reference[0] . "<br \>";
			}
		}
	}
} else {
	$xml_response = simplexml_load_string($response);
	
	if ($xml_response) {
		// Handle the OMX errors
		if ($xml_response->Success[0] == "0") {
			foreach ($xml_response->ErrorData[0]->Error as $error_desc)
				echo "OMX error: " . $error_desc . "<br \>";
		} else {
			// XML processing
			if ($xml_response->xpath('UDOARequest/Header/OrderNumber')){
				echo "The order has been created.<br \>Assigned order number: " . $xml_response->UDOARequest[0]->Header[0]->OrderNumber[0];
				//echo $xml_response->asXML();
			} else {
				echo "The order has been queued.<br \>";
			}
		}
	}
}

?>
