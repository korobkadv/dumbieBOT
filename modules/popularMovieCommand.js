import axios from 'axios';
import 'dotenv/config';
import { randomIndex } from '../helpers/random.js';

export const popularMovieCommand = async (bot, chatId) => {
  const movieURL = `https://api.themoviedb.org/3/movie/popular?language=en-US&page=${randomIndex(
    3,
    'yes'
  )}`;
  const { MOVIE_AUTH_KEY } = process.env;

  try {
    const responseMovie = await axios.get(movieURL, {
      headers: {
        Authorization: MOVIE_AUTH_KEY,
        'Content-Type': 'application/json',
      },
    });

    const randomMovieIndex = randomIndex(responseMovie.data.results.length);

    bot.sendMessage(
      chatId,
      `${
        responseMovie.data.results[randomMovieIndex].title
      } (${responseMovie.data.results[randomMovieIndex].release_date.slice(
        0,
        4
      )}) \n\u2B50 ${responseMovie.data.results[randomMovieIndex].vote_average}`
    );
    return bot.sendPhoto(
      chatId,
      `https://image.tmdb.org/t/p/w200${responseMovie.data.results[randomMovieIndex].poster_path}`
    );
  } catch (error) {
    console.error('Error with API:', error);
    return bot.sendMessage(
      chatId,
      `❗ Вибачте, сталася помилка. Спробуйте ще раз.`
    );
  }
};
