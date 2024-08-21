import { allFunnyVideo } from '../db/data.js';
import { randomIndex } from '../helpers/random.js';

export const funnyVideoCommand = (bot, chatId) => {
  bot.sendVideo(chatId, allFunnyVideo[randomIndex(allFunnyVideo.length)].url);
};
