<?php
// Налаштування
$botToken = '';
$openaiApiKey = '';
$botUsername = 'witty_aiBOT'; // Без символу @

// Функція для логування
function logMessage($message) {
    file_put_contents('bot_log.txt', date('Y-m-d H:i:s') . ': ' . $message . "\n", FILE_APPEND);
}

// Функція для обмеження швидкості
function rateLimit() {
    $rateFile = 'rate_limit.txt';
    $maxRequests = 20; // Максимальна кількість запитів
    $perSeconds = 60; // За який період (в секундах)

    if (!file_exists($rateFile)) {
        file_put_contents($rateFile, json_encode([]));
    }

    $requests = json_decode(file_get_contents($rateFile), true);
    $now = time();
    $requests = array_filter($requests, function($time) use ($now, $perSeconds) {
        return $time > $now - $perSeconds;
    });

    if (count($requests) >= $maxRequests) {
        logMessage('Досягнуто ліміту запитів. Очікування...');
        sleep($perSeconds - ($now - min($requests)));
    }

    $requests[] = $now;
    file_put_contents($rateFile, json_encode($requests));
}

// Функція для отримання контексту розмови
function getConversationContext($chatId) {
    $contextFile = "context_$chatId.json";
    logMessage("Спроба отримати контекст з файлу: $contextFile");
    
    if (file_exists($contextFile)) {
        $content = file_get_contents($contextFile);
        logMessage("Вміст файлу контексту: " . $content);
        
        if (!empty($content)) {
            $context = json_decode($content, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($context)) {
                logMessage("Контекст успішно отримано");
                
                // Перевіряємо кожен елемент контексту
                $validContext = array_filter($context, function($item) {
                    return isset($item['role']) && isset($item['content']) && 
                           is_string($item['role']) && is_string($item['content']);
                });
                
                // Якщо після фільтрації контекст порожній, логуємо це
                if (empty($validContext)) {
                    logMessage("Після валідації контекст виявився порожнім");
                    return [];
                }
                
                // Обмежуємо контекст останніми 10 повідомленнями
                $validContext = array_slice($validContext, -30);
                
                logMessage("Валідований контекст: " . json_encode($validContext));
                return $validContext;
            } else {
                logMessage("Помилка при декодуванні JSON або результат не є масивом: " . json_last_error_msg());
            }
        } else {
            logMessage("Файл контексту порожній");
        }
    } else {
        logMessage("Файл контексту не існує");
    }
    
    logMessage("Повертаємо порожній масив контексту");
    return []; // Повертаємо порожній масив, якщо виникла будь-яка помилка
}

