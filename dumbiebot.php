<?php
define('APP_ENV', true);

require_once './config.php';
require_once './modules/commands.php';
require_once './helpers/randomIndex.php';
require_once './helpers/escapeMarkdownV2.php';
require_once './modules/sendMessage.php';
require_once './modules/preloader.php';
require_once './modules/imageCommand.php';
require_once './modules/topMoviesCommand.php';
require_once './modules/popularMoviesCommand.php';
require_once './modules/topAnimeCommand.php';
require_once './modules/randomAnimeCommand.php';
require_once './modules/quoteCommand.php';
require_once './modules/musicVideoCommand.php';
require_once './modules/funnyVideoCommand.php';
require_once './modules/game/millionaire.php';
require_once './modules/actionCommand.php';
require_once './modules/game/trueOrFalseGame.php';


$userStates = [];



function processMessage($message) {
    $chatId = $message['chat']['id'];
    $text = $message['text'] ?? '';

    // Перевірка типу чату
    $chat_type = $message['chat']['type'];
    $bot_username = 'dumbieBOT';
    
    if ($chat_type === 'group' || $chat_type === 'supergroup') {
        if (strpos($text, '@'.$bot_username) === false) {
            return;
        } else {
            $text = trim(str_replace('@'.$bot_username, '', $text));
        }
    }

    // Обробка команд
    if (strpos($text, "/image") === 0) {
        // Надсилаємо клавіатуру з категоріями
        $keyboard = [
            'inline_keyboard' => [
                // Рядок 1
                [
                    ['text' => '🎲 Випадкове', 'callback_data' => 'img_cat|random'],
                    ['text' => '🏞️ Пейзаж', 'callback_data' => 'img_cat|nature'], // Pixabay любить 'nature'
                    ['text' => '🏙️ Місто', 'callback_data' => 'img_cat|buildings'] // Або 'city'
                ],
                 // Рядок 2
                [
                    ['text' => '🐾 Тварини', 'callback_data' => 'img_cat|animals'],
                    ['text' => '🍔 Їжа', 'callback_data' => 'img_cat|food'],
                    ['text' => '🧑‍💻 Технології', 'callback_data' => 'img_cat|computer'] // Або 'technology'
                ],
                 // Рядок 3
                [
                    ['text' => '🚗 Авто', 'callback_data' => 'img_cat|transportation'],
                    ['text' => '🌸 Квіти', 'callback_data' => 'img_cat|flowers'], // Може не бути такої категорії, краще 'nature'?
                    ['text' => '⚽ Спорт', 'callback_data' => 'img_cat|sports']
                ]
                // Можна додати ще
            ]
        ];
        sendMessage($chatId, escapeMarkdownV2("🖼️ Оберіть категорію зображення:"), 'text', $keyboard);
    } elseif (strpos($text, "/top_movie") === 0) {
        sendChatTyping($chatId);
        preloader($chatId, function() use ($chatId) {
            $movieData = topMovieCommand($chatId);
            if ($movieData['type'] === 'movie_data') {
                $title = $movieData['title'] ?? 'Назва невідома'; 
                $year = $movieData['release_year'] ?? '';
                $rating = $movieData['vote_average'] ?? '-';
                $posterPath = $movieData['poster_path'] ?? null;
                
                // Формуємо текст БЕЗ зірочок
                $messageText = "{$title} ({$year})\n⭐ {$rating}";
                
                // Все одно екрануємо інші потенційні спецсимволи
                $escapedMessageText = escapeMarkdownV2($messageText);
                
                // 1. Відправляємо текст (parse_mode увімкнено за замовч.)
                sendMessage($chatId, $escapedMessageText, 'text');
                
                // 2. Фото
                if ($posterPath) {
                    $posterUrl = "https://image.tmdb.org/t/p/w500" . $posterPath;
                    sendMessage($chatId, $posterUrl, 'photo'); 
                }
            } elseif ($movieData['type'] === 'error') {
                sendMessage($chatId, $movieData['content'], 'text', null, true); 
            }
        }); 
    } elseif (strpos($text, "/popular_movie") === 0) {
        sendChatTyping($chatId);
        preloader($chatId, function() use ($chatId) {
           $movieData = popularMovieCommand($chatId);
            if ($movieData['type'] === 'movie_data') {
                $title = $movieData['title'] ?? 'Назва невідома';
                $year = $movieData['release_year'] ?? '';
                $rating = $movieData['vote_average'] ?? '-';
                $posterPath = $movieData['poster_path'] ?? null;

                // Формуємо текст БЕЗ зірочок
                $messageText = "{$title} ({$year})\n⭐ {$rating}";
                $escapedMessageText = escapeMarkdownV2($messageText);

                sendMessage($chatId, $escapedMessageText, 'text');

                 if ($posterPath) {
                     $posterUrl = "https://image.tmdb.org/t/p/w500" . $posterPath;
                     sendMessage($chatId, $posterUrl, 'photo'); 
                 }
            } elseif ($movieData['type'] === 'error') {
                sendMessage($chatId, $movieData['content'], 'text', null, true); 
            }
        }); 
    } elseif (strpos($text, "/top_anime") === 0) {
        sendChatTyping($chatId);
        preloader($chatId, function() use ($chatId) {
            $result = topAnimeCommand($chatId);
            
            if ($result['type'] === 'anime_data') {
                $escapedTitle = escapeMarkdownV2($result['title']);
                $escapedYear = escapeMarkdownV2($result['year']);
                $escapedScore = escapeMarkdownV2($result['score']);
                
                $messageText = "*" . $escapedTitle . "* \(" . $escapedYear . "\)\n⭐ " . $escapedScore;
                
                sendMessage($chatId, $messageText, 'text');
                
                if (!empty($result['image_url'])) {
                    sendMessage($chatId, $result['image_url'], 'photo');
                } else {
                    sendMessage($chatId, '_(Зображення для цього аніме відсутнє)_ ', 'text');
                }
            } elseif ($result['type'] === 'error') {
                sendMessage($chatId, $result['content'], 'text');
            } else {
                error_log("dumbiebot: Unexpected result type from topAnimeCommand: " . $result['type']);
                sendMessage($chatId, 'Сталася внутрішня помилка при пошуку аніме.', 'text');
            }
        });
    } elseif (strpos($text, "/random_anime") === 0) {
        sendChatTyping($chatId);
        preloader($chatId, function() use ($chatId) {
            $result = randomAnimeCommand($chatId);
            
            if ($result['type'] === 'anime_data') {
                $escapedTitle = escapeMarkdownV2($result['title']);
                $escapedYear = escapeMarkdownV2($result['year']);
                $escapedScore = escapeMarkdownV2($result['score']);
                
                $messageText = "*" . $escapedTitle . "* \(" . $escapedYear . "\)\n⭐ " . $escapedScore;
                
                sendMessage($chatId, $messageText, 'text');
                
                if (!empty($result['image_url'])) {
                    sendMessage($chatId, $result['image_url'], 'photo');
                } else {
                    sendMessage($chatId, '_(Зображення для цього аніме відсутнє)_ ', 'text');
                }
            } elseif ($result['type'] === 'error') {
                sendMessage($chatId, $result['content'], 'text');
            } else {
                error_log("dumbiebot: Unexpected result type from randomAnimeCommand: " . $result['type']);
                sendMessage($chatId, 'Сталася внутрішня помилка при пошуку аніме.', 'text');
            }
        });
    } elseif (strpos($text, "/action") === 0) {
        actionCommand($chatId);
    } elseif (is_numeric($text) && isset($GLOBALS['userStates'][$chatId]) && $GLOBALS['userStates'][$chatId] === 'waiting_for_action_number') {
        sendChatTyping($chatId);
        processUserInput($chatId, $text);
        unset($GLOBALS['userStates'][$chatId]);
    } elseif (strpos($text, "/quote") === 0) {
        sendChatTyping($chatId);
        preloader($chatId, function() use ($chatId) {
            $result = quoteCommand($chatId);
            
            if ($result['type'] === 'quote_data') {
                $escapedQuote = escapeMarkdownV2($result['quote']);
                $escapedTitle = escapeMarkdownV2($result['title']);
                
                $messageText = $escapedQuote . "\n\n_– " . $escapedTitle . "_";
                
                sendMessage($chatId, $messageText, 'text');
            } elseif ($result['type'] === 'error') {
                sendMessage($chatId, $result['content'], 'text');
            } else {
                error_log("dumbiebot: Unexpected result type from quoteCommand: " . $result['type']);
                sendMessage($chatId, 'Сталася внутрішня помилка при отриманні цитати.', 'text');
            }
        });
    } elseif (strpos($text, "/music_video") === 0) {
        sendChatTyping($chatId);
        preloader($chatId, function() use ($chatId) {
            $result = musicVideoCommand($chatId);
            if ($result['type'] === 'video_url') {
                // Екрануємо URL перед надсиланням як текст
                $escapedUrl = escapeMarkdownV2($result['url']);
                sendMessage($chatId, $escapedUrl, 'text');
            } elseif ($result['type'] === 'error') {
                sendMessage($chatId, $result['content'], 'text');
            } else {
                error_log("dumbiebot: Unexpected result type from musicVideoCommand: " . $result['type']);
                sendMessage($chatId, 'Сталася внутрішня помилка при пошуку відео.', 'text');
            }
        });
    } elseif (strpos($text, "/funny_video") === 0) {
        sendChatTyping($chatId);
        preloader($chatId, function() use ($chatId) {
             $result = funnyVideoCommand($chatId);
             if ($result['type'] === 'video_url') {
                // Надсилаємо URL як відео (Telegram спробує вбудувати)
                sendMessage($chatId, $result['url'], 'video');
             } elseif ($result['type'] === 'error') {
                 sendMessage($chatId, $result['content'], 'text');
             } else {
                 error_log("dumbiebot: Unexpected result type from funnyVideoCommand: " . $result['type']);
                 sendMessage($chatId, 'Сталася внутрішня помилка при пошуку відео.', 'text');
             }
        });
    } elseif (strpos($text, "/true_or_false") === 0) {
        sendChatTyping($chatId);
        trueOrFalseGame($chatId);
    } elseif (strpos($text, "/millionaire") === 0) {
        sendChatTyping($chatId);
        millionaire($chatId);
    } elseif (strpos($text, "/start") === 0) {
        // Екрануємо статичне вітальне повідомлення
        $startMessage = escapeMarkdownV2('Привіт! Я розумію тількі конкретні команди. Щоб побачити список команд скористайся кнопкою "Меню" або почни писати "/"');
        sendMessage($chatId, $startMessage, 'text');
    } else {
        if (!is_numeric($text) || !isset($GLOBALS['userStates'][$chatId])) {
            // Екрануємо повідомлення про нерозуміння
            $unknownMessage = escapeMarkdownV2('Я Вас не зрозумів! Будь ласка, використовуйте команди з меню.');
            sendMessage($chatId, $unknownMessage, 'text');
        }
    }
}

