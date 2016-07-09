<?php

/* To Run this sample you need at least PHP 4 installed. Last version of PHP 5 recomended.
Depending on your PHP version you may need to install the following libaries:
	libxml (included as of PHP 5.1.0)
		SimpleXML extension needs libxml extension and PHP 5. If you have a previous version you will have to work with DOM XML
	libcurl (included as of PHP 4.0.2) (take a look at http://php.net/manual/en/curl.requirements.php for more information about the versions)
*/

// The UDI Auth token identifies the submitting system, and the account that it is submitting to. 
//  These tokens are issues by OMX account administrators, usually filtered to specific IP addresses and APIs to be permitted.
//  The sample token used here is a token for the OMX Test Drive Account - please contact Support with any questions about this account or to request access.
define("TOKEN", "28e3d4080905804a5b0bbf103e8abb44626c580d39ea0b86f0442d0ad9e0e22ee11f465acd2924fc03c3904d770902608ddd08dae5daccfd44b30dead04c1408ba703b6b54c2c3424f86f905021f204d0d09c1e08828c45cc74e674c271c05313042ee0b2f10ec8dad2d21e801ca87af0dd5b04cec0b1600e695e6789989e11");

function PostRequest($url, $data) {
	echo $data; exit;
	$error_num = 0;
	$error_desc = "";
	
	$handler = curl_init();
	curl_setopt($handler, CURLOPT_URL, $url);
	curl_setopt($handler, CURLOPT_POST, true);
	curl_setopt($handler, CURLOPT_SSL_VERIFYPEER, true);
	curl_setopt($handler, CURLOPT_POSTFIELDS, $data); 
	curl_setopt($handler, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($handler, CURLOPT_TIMEOUT, 60);
	
	$response = curl_exec($handler);
	if(empty($response)) {
		echo 'Sorry';
	}
	
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
/* <ReturnHistoryRequest version="1.00">
<UDIParameter>
<Parameter key="UDIAuthToken">TestToken</Parameter>
<Parameter key="StartDate">2006-01-01 14:34:00</Parameter>
<Parameter key="EndDate">2006-02-01 14:33:59</Parameter>
</UDIParameter>
</ReturnHistoryRequest> */

$myXMLData = 
"<?xml version='1.0' encoding='UTF-8'?>
<ReturnHistoryRequest version='2.00'>
<UDIParameter>
<Parameter key='UDIAuthToken'>".TOKEN."</Parameter>
<Parameter key='StartDate'>2006-01-01 14:34:00</Parameter>
<Parameter key='EndDate'>2017-02-01 14:33:59</Parameter>
</UDIParameter>
</ReturnHistoryRequest>";


// Create the XML Request
$xml = new DOMDocument('1.0', 'UTF-8');
$xml->formatOutput = true;

$root = CreateElement($xml, 'ReturnHistoryRequest', '', 'version', '2.00');

$udiparam = CreateElement($xml, 'UDIParameter', '', '', '');

//UDI Auth Token - this identifies the system sending the request, and the account that the request is for.
$udiparam->appendChild(CreateElement($xml, 'Parameter', TOKEN, 'key', 'UDIAuthToken'));

//QueueFlag determines whether OMX will Queue any problem orders for manual resolution within the OMX interface, 
// or return the errors to the requesting system and not accept the order.
$udiparam->appendChild(CreateElement($xml, 'Parameter', '2006-01-01 14:34:00', 'key', 'StartDate'));

//Keycode - this is also known as the Offer Code
$udiparam->appendChild(CreateElement($xml, 'Parameter', '2006-02-01 14:33:59', 'key', 'EndDate'));

$root->appendChild($udiparam);

// Send the Request to OMX
list($response, $error_num, $error_desc) = PostRequest(
    "https://api.omx.ordermotion.com/hdde/xml/udi.asp",
    $myXMLData
);

// Handle the HTTP errors
if ($error_num != 0) {
	print_r($response); exit;
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
