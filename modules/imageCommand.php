<?php
function imageCommand($chatId, $query = null) {
    $pixabayApiKey = PIXABAY_KEY;
    
    $pixabayURL = "https://pixabay.com/api/?key={$pixabayApiKey}&image_type=photo&per_page=100&safesearch=true";

    if ($query && strtolower($query) !== 'random') {
        $pixabayURL .= "&q=" . urlencode($query);
    } else {
        // Для повністю випадкових можна додати фільтр, наприклад, по категорії (якщо хочемо)
        // або залишити як є для пошуку по всьому Pixabay
        // $pixabayURL .= "&category=nature"; // приклад
    }
    
    $page = random_int(1, 5);
    $pixabayURL .= "&page={$page}";

    $options = [
        'http' => [
            'method' => 'GET',
            'ignore_errors' => true
        ],
    ];
    $context = stream_context_create($options);
    $response = @file_get_contents($pixabayURL, false, $context);

    if ($response === false) {
        error_log("imageCommand: Failed to fetch data from Pixabay API: " . $pixabayURL);
        return ['type' => 'text', 'content' => '❗ Вибачте, сталася помилка при зверненні до Pixabay.'];
    }

    $data = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("imageCommand: Failed to decode JSON from Pixabay. Error: " . json_last_error_msg() . " Response: " . $response);
        return ['type' => 'text', 'content' => '❗ Вибачте, сталася помилка при обробці відповіді від Pixabay.'];
    }

    if (isset($data['totalHits']) && $data['totalHits'] > 0 && isset($data['hits']) && count($data['hits']) > 0) {
        try {
            $randomImageIndex = random_int(0, count($data['hits']) - 1);
            $image = $data['hits'][$randomImageIndex];
            $imageUrl = $image['largeImageURL'] ?? $image['webformatURL'] ?? null;

            if ($imageUrl) {
                 return ['type' => 'photo', 'content' => $imageUrl];
            } else {
                 error_log("imageCommand: No image URL found in selected hit: " . json_encode($image));
                 return ['type' => 'text', 'content' => '❗ Не вдалося отримати URL зображення.'];
            }
        } catch (Exception $e) {
            error_log("imageCommand: Error processing image data: " . $e->getMessage());
            return ['type' => 'text', 'content' => '❗ Вибачте, сталася помилка при обробці даних зображення.'];
        }
    } else {
         error_log("imageCommand: No hits found in Pixabay response for query '{$query}'. Response: " . $response);
         $errorMessage = '❗ На жаль, не знайдено зображень';
         if ($query && strtolower($query) !== 'random') {
              $errorMessage .= " за запитом \"{$query}\"";
         }
         $errorMessage .= '.';
         return ['type' => 'text', 'content' => $errorMessage];
    }
}

// Блок обробки POST запитів видалено, оскільки це тепер робить api.php
?>
