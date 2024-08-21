const commands = [
  { command: '/start', description: 'Початкове привітання' },
  { command: '/help', description: 'Список усіх команд до бота' },
  { command: '/image', description: 'Рандомне зображенння' },
  { command: '/top_movie', description: 'Кіно з високим рейтингом' },
  { command: '/popular_movie', description: 'Популярне з нового кіно' },
  { command: '/top_anime', description: 'Аніме з високим рейтингом' },
  {
    command: '/random_anime',
    description: 'Рандомне аніме з будь яким рейтингом',
  },
  {
    command: '/quote',
    description: 'Цитата з фільмів, аніме та відомих людей',
  },
  { command: '/music_video', description: 'Музичне відео' },
  { command: '/funny_video', description: 'Кумедне відео' },
];

export default commands;