// Функція для збереження контексту розмови
function saveConversationContext($chatId, $context) {
    $contextFile = "context_$chatId.json";
    logMessage("Спроба зберегти контекст у файл: $contextFile");
    
    // Перевірка типу $context
    if (is_resource($context)) {
        logMessage("Контекст є ресурсом. Спроба конвертації.");
        $metadata = stream_get_meta_data($context);
        logMessage("Метадані ресурсу: " . print_r($metadata, true));
        
        if ($metadata['seekable']) {
            rewind($context);
        }
        
        $contextContent = stream_get_contents($context);
        if ($contextContent !== false) {
            logMessage("Вміст ресурсу: " . $contextContent);
            $context = json_decode($contextContent, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                logMessage("Помилка декодування JSON: " . json_last_error_msg());
                $context = [];
            }
        } else {
            logMessage("Не вдалося отримати вміст ресурсу");
            $context = [];
        }
    }
    
    if (!is_array($context)) {
        logMessage("Помилка: контекст не є масивом. Тип контексту: " . gettype($context));
        $context = []; // Ініціалізуємо порожній масив, якщо контекст невалідний
    }
    
    // Додаємо детальне логування вмісту контексту
    logMessage("Контекст перед збереженням: " . print_r($context, true));
    
    // Перевіряємо кожен елемент контексту на коректність
    $validContext = [];
    foreach ($context as $key => $item) {
        if (isset($item['role']) && isset($item['content']) && is_string($item['role']) && is_string($item['content'])) {
            $validContext[] = $item;
        } else {
            logMessage("Знайдено некоректний елемент контексту: " . print_r($item, true));
        }
    }
    
    // Перекодовуємо контекст, видаляючи некоректні символи UTF-8
    $validContext = array_map(function($item) {
        $item['content'] = mb_convert_encoding($item['content'], 'UTF-8', 'UTF-8');
        return $item;
    }, $validContext);
    
    $jsonContent = json_encode($validContext, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if ($jsonContent === false) {
        logMessage("Помилка при кодуванні контексту в JSON: " . json_last_error_msg());
        logMessage("Проблемний контекст: " . print_r($validContext, true));
        return;
    }
    
    $result = file_put_contents($contextFile, $jsonContent);
    if ($result === false) {
        logMessage("Помилка при збереженні контексту: не вдалося записати у файл");
    } else {
        logMessage("Контекст успішно збережено. Записано байт: $result");
        logMessage("Збережений контекст: " . $jsonContent);
    }
}

// Функція для отримання відповіді від OpenAI
function getOpenAIResponse($message, $apiKey, $chatId) {
    rateLimit();

    $context = getConversationContext($chatId);
    logMessage("Отриманий контекст: " . json_encode($context));
    
    // Перевіряємо, чи є $context масивом
    if (!is_array($context)) {
        logMessage("Помилка: контекст не є масивом. Тип контексту: " . gettype($context));
        $context = []; // Ініціалізуємо порожній масив, якщо контекст невалідний
    }
    
    // Перевіряємо і очищаємо контекст
    $context = array_filter($context, function($item) {
        return isset($item['role']) && isset($item['content']) && is_string($item['role']) && is_string($item['content']);
    });
    
    $context[] = ['role' => 'user', 'content' => $message];

    $url = 'https://api.openai.com/v1/chat/completions';
    $data = [
        'model' => 'gpt-4o-mini',
        'messages' => array_merge(
            [['role' => 'system', 'content' => 'Ти - дотепний і сучасний асистент який розмовляє українською мовою. Твої відповіді короткі та влучні. Ти спілкуєшся неформально, використовуєш емодзі.']],
            $context
        ),
        'temperature' => 0.8,
        'max_tokens' => 555
    ];

    $options = [
        'http' => [
            'method' => 'POST',
            'header' => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey
            ],
            'content' => json_encode($data)
        ]
    ];

    $streamContext = stream_context_create($options);
    
    logMessage('Відправка запиту до OpenAI API');
    
    try {
        $response = file_get_contents($url, false, $streamContext);
        if ($response === FALSE) {
            logMessage('Помилка при отриманні відповіді від OpenAI API: ' . error_get_last()['message']);
            return 'Вибач, не вдалося отримати відповідь від мого мозку. Може, спробуємо ще раз? 🤖🔧';
        }
        
        logMessage('Отримано відповідь від OpenAI API: ' . $response);
        $responseData = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            logMessage('Помилка при декодуванні JSON відповіді: ' . json_last_error_msg());
            return 'Упс, здається, мій перекладач з машинної мови зламався. Давай ще раз! 😅';
        }

        if (isset($responseData['choices'][0]['message']['content'])) {
            $reply = $responseData['choices'][0]['message']['content'];
            $context[] = ['role' => 'assistant', 'content' => $reply];
            saveConversationContext($chatId, $context);
            return $reply;
        } else {
            logMessage('Неочікувана структура відповіді від OpenAI: ' . print_r($responseData, true));
            return 'Хм, здається, моя відповідь загубилася десь у нетрях інтернету. Спробуймо ще раз! 🕵️‍♂️';
        }
    } catch (Exception $e) {
        logMessage('Виникла помилка при запиті до OpenAI: ' . $e->getMessage());
        return 'Ой, щось пішло не так у моїх електронних звивинах. Давай спробуємо ще раз пізніше? 🤔';
    }
}


// Функція для відправки повідомлення в Telegram
function sendTelegramMessage($chatId, $message, $botToken, $replyToMessageId = null) {
    $url = "https://api.telegram.org/bot$botToken/sendMessage";
    $data = [
        'chat_id' => $chatId,
        'text' => $message,
        'parse_mode' => 'Markdown'
    ];

    if ($replyToMessageId !== null) {
        $data['reply_to_message_id'] = $replyToMessageId;
    }

    $options = [
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
            'content' => http_build_query($data)
        ]
    ];

    $context = stream_context_create($options);
    
    logMessage('Відправка повідомлення до Telegram');
    
    $response = file_get_contents($url, false, $context);
    
    logMessage('Відповідь від Telegram API: ' . $response);
}

