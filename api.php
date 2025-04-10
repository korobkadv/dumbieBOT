<?php
define('APP_ENV', true); // Визначаємо константу ДО підключення інших файлів

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
require_once './modules/game/trueOrFalseGame.php';
require_once './modules/game/millionaire.php';
require_once './modules/actionCommand.php';

// Функція для генерації заголовка/опису команди
function getCommandDescription($command) {
    $descriptions = [
        '/start' => 'Вітання від бота',
        '/image' => '🖼️ Випадкове зображення з Pixabay',
        '/quote' => '❝ Випадкова цитата',
        '/top_movie' => '🎬 Фільм з високим рейтингом',
        '/popular_movie' => '🔥 Популярний новий фільм',
        '/top_anime' => '🍥 Аніме з високим рейтингом',
        '/random_anime' => '🎲 Випадкове аніме',
        '/music_video' => '🎵 Випадкове музичне відео (YouTube)',
        '/funny_video' => '😂 Випадкове кумедне відео',
        '/action' => '🤸 Випадкова дія',
        '/true_or_false' => '⚖️ Гра: Правда або Брехня',
        '/millionaire' => '💰 Гра: Мільйонер '
        // Додайте описи для інших команд за потреби
    ];
    return $descriptions[$command] ?? 'Результат команди:'; // Повертаємо опис або стандартний текст
}

