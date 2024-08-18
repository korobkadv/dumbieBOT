// Імпортуємо всі JSON файли
const quoteAnime = require("./quoteAnime.json");
const quoteAuthor = require("./quoteAuthor.json");
const quoteCartoon = require("./quoteCartoon.json");
const quoteMovie = require("./quoteMovie.json");

// Об'єднуємо всі цитати в один масив
const allQuotes = [
  ...quoteAnime,
  ...quoteAuthor,
  ...quoteCartoon,
  ...quoteMovie,
];

// Експортуємо об'єднаний масив цитат
module.exports = allQuotes;
