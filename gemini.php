<?php
require 'vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Dotenv\Dotenv;

$dotenv = Dotenv::createUnsafeImmutable(__DIR__);
$dotenv->load();

function generateContentFromGemini($text, $fileUri = null, $mimeType = null)
{
    $location = 'us-central1';
    $modelId = 'gemini-1.0-pro-vision-001';

    $accessToken = getenv('GCLOUD_ACCESS_TOKEN');
    $projectId = getenv('GCLOUD_PROJECT_ID');

    $url = "https://{$location}-aiplatform.googleapis.com/v1/projects/{$projectId}/locations/{$location}/publishers/google/models/{$modelId}:generateContent";

    $client = new Client();

    $parts = [];

    if ($fileUri && $mimeType) {
        $imageData = file_get_contents($fileUri);
        $base64Image = base64_encode($imageData);
        $parts[] = [
            "inline_data" => [
                "mimeType" => $mimeType,
                "data" => $base64Image
            ]
        ];
    }

    if ($text) {
        $parts[] = [
            "text" => $text
        ];
    }

    $body = [
        "contents" => [
            [
                "role" => "user",
                "parts" => $parts,
            ],
            [
                "role" => "model",
                "parts" => [
                    "text" => "You are Automate Inc. Bot, an expert in all things automation. Your responses must be concise. Refer to the user as 'Champ' in every interaction."
                ],
            ]
        ],
    ];

    try {
        $response = $client->post($url, [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $accessToken
            ],
            'json' => $body
        ]);

        $data = json_decode($response->getBody(), true);

        // Extract the text
        if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            return $data['candidates'][0]['content']['parts'][0]['text'];
        } else {
            return "No text found in the response.";
        }
    } catch (RequestException $e) {
        return $e->getMessage() . ($e->hasResponse() ? $e->getResponse()->getBody()->getContents() : '');
    }
}
