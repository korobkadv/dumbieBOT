<?php
function popularMovieCommand($chatId) {
    $movieAuthKey = MOVIE_AUTH_KEY;
    $page = random_int(1, 10);
    // --- Перший запит: отримуємо список з англійськими назвами --- 
    $listURL = "https://api.themoviedb.org/3/movie/popular?page={$page}"; // Без language

    $options = [
        'http' => [
            'header' => [
                "Authorization: Bearer {$movieAuthKey}",
                "Accept: application/json"
            ],
            'method' => 'GET',
            'ignore_errors' => true
        ],
    ];
    $context = stream_context_create($options);
    $listResponse = @file_get_contents($listURL, false, $context);

    if ($listResponse === false) {
        error_log("popularMovieCommand: Failed to fetch movie list from TMDB API: " . $listURL);
        return ['type' => 'error', 'content' => '❗ Помилка отримання списку популярних фільмів.'];
    }
    $listData = json_decode($listResponse, true);
    if (!$listData || !isset($listData['results']) || count($listData['results']) === 0) {
        error_log("popularMovieCommand: No results in movie list response: " . $listResponse);
        return ['type' => 'error', 'content' => '❗ Не вдалося знайти популярні фільми.'];
    }

    try {
        // Вибираємо випадковий фільм зі списку
        $randomMovieIndex = random_int(0, count($listData['results']) - 1);
        $movieListItem = $listData['results'][$randomMovieIndex];
        
        $movieId = $movieListItem['id'] ?? null;
        if (!$movieId) {
             error_log("popularMovieCommand: Movie ID not found in list item.");
             return ['type' => 'error', 'content' => '❗ Помилка обробки даних фільму (відсутній ID).'];
        }

        // Отримуємо базові дані з першого запиту (включаючи англ. назву)
        $englishTitle = $movieListItem['title'] ?? 'Назва невідома';
        $originalTitleFallback = $movieListItem['original_title'] ?? $englishTitle;
        $releaseYear = isset($movieListItem['release_date']) && strlen($movieListItem['release_date']) >= 4 
                         ? substr($movieListItem['release_date'], 0, 4) 
                         : 'Рік невідомий';
        $voteAverage = isset($movieListItem['vote_average']) ? number_format($movieListItem['vote_average'], 1) : '-';
        $posterPath = $movieListItem['poster_path'] ?? null;
        $englishOverview = $movieListItem['overview'] ?? 'Опис відсутній.'; // Англ. опис як запасний

        // --- Другий запит: отримуємо український опис --- 
        $detailsURL = "https://api.themoviedb.org/3/movie/{$movieId}?language=uk-UA";
        $detailsResponse = @file_get_contents($detailsURL, false, $context); 
        $ukrainianOverview = $englishOverview;

        if ($detailsResponse !== false) {
            $detailsData = json_decode($detailsResponse, true);
            if ($detailsData && isset($detailsData['overview']) && !empty($detailsData['overview'])) {
                $ukrainianOverview = $detailsData['overview'];
            } else {
                 error_log("popularMovieCommand: Failed to get Ukrainian overview for movie ID {$movieId}. Response: " . $detailsResponse);
            }
        } else {
             error_log("popularMovieCommand: Failed to fetch movie details for ID {$movieId}.");
        }

        // Повертаємо комбіновані дані
        return [
            'type' => 'movie_data',
            'title' => $englishTitle, // Тепер це поле містить англійську назву
            'original_title' => $originalTitleFallback, 
            'release_year' => $releaseYear,
            'vote_average' => $voteAverage,
            'overview' => $ukrainianOverview, 
            'poster_path' => $posterPath
        ];

    } catch (Exception $e) {
        error_log("popularMovieCommand: Error processing movie data: " . $e->getMessage());
        return ['type' => 'error', 'content' => '❗ Вибачте, сталася помилка при обробці даних фільму.'];
    }
}
?>