function handleCommand($command, $chatId) {
    $result = null;

    // Викликаємо відповідну функцію команди і отримуємо результат
    switch ($command) {
        case '/start':
            // Повертаємо вітальне повідомлення для веб-версії
            $welcomeMessage = "<p>Привіт! Я dumbieBOT.</p><p>Ви можете викликати різні команди за допомогою кнопок нижче.</p>";
            $result = ['type' => 'text', 'content' => $welcomeMessage];
            break;
        case '/image':
            // Формуємо HTML з кнопками категорій
            $description = getCommandDescription($command); // Отримуємо опис
            $htmlPrefix = "<div class=\"command-info\">{$description}</div>";
            $categories = [
                'random' => '🎲 Випадкове', 'nature' => '🏞️ Пейзаж', 'buildings' => '🏙️ Місто',
                'animals' => '🐾 Тварини', 'food' => '🍔 Їжа', 'computer' => '🧑‍💻 Технології',
                'transportation' => '🚗 Авто', 'flowers' => '🌸 Квіти', 'sports' => '⚽ Спорт'
            ];
            $buttonsHtml = '<div class="category-buttons">';
            foreach ($categories as $query => $label) {
                $buttonsHtml .= "<button class=\"command image-category-button\" data-category=\"{$query}\">{$label}</button>";
            }
            $buttonsHtml .= '</div>';
            
            // Повертаємо новий тип відповіді
            $result = ['type' => 'image_categories', 'content' => $htmlPrefix . $buttonsHtml];
            break;
        case '/quote':
            $quoteData = quoteCommand($chatId);
             if ($quoteData['type'] === 'quote_data') {
                $quote = htmlspecialchars($quoteData['quote']);
                $title = htmlspecialchars($quoteData['title']);
                $webText = $quote . "<br><br><i>– " . $title . "</i>";
                $result = ['type' => 'text', 'content' => "<p>{$webText}</p>"];
            } elseif ($quoteData['type'] === 'error') {
                $quoteData['content'] = "<p>" . htmlspecialchars($quoteData['content']) . "</p>";
                $result = $quoteData;
            } else {
                 $result = ['type' => 'error', 'content' => '<p>Внутрішня помилка сервера при отриманні цитати.</p>'];
            }
            break;
        case '/top_movie':
        case '/popular_movie':
            $movieFunction = ($command === '/top_movie') ? 'topMovieCommand' : 'popularMovieCommand';
            $movieData = $movieFunction($chatId);
            if ($movieData['type'] === 'movie_data') {
                $englishTitle = htmlspecialchars($movieData['title']);
                $posterPath = htmlspecialchars($movieData['poster_path'] ?? '');
                $rating = htmlspecialchars($movieData['vote_average']);
                $releaseYear = htmlspecialchars($movieData['release_year']);
                $ukrainianOverview = htmlspecialchars($movieData['overview']);
                $posterUrl = $posterPath ? "https://image.tmdb.org/t/p/w500" . $posterPath : '';

                $htmlContent = "<h2>{$englishTitle} ({$releaseYear})</h2>";
                $htmlContent .= "<p>⭐ {$rating}</p>";
                if ($posterUrl) {
                     $htmlContent .= "<img src=\"{$posterUrl}\" alt=\"{$englishTitle}\" class=\"info-poster\">";
                }
                $htmlContent .= "<p>{$ukrainianOverview}</p>";
                 
                $result = ['type' => 'text', 'content' => $htmlContent];
            } elseif ($movieData['type'] === 'error') {
                $movieData['content'] = "<p>" . htmlspecialchars($movieData['content']) . "</p>";
                $result = $movieData;
            } else {
                 error_log("API: Unexpected result type from {$movieFunction}: " . $movieData['type']);
                 $result = ['type' => 'error', 'content' => '<p>Внутрішня помилка сервера при пошуку фільму.</p>'];
            }
            break;
        case '/top_anime':
        case '/random_anime':
            $animeFunction = ($command === '/top_anime') ? 'topAnimeCommand' : 'randomAnimeCommand';
            $animeData = $animeFunction($chatId);
            if ($animeData['type'] === 'anime_data') {
                $title = htmlspecialchars($animeData['title']);
                $imageUrl = htmlspecialchars($animeData['image_url']);
                $score = htmlspecialchars($animeData['score']);
                $episodes = htmlspecialchars($animeData['episodes']);
                $synopsis = htmlspecialchars($animeData['synopsis']);

                $htmlContent = "<h2>{$title}</h2>";
                $htmlContent .= "<p>⭐ {$score}</p>"; 
                $htmlContent .= "<img src=\"{$imageUrl}\" alt=\"{$title}\" class=\"info-poster\">";
                $htmlContent .= "<p>{$synopsis}</p>";
                 
                $result = ['type' => 'text', 'content' => $htmlContent];
            } elseif ($animeData['type'] === 'error') {
                $animeData['content'] = "<p>" . htmlspecialchars($animeData['content']) . "</p>";
                $result = $animeData;
            } else {
                 error_log("API: Unexpected result type from {$animeFunction}: " . $animeData['type']);
                 $result = ['type' => 'error', 'content' => '<p>Внутрішня помилка сервера при пошуку аніме.</p>'];
            }
            break;
        case '/music_video':
        case '/funny_video': // Обробляємо обидві команди однаково для API
            $videoFunction = ($command === '/music_video') ? 'musicVideoCommand' : 'funnyVideoCommand';
            $videoData = $videoFunction($chatId);

            if ($videoData['type'] === 'video_url') {
                // Форматуємо для веб
                $rawUrl = $videoData['url'];
                $escapedUrl = htmlspecialchars($rawUrl);
                $htmlContent = "";
                $videoId = null;

                // Регулярний вираз для витягнення YouTube ID
                $pattern = '%^(?:https?://)?(?:www\.)?(?:youtube\.com/watch\?v=|youtu\.be/)([a-zA-Z0-9_\-]{11})(?:.*)?$%';
                if (preg_match($pattern, $rawUrl, $matches)) {
                    $videoId = $matches[1];
                }

                if ($videoId) {
                    // Якщо це YouTube відео, генеруємо iframe
                    $embedUrl = "https://www.youtube.com/embed/" . htmlspecialchars($videoId);
                    $htmlContent = '<div style="position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; max-width: 100%; margin-bottom: 10px;">';
                    $htmlContent .= '<iframe ';
                    $htmlContent .= 'style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;" ';
                    $htmlContent .= "src=\"{$embedUrl}\" ";
                    $htmlContent .= 'title="YouTube video player" frameborder="0" ';
                    $htmlContent .= 'allow="autoplay; encrypted-media; picture-in-picture; fullscreen" ';
                    $htmlContent .= 'referrerpolicy="strict-origin-when-cross-origin" allowfullscreen>';
                    $htmlContent .= '</iframe>';
                    $htmlContent .= '</div>';
                    // Додаємо також звичайне посилання під плеєром
                    $htmlContent .= "<p><a href=\"{$escapedUrl}\" target=\"_blank\">Відкрити на YouTube</a></p>";
                } else {
                    // Якщо це не YouTube, генеруємо звичайне посилання
                    $htmlContent = "<p>Посилання на відео: <a href=\"{$escapedUrl}\" target=\"_blank\">{$escapedUrl}</a></p>";
                }

                $result = ['type' => 'text', 'content' => $htmlContent];
            } elseif ($videoData['type'] === 'error') {
                $videoData['content'] = "<p>" . htmlspecialchars($videoData['content']) . "</p>";
                $result = $videoData;
            } else {
                 error_log("API: Unexpected result type from {$videoFunction}: " . $videoData['type']);
                 $result = ['type' => 'error', 'content' => '<p>Внутрішня помилка сервера при пошуку відео.</p>'];
            }
            break;
        case '/action':
             // Для API викликаємо getActionData() для отримання випадкової дії
            // Передаємо false, щоб не екранувати текст для веб-версії
            $actionData = getActionData(null, false); 
            if ($actionData['type'] === 'action_text') { // Перевіряємо новий тип
                // Текст вже НЕ екрановано, тому використовуємо htmlspecialchars перед вставкою
                $htmlContent = "<p>" . htmlspecialchars($actionData['content']) . "</p>"; 
                $result = ['type' => 'text', 'content' => $htmlContent];
            } elseif ($actionData['type'] === 'error') {
                $actionData['content'] = "<p>" . htmlspecialchars($actionData['content']) . "</p>"; // Додаємо <p> до помилки
                $result = $actionData;
            } else {
                 error_log("API: Unexpected result type from getActionData: " . ($actionData['type'] ?? 'unknown'));
                 $result = ['type' => 'error', 'content' => '<p>Внутрішня помилка сервера при отриманні дії.</p>'];
            }
            break;
        case '/millionaire':
             error_log("API: Command {$command} is not supported via API.");
             $result = ['type' => 'error', 'content' => "Команда {$command} не підтримується через API."];
             break;

        default:
             error_log("API: Unknown command received in handleCommand: " . $command); 
             $result = ['type' => 'error', 'content' => '<p>Невідома команда.</p>'];
            break;
    }

    // Перевіряємо, чи результат є масивом (очікуваний формат)
    if (!is_array($result)) {
         error_log("API: Command {$command} returned unexpected non-array result.");
        $result = ['type' => 'error', 'content' => '<p>Внутрішня помилка сервера при обробці команди.</p>'];
    }
    
    return $result;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rawData = file_get_contents('php://input');
    $data = json_decode($rawData, true);
    $command = $data['command'] ?? null;
    $apiAction = $data['action'] ?? 'get_command'; // За замовчуванням - отримати команду
    $chatId = $data['chatId'] ?? 'web_user'; // Ідентифікатор для веб

    header('Content-Type: application/json');

    if (!$command) {
        http_response_code(400);
        echo json_encode(['type' => 'error', 'content' => 'Команду не вказано.']);
        exit;
    }

    $responseArray = [];

    // Перевіряємо додаткову дію
    if ($apiAction === 'check_tf' && $command === '/true_or_false') {
        // Обробка перевірки відповіді для "Правда/Брехня"
        $factId = $data['fact_id'] ?? null;
        $userAnswer = $data['user_answer'] ?? null; // Очікуємо true або false
        
        if ($factId === null || $userAnswer === null) {
             http_response_code(400);
             $responseArray = ['type' => 'error', 'content' => '<p>Не вказано ID факту або відповідь користувача для перевірки.</p>'];
        } else {
            $responseArray = checkTFAnswer(intval($factId), (bool)$userAnswer);
        }
    } elseif ($apiAction === 'check_millionaire' && $command === '/millionaire') {
        // Обробка перевірки відповіді для "Мільйонера"
        $questionId = $data['question_id'] ?? null;
        $userAnswer = $data['user_answer'] ?? null; 
        
        if ($questionId === null || $userAnswer === null) {
             http_response_code(400);
             $responseArray = ['type' => 'error', 'content' => '<p>Не вказано ID питання або відповідь користувача для перевірки Мільйонера.</p>'];
        } else {
            $checkResult = checkMillionaireAnswer(intval($questionId), $userAnswer);
            // Тепер форматуємо результат перевірки в HTML тут
            if ($checkResult['type'] === 'millionaire_result') {
                $correct = $checkResult['correct'];
                $correctAnswer = htmlspecialchars($checkResult['correct_answer']);
                $price = htmlspecialchars($checkResult['price']);
                
                if ($correct) {
                    $resultText = "🎉😆👍 Правильно! Ви виграли {$price} балів!";
                } else {
                    $resultText = "😕👎 Неправильно. Правильна відповідь: <b>{$correctAnswer}</b>. Ви втратили {$price} балів.";
                }
                 $responseArray = ['type' => 'text', 'content' => "<p>{$resultText}</p>"];
            } else {
                // Якщо checkMillionaireAnswer повернула помилку
                $responseArray = $checkResult;
            }
        }
    } elseif ($apiAction === 'get_image_category' && $command === '/image') { // Нова дія
        $category = $data['category'] ?? null;
        if (!$category) {
            http_response_code(400);
            $responseArray = ['type' => 'error', 'content' => 'Категорію зображення не вказано.'];
        } else {
            // Викликаємо imageCommand з категорією
            $imageData = imageCommand($chatId, $category);
             // Якщо тип 'photo', перетворюємо на 'text' з тегом img для веб
             if ($imageData['type'] === 'photo') {
                 $imageUrl = htmlspecialchars($imageData['content']);
                 $imageData['content'] = "<img src=\"{$imageUrl}\" alt=\"Image: {$category}\" style=\"max-width: 100%;\">";
                 $imageData['type'] = 'text'; // Змінюємо тип для renderApiResponse
             } elseif ($imageData['type'] === 'text') {
                  // Додаємо <p> для текстових помилок від imageCommand
                  $imageData['content'] = "<p>" . htmlspecialchars($imageData['content']) . "</p>";
             }
             $responseArray = $imageData;
        }
    } else {
         // Стандартна обробка команди (отримання даних або питання гри)
         if ($command === '/true_or_false') {
            $responseArray = getRandomTFQuestion();
             if ($responseArray['type'] === 'tf_question_data'){
                 // Формуємо HTML для питання тут, але БЕЗ префікса
                $question = htmlspecialchars($responseArray['question']);
                 $responseArray = ['type' => 'tf_question_data', 'content' => "<p><b>Питання:</b> {$question}</p>", 'fact_id' => $responseArray['fact_id']];
            } // Помилка обробляється нижче
        } elseif ($command === '/millionaire') {
            $responseArray = getRandomMillionaireQuestion();
             if ($responseArray['type'] === 'millionaire_question_data'){
                 // Формуємо HTML для питання тут, але БЕЗ префікса
                $question = htmlspecialchars($responseArray['question']);
                 $responseArray = ['type' => 'millionaire_question_data', 'content' => "<p><b>Питання:</b> {$question}</p>", 'options' => $responseArray['options'], 'question_id' => $responseArray['question_id']];
            } // Помилка обробляється нижче
        } else {
             // ВАЖЛИВО: handleCommand тепер повертає 'image_categories' для /image
             // інші команди обробляються як раніше
             $responseArray = handleCommand($command, $chatId); 
             // Якщо handleCommand повернув фото для інших команд (малоймовірно), змінюємо тип
             if (isset($responseArray['type']) && $responseArray['type'] === 'photo' && $command !== '/image') {
                  $responseArray['type'] = 'text'; // Припускаємо, що content вже містить <img>
             }
        }
    }

    // Додаємо HTML-префікс до ВСІХ фінальних відповідей, що мають 'content'
    // КРІМ випадку з кнопками категорій зображень (там префікс вже додано в handleCommand)
    if (isset($responseArray['content']) && $responseArray['type'] !== 'image_categories') {
        // Якщо відповідь - це помилка і ще не має <p>, додаємо його
        if ($responseArray['type'] === 'error' && strpos($responseArray['content'], '<p>') === false) {
            $responseArray['content'] = "<p>" . $responseArray['content'] . "</p>";
        }
        // Додаємо префікс
        $description = getCommandDescription($command);
        // Для get_image_category беремо опис батьківської команди /image
        if ($apiAction === 'get_image_category') { 
            $description = getCommandDescription('/image');
        }
        $htmlPrefix = "<div class=\"command-info\">{$description}</div>";
        $responseArray['content'] = $htmlPrefix . $responseArray['content'];
    }
    
    echo json_encode($responseArray);

} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
}