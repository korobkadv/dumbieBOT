import axios from 'axios';
import { randomIndex } from '../helpers/random.js';

export const topAnimeCommand = async (bot, chatId) => {
  const animeURL = `https://api.jikan.moe/v4/top/anime?page=${randomIndex(
    10,
    'yes'
  )}`;

  try {
    const response = await axios.get(animeURL);
    const randomAnimeIndex = randomIndex(response.data.data.length);

    bot.sendMessage(
      chatId,
      `${
        response.data.data[randomAnimeIndex].title_english
      } (${response.data.data[randomAnimeIndex].aired.from.slice(
        0,
        4
      )})  \n\u2B50 ${response.data.data[randomAnimeIndex].score}`
    );
    return bot.sendPhoto(
      chatId,
      response.data.data[randomAnimeIndex].images.jpg.image_url
    );
  } catch (error) {
    console.error('Error with API:', error);
    return bot.sendMessage(
      chatId,
      `❗ Вибачте, сталася помилка. Спробуйте ще раз.`
    );
  }
};
