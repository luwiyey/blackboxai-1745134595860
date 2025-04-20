<?php
class Captcha {
    private $secretKey;

    public function __construct() {
        // Use Google reCAPTCHA v2 or v3 secret key from config or environment
        $this->secretKey = 'your-google-recaptcha-secret-key';
    }

    public function verifyResponse($responseToken, $remoteIp = null) {
        $url = 'https://www.google.com/recaptcha/api/siteverify';
        $data = [
            'secret' => $this->secretKey,
            'response' => $responseToken,
        ];
        if ($remoteIp) {
            $data['remoteip'] = $remoteIp;
        }

        $options = [
            'http' => [
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'method'  => 'POST',
                'content' => http_build_query($data),
                'timeout' => 10,
            ],
        ];
        $context  = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        if ($result === false) {
            return false;
        }
        $resultJson = json_decode($result, true);
        return $resultJson['success'] ?? false;
    }
}
?>
