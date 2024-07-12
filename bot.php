<?php

use Dotenv\Dotenv;
use Twilio\Rest\Client;
use GuzzleHttp\Exception\RequestException;

require 'vendor/autoload.php';
require 'gemini.php';

$dotenv = Dotenv::createUnsafeImmutable(__DIR__);
$dotenv->load();

function listenToWhatsAppReplies($request)
{
    $from = $request['From'];
    $body = $request['Body'];
    $mediaUrl = isset($request['MediaUrl0']) ? $request['MediaUrl0'] : null;
    $mimeType = isset($request['MediaContentType0']) ? $request['MediaContentType0'] : null;

    try {
        if ($mediaUrl) {
            $message = generateContentFromGemini($body, $mediaUrl, $mimeType);
            sendWhatsAppMessage($message, $from);
        } else {
            $message = generateContentFromGemini($body);
            sendWhatsAppMessage($message, $from);
        }
    } catch (RequestException $e) {
        sendWhatsAppMessage($e->getMessage(), $from);
    }
}

function sendWhatsAppMessage($message, $recipient)
{
    $twilio_whatsapp_number = getenv('TWILIO_WHATSAPP_NUMBER');
    $account_sid = getenv("TWILIO_SID");
    $auth_token = getenv("TWILIO_AUTH_TOKEN");

    $client = new Client($account_sid, $auth_token);

    return $client->messages->create("$recipient", [
        'from' => "whatsapp:$twilio_whatsapp_number",
        'body' => $message
    ]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request = $_POST;
    listenToWhatsAppReplies($request);
    http_response_code(200);
    echo 'Message processed';
} else {
    http_response_code(405);
    echo 'Method not allowed';
}
