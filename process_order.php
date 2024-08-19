<?php
require 'vendor/autoload.php';

use setasign\Fpdi\Fpdi;
use Dompdf\Dompdf;

// API settings
$user = 'frits@test.qlsnet.nl';
$password = '4QJW9yh94PbTcpJGdKz6egwH';
$companyId = '9e606e6b-44a4-4a4e-a309-cc70ddd3a103';
$brandId = 'e41c8d26-bdfd-4999-9086-e5939d67ae28';
$productId = 2;
$productCombinationId = 3;

// Retrieve order data from POST request
$orderNumber = $_POST['order']; // Ensure this is securely handled in a real-world application

// Make API call to create shipment
$ch = curl_init("https://api.pakketdienstqls.nl/company/{$companyId}/shipment/create");
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'brand_id' => $brandId,
    'reference' => $orderNumber,
    'weight' => 1000,
    'product_id' => $productId,
    'product_combination_id' => $productCombinationId,
    'cod_amount' => 0,
    'piece_total' => 1,
    'receiver_contact' => [
        'companyname' => '',
        'name' => 'John Doe',
        'street' => 'Daltonstraat',
        'housenumber' => '65',
        'postalcode' => '3316GD',
        'locality' => 'Dordrecht',
        'country' => 'NL',
        'email' => 'email@example.com',
    ]
]));
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_USERPWD, $user . ':' . $password);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

// Execute the API request and check for errors
$response = curl_exec($ch);
if (curl_errno($ch)) {
    die('Curl error: ' . curl_error($ch));
}
curl_close($ch);

// Decode the API response
$shipment = json_decode($response, true);

// Process shipment data
if (isset($shipment['data']['labels']['a4']['offset_0'])) {
    // Get the URL for the A4 label
    $labelUrl = $shipment['data']['labels']['a4']['offset_0'];

    // Download the label PDF
    $labelPdfContent = file_get_contents($labelUrl);
    if ($labelPdfContent === false) {
        die('Failed to download label PDF');
    }

    // Save the label PDF file
    $labelPdfPath = __DIR__ . '/label.pdf';
    if (file_put_contents($labelPdfPath, $labelPdfContent) === false) {
        die('Failed to save label PDF');
    }
} else {
    die('Label URL not found in shipment response');
}

// Create HTML for the packing slip
$orderDetails = "
<h1>Packing Slip</h1>
<p>Order Number: {$orderNumber}</p>
<p>Name: John Doe</p>
<p>Address: Daltonstraat 65</p>
<p>Postal Code: 3316GD</p>
<p>City: Dordrecht</p>
<h2>Order Details:</h2>
<ul>
    <li>2 x Jeans - Black - 36 (SKU: 69205)</li>
    <li>1 x Scarf - Red Orange (SKU: 25920)</li>
</ul>
";

// Generate a PDF from the packing slip HTML
$dompdf = new Dompdf();
$dompdf->loadHtml($orderDetails);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Save the packing slip PDF file
$pakbonPdfPath = __DIR__ . '/pakbon.pdf';
if (file_put_contents($pakbonPdfPath, $dompdf->output()) === false) {
    die('Failed to save packing slip PDF');
}

// Instantiate FPDI for PDF merging
$pdf = new Fpdi();
$pdf->AddPage();
$pdf->SetAutoPageBreak(false);

// Add the label PDF to the new PDF
$pdf->setSourceFile($labelPdfPath);
$labelTplIdx = $pdf->ImportPage(1);
$labelSize = $pdf->getTemplateSize($labelTplIdx);
$labelWidth = $labelSize['width'];
$labelHeight = $labelSize['height'];

// Scale the label to fit the A4 page width
$labelScale = 210 / $labelWidth; // Scale to fit A4 width
$scaledLabelHeight = $labelHeight * $labelScale;
$pdf->useTemplate($labelTplIdx, 0, 297 - $scaledLabelHeight, 210, $scaledLabelHeight, true); // Place the label at the top of the page

// Add the packing slip PDF to the same page
$pdf->setSourceFile($pakbonPdfPath);
$pakbonTplIdx = $pdf->ImportPage(1);
$pakbonSize = $pdf->getTemplateSize($pakbonTplIdx);
$pakbonWidth = $pakbonSize['width'];
$pakbonHeight = $pakbonSize['height'];

// Scale the packing slip to fit the A4 page width
$pakbonScale = 210 / $pakbonWidth; // Scale to fit A4 width
$scaledPakbonHeight = $pakbonHeight * $pakbonScale;

// Determine the vertical position of the packing slip (below the label)
$pakbonY = 450 - $scaledPakbonHeight - 10; // 10mm space between label and packing slip

// Add the packing slip to the page
$pdf->useTemplate($pakbonTplIdx, 0, $pakbonY, 210, $scaledPakbonHeight, true);

// Save the combined PDF file
$combinedPdfPath = __DIR__ . '/combined.pdf';
if ($pdf->Output($combinedPdfPath, 'F') === false) {
    die('Failed to save combined PDF');
}
?>
