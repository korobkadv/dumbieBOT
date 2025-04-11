<?php
require_once __DIR__ . '/../helpers/randomIndex.php';

function topMovieCommand($chatId) {
    $apiKey = MOVIE_AUTH_KEY; // Використовуємо ключ v3
    $page = randomIndex(1, 10); // Беремо з перших 10 сторінок для релевантності
    
    // Один запит для отримання списку фільмів українською
    $listURL = "https://api.themoviedb.org/3/movie/top_rated?api_key={$apiKey}&language=uk-UA&page={$page}"; 

    $opts = [
        'http' => [
            'method' => 'GET',
            'timeout' => 10,
            'ignore_errors' => true
        ]
    ];
    $context = stream_context_create($opts);
    $listResponse = @file_get_contents($listURL, false, $context);

    if ($listResponse === false) {
        error_log("topMovieCommand: Failed to fetch movie list from TMDB API (v3): " . $listURL);
        return ['type' => 'error', 'content' => '❗ Помилка отримання списку топ фільмів.'];
    }
    $listData = json_decode($listResponse, true);
    if (!$listData || !isset($listData['results']) || count($listData['results']) === 0) {
        error_log("topMovieCommand: No results in movie list response: " . $listResponse);
        return ['type' => 'error', 'content' => '❗ Не вдалося знайти топ фільми.'];
    }

    try {
        // Вибираємо випадковий фільм зі списку
        $randomIndex = randomIndex(0, count($listData['results']));
        $movie = $listData['results'][$randomIndex];
        
        // Витягуємо потрібні дані
        $title = $movie['title'] ?? 'Назва невідома';
        $releaseYear = isset($movie['release_date']) && strlen($movie['release_date']) >= 4 
                         ? substr($movie['release_date'], 0, 4) 
                         : 'N/A'; // Використовуємо N/A замість тексту
        $voteAverage = isset($movie['vote_average']) ? number_format($movie['vote_average'], 1) : 'N/A';
        $posterPath = $movie['poster_path'] ?? null;
        $overview = $movie['overview'] ?? ''; // Опис (українською)

        // Перевіряємо наявність основних даних
        if (empty($title) || empty($overview) || $posterPath === null) {
             error_log("topMovieCommand: Incomplete data for movie ID {$movie['id']}. Skipping.");
             // Можна спробувати взяти наступний фільм або повернути помилку
             // Для простоти повернемо помилку
             return ['type' => 'error', 'content' => '❗ Не вдалося отримати повні дані для випадкового топ фільму. Спробуйте ще раз.'];
        }

        // Повертаємо дані (текст буде екрануватися в dumbiebot.php)
        return [
            'type' => 'movie_data',
            'title' => $title, 
            'release_year' => $releaseYear,
            'vote_average' => $voteAverage,
            'overview' => $overview, 
            'poster_path' => $posterPath
        ];

    } catch (Exception $e) {
        error_log("topMovieCommand: Error processing movie data: " . $e->getMessage());
        return ['type' => 'error', 'content' => '❗ Вибачте, сталася помилка при обробці даних топ фільму.'];
    }
}

?>