function processCallbackQuery($chatId, $callbackQueryId, $data, $messageId, $update) {
    
    if (strpos($data, 'm_ans|') === 0) { // Гра Мільйонер
         // Передаємо весь callback_query масив до обробника Мільйонера
         handleMillionaireCallbackQuery($update['callback_query']);
    
    } elseif (strpos($data, 'tf_ans|') === 0) { // Гра Правда/Брехня
        // 1. Розбираємо дані: tf_ans|{відповідь}|{id}
        $parts = explode('|', $data);
        if (count($parts) !== 3) {
            error_log("Invalid tf_ans callback data format: " . $data);
            answerCallbackQuery($callbackQueryId, "Помилка даних", true);
            return;
        }
        $userAnswerStr = $parts[1]; // 'true' або 'false'
        $factId = (int)$parts[2];
        $userAnswerBool = ($userAnswerStr === 'true');

        // 2. Відповідаємо на callback query
        answerCallbackQuery($callbackQueryId);

        // 3. Отримуємо результат перевірки
        $checkResult = checkTFAnswer($factId, $userAnswerBool);

        if ($checkResult['type'] === 'tf_result') {
            $isCorrect = $checkResult['correct'];
            $description = $checkResult['description'] ?? 'Пояснення відсутнє.';
            $userName = $update['callback_query']['from']['first_name'] ?? 'Гравець';
            
            $answerText = $userAnswerBool ? '✔️ Правда' : '❌ Брехня';
            
            // Формуємо перше повідомлення (відповідь гравця)
            $msg1 = escapeMarkdownV2("{$answerText}! Каже {$userName}");
            sendMessage($chatId, $msg1, 'text');
            
            // Формуємо друге повідомлення (результат + пояснення)
            $resultPrefix = $isCorrect ? '😆👍' : '😝👎';
            $resultText = $isCorrect ? 'правий' : 'помиляється';
            $escapedDescription = escapeMarkdownV2($description);
            
            $msg2 = escapeMarkdownV2("{$resultPrefix} {$userName} {$resultText}! ") . $escapedDescription;
            sendMessage($chatId, $msg2, 'text');

        } else {
            // Помилка під час перевірки відповіді
            error_log("Error checking TF answer: " . ($checkResult['content'] ?? 'Unknown error'));
            // Надсилаємо помилку без parse_mode
            sendMessage($chatId, $checkResult['content'] ?? '❗ Помилка перевірки відповіді.', 'text', null, true); 
        }

    } elseif (strpos($data, 'img_cat|') === 0) { // Новий обробник для категорії зображень
        // 1. Видаляємо префікс, отримуємо категорію
        $categoryQuery = substr($data, strlen('img_cat|'));
        
        // 2. Відповідаємо на callback query, щоб прибрати годинник
        answerCallbackQuery($callbackQueryId);
        
        // 3. Повідомляємо, що бот працює (опціонально, можна додати текст)
        sendChatTyping($chatId);
        
        // 4. Викликаємо команду зображення з категорією
        // Використовуємо preloader для обробки потенційно довгого запиту
        preloader($chatId, function() use ($chatId, $categoryQuery) {
             $imageData = imageCommand($chatId, $categoryQuery);
            
             if ($imageData['type'] === 'photo') {
                 sendMessage($chatId, $imageData['content'], 'photo');
             } else { // Обробка помилки або якщо не знайдено
                 // Повідомлення про помилку вже містить крапку, тому надсилаємо без parse_mode
                 sendMessage($chatId, $imageData['content'], 'text', null, true);
             }
        }, "⏳ Шукаю зображення категорії '{$categoryQuery}'..."); // Можна кастомізувати текст прелоадера
        
    } elseif (strpos($data, 'action_range|') === 0) { // <-- Обробник для діапазону дій
        // 1. Витягуємо діапазон
        $range = substr($data, strlen('action_range|'));
        
        // 2. Відповідаємо на callback query
        answerCallbackQuery($callbackQueryId);
        
        // 3. Показуємо, що бот працює
        sendChatTyping($chatId);
        
        // 4. Отримуємо текст нової дії з вказаним діапазоном
        $actionResult = getActionData($range);

        // 5. Надсилаємо ТІЛЬКИ текст дії (без клавіатури)
        if ($actionResult['type'] === 'action_text') {
            sendMessage($chatId, $actionResult['content'], 'text');
        } else {
            // Надсилаємо помилку без parse_mode
            sendMessage($chatId, $actionResult['content'], 'text', null, true);
        }

    } elseif ($data === 'random_action') { // <-- Обробник для випадкової дії
         // 1. Відповідаємо на callback query
         answerCallbackQuery($callbackQueryId);
         // 2. Показуємо, що бот працює
         sendChatTyping($chatId);
         // 3. Отримуємо текст нової випадкової дії (без діапазону)
         $actionResult = getActionData(); // $range = null

         // 4. Надсилаємо ТІЛЬКИ текст дії (без клавіатури)
         if ($actionResult['type'] === 'action_text') {
             sendMessage($chatId, $actionResult['content'], 'text');
         } else {
             // Надсилаємо помилку без parse_mode
             sendMessage($chatId, $actionResult['content'], 'text', null, true);
         }

    } else {
        // Якщо callback_data не відповідає відомим форматам
        error_log("Unknown callback_data received: " . $data);
        answerCallbackQuery($callbackQueryId, "Невідома дія", true); // Повідомляємо користувача
    }
}

