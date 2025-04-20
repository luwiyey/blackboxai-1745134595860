<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

class QRCodeGenerator {
    /**
     * Generate a QR code PNG image for a given text (e.g., book URL or ID)
     * Returns the PNG image data as a base64 encoded string
     */
    public static function generateBase64QRCode($text) {
        try {
            $writer = new PngWriter();
            $qrCode = QrCode::create($text)
                ->setSize(300)
                ->setMargin(10);

            $result = $writer->write($qrCode);
            $dataUri = $result->getDataUri();
            return $dataUri;
        } catch (Exception $e) {
            error_log("Error generating QR code: " . $e->getMessage());
            return null;
        }
    }
}
?>
