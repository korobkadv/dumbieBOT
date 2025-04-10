<?php
function topAnimeCommand($chatId) {
    $page = random_int(1, 16);
    // Jikan API може бути нестабільним, додаємо таймаут
    $animeURL = "https://api.jikan.moe/v4/top/anime?page={$page}"; 

    $opts = [
        'http' => [
            'method' => 'GET',
            'timeout' => 10, // Таймаут 10 секунд
            'ignore_errors' => true
        ]
    ];
    $context = stream_context_create($opts);
    $response = @file_get_contents($animeURL, false, $context);

    if ($response === false) {
        error_log("topAnimeCommand: Failed to fetch data from Jikan API (Timeout or Network error): " . $animeURL);
        return ['type' => 'error', 'content' => '❗ Вибачте, сталася помилка при зверненні до бази даних аніме (таймаут або мережева помилка).'];
    }

    $data = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("topAnimeCommand: Failed to decode JSON from Jikan API. Error: " . json_last_error_msg() . " Response: " . $response);
        return ['type' => 'error', 'content' => '❗ Вибачте, сталася помилка при обробці відповіді від бази даних аніме.'];
    }
    
    // Перевірка на помилки від Jikan API (наприклад, rate limit)
    if (isset($data['status']) && $data['status'] >= 400) {
         error_log("topAnimeCommand: Jikan API Error: " . $response);
         return ['type' => 'error', 'content' => '❗ Помилка отримання даних від бази аніме: ' . ($data['message'] ?? 'Невідома помилка API')];
    }

    if (isset($data['data']) && count($data['data']) > 0) {
        try {
            $randomAnimeIndex = random_int(0, count($data['data']) - 1);
            $anime = $data['data'][$randomAnimeIndex];

            // Обережніше отримуємо дані, перевіряючи існування ключів
            $title = $anime['title'] ?? 'Назва невідома';
            $year = isset($anime['aired']['from']) ? substr($anime['aired']['from'], 0, 4) : 'Рік невідомий';
            $score = $anime['score'] ?? '-';
            $imageUrl = $anime['images']['jpg']['image_url'] ?? null;

            // Повертаємо дані аніме
            return [
                'type' => 'anime_data',
                'title' => $title,
                'year' => $year,
                'score' => $score,
                'image_url' => $imageUrl
            ];

        } catch (Exception $e) {
            error_log("topAnimeCommand: Error processing anime data: " . $e->getMessage());
            return ['type' => 'error', 'content' => '❗ Вибачте, сталася помилка при обробці даних аніме.'];
        }
    } else {
        error_log("topAnimeCommand: No 'data' found in Jikan response or data array is empty. Response: " . $response);
        return ['type' => 'error', 'content' => '❗ Вибачте, не вдалося знайти аніме з високим рейтингом.'];
    }
}
?>
