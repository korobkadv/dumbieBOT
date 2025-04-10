<?php
require_once 'sendMessage.php'; // Переконайтесь, що цей файл підключено

function preloader($chatId, $callback, $message = "⏳ Завантаження...") {
    // Надсилаємо повідомлення без parse_mode
    $response = sendMessage($chatId, $message, 'text', null, true);
    
    $responseData = json_decode($response, true);
    $messageId = $responseData['result']['message_id'] ?? null;

    if ($messageId) {
        try {
            $callback(); // Виконуємо основну дію
        } finally {
            // Видаляємо повідомлення прелоадера
            $deleteUrl = API_URL . 'deleteMessage';
            $postData = ['chat_id' => $chatId, 'message_id' => $messageId];
            $options = [
                'http' => [
                    'method' => 'POST',
                    'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                    'content' => http_build_query($postData),
                    'ignore_errors' => true
                ]
            ];
            $context = stream_context_create($options);
            @file_get_contents($deleteUrl, false, $context);
        }
    } else {
        error_log("Preloader: Failed to get message_id for preloader message.");
        // Якщо не вдалося надіслати прелоадер, все одно виконуємо дію
        $callback();
    }
}

function deleteMessage($chatId, $messageId) {
    $telegramToken = BOT_TOKEN;



    $url = "https://api.telegram.org/bot$telegramToken/deleteMessage";
    $postData = [
        'chat_id' => $chatId,
        'message_id' => $messageId,
    ];

    $options = [
        'http' => [
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($postData),
        ],
    ];

    $context = stream_context_create($options);
    file_get_contents($url, false, $context);
}

function editMessageText($chatId, $messageId, $newText) {
    $telegramToken = BOT_TOKEN;

    $url = "https://api.telegram.org/bot$telegramToken/editMessageText";
    $postData = [
        'chat_id' => $chatId,
        'message_id' => $messageId,
        'text' => $newText,
    ];

    $options = [
        'http' => [
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($postData),
        ],
    ];

    $context = stream_context_create($options);
    file_get_contents($url, false, $context);
}
