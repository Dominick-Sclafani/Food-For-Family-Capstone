<?php
session_start();
require_once('../db.php');

// Your Gemini API key
$api_key = 'AIzaSyDFp1Y-QYsY73KS6PKCuGWC2m0rCINW8c0';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $userMessage = $_POST['message'];

    // System message to set the context
    $systemMessage = "You are a helpful assistant for the Food For Family website. Your role is to help users navigate the site, find meals, become chefs, post meals, and manage their accounts. Keep your responses concise and focused on the website's functionality. If you're unsure about something, suggest contacting support.";

    // Prepare the API request
    $data = [
        'contents' => [
            [
                'parts' => [
                    ['text' => $systemMessage . "\n\n" . $userMessage]
                ]
            ]
        ]
    ];

    // Initialize cURL
    $ch = curl_init();

    // Set cURL options
    curl_setopt_array($ch, [
        CURLOPT_URL => 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . $api_key,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json'
        ],
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => true
    ]);

    // Execute the request
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($httpCode === 200 && $response) {
        $responseData = json_decode($response, true);
        if (isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
            echo $responseData['candidates'][0]['content']['parts'][0]['text'];
        } else {
            echo "I'm sorry, I couldn't process your request. Please try again.";
        }
    } else {
        if ($httpCode === 404) {
            echo "Error: The service is currently unavailable. Please try again later.";
        } else {
            echo "Error: Unable to process your request. Please try again later.";
        }
    }
    exit;
}
?>