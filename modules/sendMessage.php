<?php

function sendMessage($chatId, $message, $method, $keyboard = null, $forceNoParseMode = false) {
    $telegramToken = BOT_TOKEN;
    $methodURL = 'sendMessage';
    $requestParam = 'text'; // Параметр для тексту або URL медіа

    if ($method === 'photo') {
        $methodURL = 'sendPhoto';
        $requestParam = 'photo';
    } elseif ($method === 'video') {
        $methodURL = 'sendVideo';
        $requestParam = 'video';
    }

    $url = API_URL . $methodURL;

    $postData = [
        'chat_id' => $chatId,
        $requestParam => $message, 
    ];

    $contentType = 'application/x-www-form-urlencoded'; // За замовчуванням
    $content = http_build_query($postData); // За замовчуванням
    $parseModeSet = false; // DEBUG

    // Змінюємо parse_mode та кодування ТІЛЬКИ якщо метод 'text' І $forceNoParseMode = false
    if ($method === 'text' && !$forceNoParseMode) {
        $postData['parse_mode'] = 'MarkdownV2'; 
        $contentType = 'application/json';
        $content = json_encode($postData);
        $parseModeSet = true; // DEBUG
    } else {
        
    }

    if ($keyboard) {
        // Якщо контент - JSON, додаємо reply_markup до масиву перед кодуванням
        if ($contentType === 'application/json') {
            $postData['reply_markup'] = $keyboard; // Клавіатура вже має бути масивом PHP
            $content = json_encode($postData); // Перекодовуємо JSON з клавіатурою
        } else {
            // Для form-urlencoded додаємо як рядок (якщо клавіатура була рядком)
            // АБО потрібно переконатися, що клавіатура передається як рядок JSON сюди
             if (is_array($keyboard)) {
                 $keyboardJson = json_encode($keyboard);
             } else {
                 $keyboardJson = $keyboard; // Припускаємо, що це вже JSON рядок
             }
             // Додаємо до рядка запиту (http_build_query вже було викликано)
             $content .= '&reply_markup=' . urlencode($keyboardJson);
        }
        // Важливо: Якщо $contentType змінився на json вище, $content треба перекодувати
        if ($parseModeSet && is_array($keyboard)) {
             $postData['reply_markup'] = $keyboard;
             $content = json_encode($postData);
        }
    }

    $options = [
        'http' => [
            'header'  => "Content-type: " . $contentType . "\r\n", // Використовуємо визначений тип
            'method'  => 'POST',
            'content' => $content, // Використовуємо відформатований контент
            'ignore_errors' => true 
        ],
    ];
    $context  = stream_context_create($options);
    $response = @file_get_contents($url, false, $context);
    
    $responseData = json_decode($response, true);
    if (!$responseData || !$responseData['ok']) {
        error_log("Telegram API Error: " . $response);
    }
    
    return $response; // Повертаємо відповідь для можливої подальшої обробки
}

/**
 * Надсилає відповідь на callback query.
 * Використовується, щоб прибрати годинник з кнопки в Telegram.
 *
 * @param string $callbackQueryId ID callback-запиту.
 * @param string|null $text Текст повідомлення (необов'язково).
 * @param bool $showAlert Показувати як спливаюче вікно (необов'язково).
 * @return string|false Відповідь від Telegram API або false у разі помилки.
 */
function answerCallbackQuery($callbackQueryId, $text = null, $showAlert = false) {
    $url = API_URL . 'answerCallbackQuery';

    $postData = [
        'callback_query_id' => $callbackQueryId,
    ];

    if ($text !== null) {
        $postData['text'] = $text;
    }
    if ($showAlert) {
        $postData['show_alert'] = true;
    }

    $options = [
        'http' => [
            'method'  => 'POST',
            'header'  => "Content-type: application/json\r\n", // Надсилаємо як JSON
            'content' => json_encode($postData),
            'ignore_errors' => true 
        ],
    ];
    $context  = stream_context_create($options);
    $response = @file_get_contents($url, false, $context);
    
    // Логування помилок
    $responseData = json_decode($response, true);
    if (!$responseData || !$responseData['ok']) {
        error_log("Telegram API Error (answerCallbackQuery): " . $response);
    }
    
    return $response;
}

/**
 * Надсилає дію "typing" у чат.
 *
 * @param int|string $chatId ID чату.
 * @return string|false Відповідь від Telegram API або false у разі помилки.
 */
function sendChatTyping($chatId) {
    $url = API_URL . 'sendChatAction';
    $postData = [
        'chat_id' => $chatId,
        'action' => 'typing',
    ];

    $options = [
        'http' => [
            'method'  => 'POST',
            'header'  => "Content-type: application/json\r\n",
            'content' => json_encode($postData),
            'ignore_errors' => true 
        ],
    ];
    $context  = stream_context_create($options);
    // Ми не логуємо помилку тут, бо це не критично, якщо індикатор не покажеться
    // Але можна додати логування за бажанням
    return @file_get_contents($url, false, $context); 
}
