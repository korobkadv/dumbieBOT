<?php

function sendMessage($chatId, $message, $method, $keyboard = null) {
    $telegramToken = BOT_TOKEN;
    $methodURL = 'sendMessage';

    if ($method === 'photo') {
        $methodURL = 'sendPhoto';
    } elseif ($method === 'video') {
        $methodURL = 'sendVideo';
    }

    $url = API_URL . $methodURL;

    $postData = [
        'chat_id' => $chatId,
        $method => $message,
    ];

    if ($keyboard) {
        $postData['reply_markup'] = json_encode($keyboard);
    }

    $options = [
        'http' => [
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($postData),
        ],
    ];
    $context  = stream_context_create($options);
    return file_get_contents($url, false, $context);
}