// Додаємо команди в меню бота
setMyCommands();

$content = file_get_contents("php://input");
$update = json_decode($content, true);

if (!$update) {
    error_log("Failed to decode incoming update or empty request.");
    exit;
}

if (isset($update["message"])) {
    processMessage($update["message"]);
} elseif (isset($update["callback_query"])) {
    $callbackQuery = $update["callback_query"];
    
    // Витягуємо необхідні дані
    $callbackData = $callbackQuery['data'] ?? '';
    $callbackQueryId = $callbackQuery['id'] ?? null;
    $chatId = $callbackQuery['message']['chat']['id'] ?? null;
    $messageId = $callbackQuery['message']['message_id'] ?? null;

    // Базова перевірка наявності даних
    if (!$callbackQueryId || !$chatId || !$messageId) {
         error_log("Error processing callback query: Missing required fields. Update: " . json_encode($update));
         // Відповідаємо на запит, щоб уникнути "зависання" кнопки
         if ($callbackQueryId) {
              answerCallbackQuery($callbackQueryId, "Помилка обробки запиту", true);
         }
         exit;
    }

    // Викликаємо єдину функцію обробки, передаючи всі параметри
    processCallbackQuery($chatId, $callbackQueryId, $callbackData, $messageId, $update);
}

?>
