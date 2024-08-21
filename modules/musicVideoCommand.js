import { allMusicVideo } from '../db/data.js';
import { randomIndex } from '../helpers/random.js';

export const musicVideoCommand = (bot, chatId) => {
  bot.sendMessage(chatId, allMusicVideo[randomIndex(allMusicVideo.length)].url);
};
