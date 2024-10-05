<?php
function popularMovieCommand($chatId) {
    $page = randomIndex(1, 10);
    $movieURL = "https://api.themoviedb.org/3/movie/popular?language=en-US&page=$page";

    $options = [
        'http' => [
            'header' => [
                "Authorization: Bearer ". MOVIE_AUTH_KEY,
                "Content-Type: application/json"
            ],
            'method' => 'GET',
        ],
    ];

    $context = stream_context_create($options);
    $response = file_get_contents($movieURL, false, $context);
    $data = json_decode($response, true);

    if (isset($data['results']) && count($data['results']) > 0) {
        $randomMovieIndex = randomIndex(0, count($data['results']));
        $movie = $data['results'][$randomMovieIndex];

        // Отримуємо лише рік з дати випуску
        $year = substr($movie['release_date'], 0, 4);

        // Відправляємо повідомлення з назвою фільму та рейтингом
        $messageText = "{$movie['title']} ({$year}) \n⭐ {$movie['vote_average']}";
        sendMessage($chatId, $messageText, 'text');

        // Відправляємо постер фільму
        $posterUrl = "https://image.tmdb.org/t/p/w200{$movie['poster_path']}";
        sendMessage($chatId, $posterUrl, 'photo');
    } else {
        sendMessage($chatId, "❗ Вибачте, сталася помилка. Спробуйте ще раз.", 'text');
    }
}
?>
