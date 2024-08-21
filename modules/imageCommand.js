import axios from 'axios';
import 'dotenv/config';
import { randomIndex } from '../helpers/random.js';

export const imageCommand = async (bot, chatId) => {
  const { PIXABAY_KEY } = process.env;

  const imageURL = `https://pixabay.com/api/?key=${PIXABAY_KEY}&editors_choice=true&pretty=true&page=${randomIndex(
    10,
    'yes'
  )}`;

  try {
    const responseImage = await axios.get(imageURL);

    return bot.sendPhoto(
      chatId,
      responseImage.data.hits[randomIndex(responseImage.data.hits.length)]
        .largeImageURL
    );
  } catch (error) {
    console.error('Error with API:', error);
    return bot.sendMessage(
      chatId,
      `😅 Вибачте, на сайті з зображеннями сталася помилка! Спробуйте ще раз.`
    );
  }
};
