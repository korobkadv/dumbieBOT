<?php
function randomAnimeCommand($chatId) {
    $animeURL = "https://api.jikan.moe/v4/random/anime";

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
        error_log("randomAnimeCommand: Failed to fetch data from Jikan API (Timeout or Network error): " . $animeURL);
        return ['type' => 'error', 'content' => '❗ Вибачте, сталася помилка при зверненні до бази даних аніме (таймаут або мережева помилка).'];
    }

    $data = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("randomAnimeCommand: Failed to decode JSON from Jikan API. Error: " . json_last_error_msg() . " Response: " . $response);
        return ['type' => 'error', 'content' => '❗ Вибачте, сталася помилка при обробці відповіді від бази даних аніме.'];
    }
    
    if (isset($data['status']) && $data['status'] >= 400) {
         error_log("randomAnimeCommand: Jikan API Error: " . $response);
         return ['type' => 'error', 'content' => '❗ Помилка отримання даних від бази аніме: ' . ($data['message'] ?? 'Невідома помилка API')];
    }

    if (isset($data['data'])) { // Тут може бути лише один об'єкт
        try {
        $anime = $data['data'];

            $title = $anime['title'] ?? 'Назва невідома';
            $year = isset($anime['aired']['from']) ? substr($anime['aired']['from'], 0, 4) : 'Рік невідомий';
            $score = $anime['score'] ?? '-';
            if ($score === null || $score === '-') { // Додаткова перевірка для Jikan Random
                 $score = 'Рейтинг відсутній';
            }
            $imageUrl = $anime['images']['jpg']['image_url'] ?? null;

            return [
                'type' => 'anime_data',
                'title' => $title,
                'year' => $year,
                'score' => $score,
                'image_url' => $imageUrl
            ];

        } catch (Exception $e) {
            error_log("randomAnimeCommand: Error processing anime data: " . $e->getMessage());
            return ['type' => 'error', 'content' => '❗ Вибачте, сталася помилка при обробці даних аніме.'];
        }
    } else {
        error_log("randomAnimeCommand: No 'data' found in Jikan response. Response: " . $response);
        return ['type' => 'error', 'content' => '❗ Вибачте, не вдалося знайти випадкове аніме.'];
    }
}
?>
