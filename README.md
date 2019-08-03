# Telegram Channel/Chat Analytics

The application allows you to conduct a complete analysis of subscribers to your telegram channel or chat.
This project is a wrapper for repository [MadelineProto](https://github.com/danog/MadelineProto)

## Installing

*The list of commands implies a pre-installed [Composer](https://getcomposer.org) and [Docker](https://docs.docker.com/get-started/)*

```bash
    git clone git@github.com:revilon1991/tca.git
    cp .env.dist .env
    docker-compose build
    docker-compose up -d
    docker-compose exec php bash
    composer install
    php bin/console doctrine:schema:update -f
```

### Usage

Fetch group info:
```bash
php bin/console fetch:group [channael/chat id]
```

Fetch all subscribers by group:
```bash
php bin/console fetch:group:subscribers [channael/chat id]
```

After executing the commands, you can observe aggregated data in the database tables.

## License

![license](https://img.shields.io/badge/License-proprietary-red.svg?style=flat-square)
