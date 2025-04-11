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
require_once './modules/randomAnimeCommand.php';
require_once './modules/randomMovieCommands.php';


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
                // Екрануємо отримані дані
                $title = escapeMarkdownV2($movieData['title'] ?? 'Назва невідома'); 
                $year = escapeMarkdownV2($movieData['release_year'] ?? 'N/A');
                $rating = escapeMarkdownV2($movieData['vote_average'] ?? 'N/A');
                $posterPath = $movieData['poster_path'] ?? null;
                $overview = escapeMarkdownV2($movieData['overview'] ?? ''); // Додано витягнення та екранування опису
                
                // Формуємо текст з екранованими даними, без зірочок навколо назви
                $messageText = "{$title} \({$year}\)
⭐ {$rating}";
                // Опис додамо пізніше, якщо не буде постера
                
                // 1. Відправляємо текст (parse_mode увімкнено за замовч.)
                sendMessage($chatId, $messageText, 'text');
                
                // 2. Фото або опис
                if ($posterPath) {
                    $posterUrl = "https://image.tmdb.org/t/p/w500" . $posterPath;
                    sendMessage($chatId, $posterUrl, 'photo'); 
                } elseif (!empty($overview)) {
                    // Якщо немає постера, але є опис, надсилаємо його
                     sendMessage($chatId, $overview, 'text');
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
                // Екрануємо отримані дані
                $title = escapeMarkdownV2($movieData['title'] ?? 'Назва невідома');
                $year = escapeMarkdownV2($movieData['release_year'] ?? 'N/A');
                $rating = escapeMarkdownV2($movieData['vote_average'] ?? 'N/A');
                $posterPath = $movieData['poster_path'] ?? null;
                $overview = escapeMarkdownV2($movieData['overview'] ?? '');

                // Формуємо текст з екранованими даними, без зірочок
                $messageText = "{$title} \({$year}\)
⭐ {$rating}";

                sendMessage($chatId, $messageText, 'text');

                 if ($posterPath) {
                     $posterUrl = "https://image.tmdb.org/t/p/w500" . $posterPath;
                     sendMessage($chatId, $posterUrl, 'photo'); 
                 } elseif (!empty($overview)) {
                    sendMessage($chatId, $overview, 'text');
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
        
        // Отримуємо жанри
        $genres = getAnimeGenres();
        
        if (empty($genres)) {
            sendMessage($chatId, '❌ Не вдалося завантажити список жанрів аніме.', 'text', null, true);
            return;
        }
        
        // Формуємо клавіатуру
        $buttons = [];
        // Додаємо кнопку "Будь-який жанр" першою
        $buttons[] = [['text' => '🎲 Будь-який жанр', 'callback_data' => 'anime_genre|any']]; 
        
        $row = [];
        $maxButtonsPerRow = 2; // Кількість кнопок у ряду для інших жанрів
        
        foreach ($genres as $genre) {
            $row[] = ['text' => $genre['name'], 'callback_data' => 'anime_genre|' . $genre['id']];
            if (count($row) >= $maxButtonsPerRow) {
                $buttons[] = $row;
                $row = []; // Починаємо новий ряд
            }
        }
        if (!empty($row)) { // Додаємо останній неповний ряд
            $buttons[] = $row;
        }

        $keyboard = ['inline_keyboard' => $buttons];
        sendMessage($chatId, escapeMarkdownV2("⛩️ Оберіть жанр аніме:"), 'text', $keyboard);
    } elseif (strpos($text, "/random_movie") === 0) {
        sendChatTyping($chatId);
        
        $genres = getMovieGenres();
        if (empty($genres)) {
            sendMessage($chatId, '❌ Не вдалося завантажити список жанрів фільмів.', 'text', null, true);
            return;
        }
        
        $buttons = [];
        $buttons[] = [['text' => '🎲 Будь-який жанр', 'callback_data' => 'movie_genre|any']]; 
        
        $row = [];
        $maxButtonsPerRow = 2; 
        
        foreach ($genres as $genre) {
            // Обрізаємо довгі назви жанрів, якщо потрібно
            $genreName = mb_strlen($genre['name']) > 25 ? mb_substr($genre['name'], 0, 22) . '...' : $genre['name'];
            $row[] = ['text' => $genreName, 'callback_data' => 'movie_genre|' . $genre['id']];
            if (count($row) >= $maxButtonsPerRow) {
                $buttons[] = $row;
                $row = [];
            }
        }
        if (!empty($row)) { 
            $buttons[] = $row;
        }

        $keyboard = ['inline_keyboard' => $buttons];
        sendMessage($chatId, escapeMarkdownV2("🎬 Оберіть жанр фільму:"), 'text', $keyboard);
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
        // Реєструємо команди при старті
        registerTelegramCommands(); 
        
        // Це повідомлення вже екрановано для MarkdownV2
        $welcomeMessage = "Привіт\! Я dumbieBOT\.
Готовий до роботи\.
Обери команду:";
        
        // Надсилаємо повідомлення як є, без додаткового екранування
        sendMessage($chatId, $welcomeMessage, 'text');
    } elseif (strpos($text, "/help") === 0) {
        // Send help message with list of commands
        $helpText = "*Доступні команди:*

" .
                    "`/image` \- Випадкове зображення або за категорією
" .
                    "`/top_movie` \- Топ фільмів за рейтингом
" .
                    "`/popular_movie` \- Популярні фільми зараз
" .
                    "`/random_movie` \- Випадковий фільм
" .
                    "`/top_anime` \- Топ аніме за рейтингом
" .
                    "`/random_anime` \- Випадкове аніме
" .
                    "`/quote` \- Випадкова цитата
" .
                    "`/music_video` \- Випадковий музичний кліп
" .
                    "`/funny_video` \- Випадкове смішне відео
" .
                    "`/true_or_false` \- Гра 'Правда чи Брехня'
" .
                    "`/millionaire` \- Гра 'Хто хоче стати мільйонером?' \(веб\-версія\)
" .
                    "`/start` \- Почати роботу з ботом
" .
                    "`/help` \- Допомога по командам";

        // Escape markdown for the help text
        $escapedHelpText = escapeMarkdownV2($helpText);
        sendMessage($chatId, $escapedHelpText, 'MarkdownV2');
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
        }, "Шукаю зображення категорії '{$categoryQuery}'..."); // Можна кастомізувати текст прелоадера
        
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

    } elseif (strpos($data, 'movie_genre|') === 0) { // <-- Новий обробник для жанру фільмів
        $genrePart = substr($data, strlen('movie_genre|'));

        answerCallbackQuery($callbackQueryId);
        sendChatTyping($chatId);

        $preloaderText = "Шукаю випадковий фільм...";
        $genreId = null; // За замовчуванням - будь-який

        if ($genrePart !== 'any') {
            $genreId = (int)$genrePart;
            if ($genreId <= 0) {
                sendMessage($chatId, '❌ Некоректний ID жанру фільму.', 'text', null, true);
                return; // Виходимо, якщо ID некоректний
            }
            $preloaderText = "Шукаю випадковий фільм цього жанру...";
        } else {
             $preloaderText = "Шукаю будь-який випадковий фільм...";
        }

        preloader($chatId, function() use ($chatId, $genreId) {
             $movieResult = getRandomMovie($genreId, true); // Отримуємо екрановані дані

             if ($movieResult && $movieResult['type'] === 'movie_data') {
                 $title = $movieResult['title']; // Вже екрановано
                 $rating = $movieResult['vote_average']; // Вже екрановано
                 $year = $movieResult['release_year']; // Вже екрановано
                 $overview = $movieResult['overview']; // Вже екрановано
                 $posterPath = $movieResult['poster_path'];
                 $posterUrl = $posterPath ? "https://image.tmdb.org/t/p/w500" . $posterPath : null;

                 // Прибираємо Markdown зірочки, залишаємо екранування для року
                 $caption = "{$title} \({$year}\)

⭐ Рейтинг: {$rating}

{$overview}";
                 
                 if ($posterUrl) {
                     // Обрізаємо опис, якщо він занадто довгий для підпису під фото (макс 1024)
                     $maxCaptionLength = 1000; // З запасом
                     if (mb_strlen($caption) > $maxCaptionLength) {
                         $caption = mb_substr($caption, 0, $maxCaptionLength) . "\.\.\.";
                     }
                     sendPhoto($chatId, $posterUrl, $caption); 
                 } else {
                      // Якщо немає постера, надсилаємо як текст (тут теж потрібен Markdown)
                      sendMessage($chatId, $caption, 'text');
                 }
             } elseif ($movieResult && $movieResult['type'] === 'error') {
                 sendMessage($chatId, $movieResult['content'], 'text', null, true);
             } else {
                 sendMessage($chatId, '❌ Сталася невідома помилка при пошуку фільму.', 'text', null, true);
             }
        }, $preloaderText);

    } elseif (strpos($data, 'anime_genre|') === 0) {
        // 1. Витягуємо частину після префіксу
        $genrePart = substr($data, strlen('anime_genre|'));

        // 2. Відповідаємо на callback query
        answerCallbackQuery($callbackQueryId);

        // 3. Показуємо, що бот працює
        sendChatTyping($chatId);

        // 4. Визначаємо текст прелоадера ПЕРЕД викликом
        $preloaderText = "Шукаю випадкове аніме..."; // Текст за замовчуванням
        if ($genrePart === 'any') {
            $preloaderText = "Шукаю будь-яке випадкове аніме...";
        } else {
            // Тут можна додати логіку отримання назви жанру за ID і додати до тексту,
            // але поки що залишимо загальний текст для конкретного жанру.
            $preloaderText = "Шукаю випадкове аніме цього жанру...";
        }

        // 5. Запускаємо в прелоадері отримання та відправку аніме
        preloader($chatId, function() use ($chatId, $genrePart) { // Передаємо лише потрібні змінні
             
             $animeResult = null;
             // Текст прелоадера вже визначено вище

             // Визначаємо, яку функцію викликати
             if ($genrePart === 'any') {
                 $animeResult = getTrulyRandomAnime();
             } else {
                 $genreId = (int)$genrePart; // Перетворюємо на число, якщо це ID
                 if ($genreId > 0) { // Перевіряємо, чи ID коректний
                     $animeResult = getRandomAnimeByGenre($genreId);
                 } else {
                     $animeResult = ['type' => 'error', 'content' => '❌ Некоректний ID жанру.'];
                 }
             }
             
             // Обробка результату (залишається майже такою ж)
             if ($animeResult && $animeResult['type'] === 'anime_data') {
                 // Відновлюємо витягнення та екранування потрібних даних
                 $title = escapeMarkdownV2($animeResult['title']);
                 $score = escapeMarkdownV2($animeResult['score']);
                 $year = escapeMarkdownV2($animeResult['year']);
                 // $episodes = ...; // Епізоди залишаються закоментованими
                 
                 // Формуємо $caption з ВЖЕ ЕКРАНОВАНИМИ змінними
                 $caption = "*{$title}*

⭐ Рейтинг: {$score}
📅 Рік: {$year}"; // Видалено частину про епізоди
                 
                 // Надсилаємо фото з описом
                 sendPhoto($chatId, $animeResult['image_url'], $caption);
                 
             } elseif ($animeResult && $animeResult['type'] === 'error') {
                 // Надсилаємо текст помилки
                 sendMessage($chatId, $animeResult['content'], 'text', null, true);
             } else {
                 // Якщо $animeResult === null або невідомий тип (малоймовірно)
                 sendMessage($chatId, '❌ Сталася невідома помилка при пошуку аніме.', 'text', null, true);
             }
        }, $preloaderText); // <-- Тепер передаємо текст прелоадера як третій аргумент

    } else {
        // Якщо callback_data не відповідає відомим форматам
        error_log("Unknown callback_data received: " . $data);
        answerCallbackQuery($callbackQueryId, "Невідома дія"); // Повідомляємо користувача
    }
}

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