// Функція перевірки прав бота
function checkBotPermissions($chatId, $botToken) {
    $url = "https://api.telegram.org/bot$botToken/getChatMember";
    $data = [
        'chat_id' => $chatId,
        'user_id' => explode(':', $botToken)[0] // Bot's user ID is the first part of the token
    ];

    $options = [
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
            'content' => http_build_query($data)
        ]
    ];

    $context = stream_context_create($options);
    $response = file_get_contents($url, false, $context);
    $responseData = json_decode($response, true);

    if ($responseData['ok']) {
        $status = $responseData['result']['status'];
        $permissions = $responseData['result']['can_send_messages'] ?? false;

        logMessage("Статус бота в групі: $status");
        logMessage("Право на відправку повідомлень: " . ($permissions ? "Так" : "Ні"));

        if ($status == 'administrator') {
            logMessage("Бот є адміністратором групи і має всі необхідні права");
            return true;
        } elseif ($status == 'member' && $permissions) {
            logMessage("Бот є учасником групи і має право відправляти повідомлення");
            return true;
        } else {
            logMessage("Бот не має необхідних прав у групі");
            return false;
        }
    } else {
        logMessage("Помилка при перевірці прав бота: " . $responseData['description']);
        return false;
    }
}

// Обробка вхідного повідомлення
$update = json_decode(file_get_contents('php://input'), true);

logMessage('Отримано оновлення: ' . json_encode($update, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

if (isset($update['message'])) {
    $message = $update['message']['text'] ?? '';
    $chatId = $update['message']['chat']['id'];
    $messageId = $update['message']['message_id'];
    $chatType = $update['message']['chat']['type'];
    $fromUsername = $update['message']['from']['username'] ?? 'Unknown';

    logMessage("Отримано повідомлення: '$message'");
    logMessage("Тип чату: $chatType");
    logMessage("Від користувача: @$fromUsername");
    logMessage("ID чату: $chatId");
    logMessage("ID повідомлення: $messageId");

    $shouldReply = false;
    $replyToMessageId = null;

    // Перевірка, чи це особистий чат або звернення до бота в груповому чаті
    if ($chatType === 'private') {
        $shouldReply = true;
        logMessage("Приватний чат, бот відповідатиме");
    } elseif ($chatType === 'group' || $chatType === 'supergroup') {
        logMessage("Груповий чат, перевіряємо звернення до бота");
        logMessage("Поточний botUsername: @$botUsername");
        
        // Перевірка прав бота
        $hasPermissions = checkBotPermissions($chatId, $botToken);
        if (!$hasPermissions) {
            logMessage("Бот не має необхідних прав у групі $chatId");
            return; // Припинити обробку повідомлення
        }
        
        // Перевірка, чи повідомлення починається з username бота
        if (preg_match("/^@" . preg_quote($botUsername, '/') . '\b/i', $message)) {
            $shouldReply = true;
            $message = preg_replace("/^@" . preg_quote($botUsername, '/') . '\s*/i', '', $message);
            logMessage("Знайдено звернення до бота за username. Оброблене повідомлення: '$message'");
        }
      // Перевірка, чи це відповідь на повідомлення бота
        elseif (isset($update['message']['reply_to_message']['from']['username'])) {
            $repliedToUsername = strtolower($update['message']['reply_to_message']['from']['username']);
            logMessage("Відповідь на повідомлення користувача: @$repliedToUsername");
            if ($repliedToUsername === strtolower($botUsername)) {
                $shouldReply = true;
                $replyToMessageId = $messageId;
                logMessage("Знайдено відповідь на повідомлення бота");
            }
        }
    }

    logMessage("shouldReply: " . ($shouldReply ? "true" : "false"));

    if ($shouldReply) {
        if ($message === '/start') {
            $reply = "Привіт! Я твій кумедний AI-помічник. Давай почнемо! 😎";
        } else {
            $reply = getOpenAIResponse($message, $openaiApiKey, $chatId);
        }

        logMessage("Відправляємо відповідь: $reply");
        sendTelegramMessage($chatId, $reply, $botToken, $replyToMessageId);
    } else {
        logMessage("Бот не відповідатиме на це повідомлення");
    }
} else {
    logMessage('Повідомлення не знайдено в оновленні');
}