// Імпортуємо всі JSON файли
import quoteAnime from './quoteAnime.json' assert { type: 'json' };
import quoteAuthor from './quoteAuthor.json' assert { type: 'json' };
import quoteCartoon from './quoteCartoon.json' assert { type: 'json' };
import quoteMovie from './quoteMovie.json' assert { type: 'json' };
import allMusicVideo from './musicVideo.json' assert { type: 'json' };
import allFunnyVideo from './funnyVideo.json' assert { type: 'json' };

// Об'єднуємо всі цитати в один масив
export const allQuotes = [
  ...quoteAnime,
  ...quoteAuthor,
  ...quoteCartoon,
  ...quoteMovie,
];

// Експортуємо інші дані
export { allMusicVideo, allFunnyVideo };