/**
 * Реєструє/оновлює список команд для бота в Telegram.
 */
function registerTelegramCommands() {
    // Перевіряємо, чи визначено токен (на випадок виклику з контексту без config)
    if (!defined('BOT_TOKEN') || empty(BOT_TOKEN)) {
        error_log("registerTelegramCommands: BOT_TOKEN is not defined or empty.");
        return false;
    }

    // Список команд та їх описів
    $commands = [
        ['command' => 'start', 'description' => '🚀 Початок роботи / Перезапуск'],
        ['command' => 'image', 'description' => '🖼️ Випадкове зображення за категорією'],
        ['command' => 'top_movie', 'description' => '⭐ Топ фільмів (за рейтингом)'],
        ['command' => 'popular_movie', 'description' => '🔥 Популярні фільми (зараз)'],
        ['command' => 'random_movie', 'description' => '🎬 Випадковий фільм (за жанром)'], 
        ['command' => 'top_anime', 'description' => '🍥 Топ аніме (за рейтингом)'],
        ['command' => 'random_anime', 'description' => '⛩️ Випадкове аніме (за жанром)'],
        ['command' => 'quote', 'description' => '💡 Випадкова цитата'],
        ['command' => 'music_video', 'description' => '🎵 Випадковий музичний кліп'],
        ['command' => 'funny_video', 'description' => '🤣 Випадкове смішне відео'],
        ['command' => 'action', 'description' => '🤸 Випадкова дія'],
        ['command' => 'true_or_false', 'description' => '⚖️ Гра: Правда або Брехня'],
        ['command' => 'millionaire', 'description' => '💰 Гра: Мільйонер'],
    ];

    $url = API_URL . 'setMyCommands';
    $postData = ['commands' => $commands];

    $options = [
        'http' => [
            'method'  => 'POST',
            'header'  => "Content-type: application/json\r\n",
            'content' => json_encode($postData),
            'ignore_errors' => true
        ],
    ];
    $context  = stream_context_create($options);
    $response = @file_get_contents($url, false, $context);
    $responseData = json_decode($response, true);

    if (!$responseData || !$responseData['ok']) {
        error_log("Failed to set Telegram commands. Response: " . $response);
        return false;
    } else {
        // Можна додати логування успіху, але це буде спамити лог при кожному /start
        // error_log("Telegram commands successfully updated.");
        return true;
    }
}

?>
