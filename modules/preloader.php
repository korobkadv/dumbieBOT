<?php
function preloader($chatId, $commandFunction) {

    // 1. Відправляємо повідомлення про завантаження
    $loadingMessage = json_decode(sendMessage($chatId, '⌛ Зачекайте, ваш запит виконується...', 'text'), true);
    
    try {
        // 2. Виконуємо основну команду
        $commandFunction();

        // 3. Видаляємо повідомлення з результатом
        deleteMessage($chatId, $loadingMessage['result']['message_id']);
    } catch (Exception $e) {
        // У разі помилки оновлюємо повідомлення відповідним текстом
        editMessageText($chatId, $loadingMessage['result']['message_id'], '😅 Виникла помилка');
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
