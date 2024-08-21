import axios from 'axios';
import { randomIndex } from '../helpers/random.js';

export const randomAnimeCommand = async (bot, chatId) => {
  const animeURL = `https://api.jikan.moe/v4/random/anime`;

  try {
    const response = await axios.get(animeURL);

    console.log(response.data.data);
    bot.sendMessage(
      chatId,
      `${response.data.data.title} (${response.data.data.aired.from.slice(
        0,
        4
      )})  \n\u2B50 ${
        response.data.data.score !== null
          ? response.data.data.score
          : 'Рейтинг відсутній'
      }`
    );
    return bot.sendPhoto(chatId, response.data.data.images.jpg.image_url);
  } catch (error) {
    console.error('Error with API:', error);
    return bot.sendMessage(
      chatId,
      `❗ Вибачте, сталася помилка. Спробуйте ще раз.`
    );
  }
};
