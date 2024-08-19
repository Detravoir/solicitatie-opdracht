<?php

/**
 * MyQLS API docs: https://api.pakketdienstqls.nl/swagger/
 *
 * Onderstaande data is de representatie van een bestelling. In de praktijk zal dit uit een
 * webshop API (van onze klanten) komen.
 */
$order = [
   'number' => '#958201',
   'billing_address' => [
      'companyname' => null,
      'name' => 'John Doe',
      'street' => 'Daltonstraat',
      'housenumber' => '65',
      'address_line_2' => '',
      'zipcode' => '3316GD',
      'city' => 'Dordrecht',
      'country' => 'NL',
      'email' => 'email@example.com',
      'phone' => '0101234567',
   ],
   'delivery_address' => [
      'companyname' => '',
      'name' => 'John Doe',
      'street' => 'Daltonstraat',
      'housenumber' => '65',
      'address_line_2' => '',
      'zipcode' => '3316GD',
      'city' => 'Dordrecht',
      'country' => 'NL',
   ],
   'order_lines' => [
      [
         'amount_ordered' => 2,
         'name' => 'Jeans - Black - 36',
         'sku' => 69205,
         'ean' =>  '8710552295268',
      ],
      [
         'amount_ordered' => 1,
         'name' => 'Sjaal - Rood Oranje',
         'sku' => 25920,
         'ean' =>  '3059943009097',
      ]
   ]
];

/**
 * User en password om onze API te kunnen gebruiken
 */
$user = 'frits@test.qlsnet.nl';
$password = '4QJW9yh94PbTcpJGdKz6egwH';

/**
 * Een company vertegenwoordigd onze klant en kan meerdere brands (merken/handelsnamen)
 * hebben, voor nu kun je deze waarden gebruiken:
 */
$companyId = '9e606e6b-44a4-4a4e-a309-cc70ddd3a103';
$brandId = 'e41c8d26-bdfd-4999-9086-e5939d67ae28';

/**
 * Om een vervoerder en vervoersmethode te kiezen heeft een zending een verzendproduct
 * nodig. Via het `Products` endpoint kunnen de mogelijke verzendproducten voor de klant
 * opgehaald worden.
 *
 * GET @ https://api.pakketdienstqls.nl/company/{$companyId}/product
 *
 * Laat de gebruiker het verzendproduct (bijv. DHL Pakje, DHL Brievenbuspakje) kiezen, of
 * gebruik hardcoded "DHL Pakje (NL)" (productId = 2). Laat de gebruiker de verzendproduct
 * opties (combinatie) kiezen (bijv. DHL Pakje zonder opties, met handtekening, geen
 * levering bij buren, DHL Brievenbuspakje zonder opties), of gebruik hardcoded
 * "DHL Pakje (NL) without delivery options" (productCombinationId = 3).
 */
$productId = 2;
$productCombinationId = 3;

/**
 * Hier is een voorbeeld request om een shipment aan te maken (voel je vrij om dit op je
 * eigen manier in je eigen framework te doen):
 */
$ch = curl_init("https://api.pakketdienstqls.nl/company/{$companyId}/shipment/create");
curl_setopt($ch, CURLOPT_HTTPHEADER, [
	'Accept: application/json',
	'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
	'brand_id' => $brandId,
	'reference' => $order['number'],
	'weight' => 1000,
	'product_id' => $productId,
	'product_combination_id' => $productCombinationId,
	'cod_amount' => 0,
	'piece_total' => 1,
	'receiver_contact' => [
		'companyname' => $order['delivery_address']['companyname'],
		'name' => $order['delivery_address']['name'],
		'street' => $order['delivery_address']['street'],
		'housenumber' => $order['delivery_address']['housenumber'],
        'postalcode' => $order['delivery_address']['zipcode'],
		'locality' => $order['delivery_address']['city'],
		'country' => $order['delivery_address']['country'],
		'email' => $order['billing_address']['email'],
	]
]));
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_USERPWD, $user . ':' . $password);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
curl_close($ch);

echo '<pre>';
print_r($response);


// Tip: Convert pdf label to image, om makkelijk te gebruiken in de volgende stap:
// TODO: Generate packing slip (pakbon) en voeg het verzendlabel toe aan de pakbon.

// Succes!
