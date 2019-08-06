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

## Configuration
Configure environment variables in the .env file.

`AppId` and `AppHash` for the madelineproto environment can be obtained after registering your application on the resource [my.telegram.org](https://my.telegram.org)

Proxy settings - optional.

`DEFAULT_FETCH_GROUP_SUBSCRIBER` - group id without "@" which will by default receive subscriber updates.

```bash
###> danog/madelineproto ###
MADELINEPROTO_CONNECTION_PROXY_HOST=''
MADELINEPROTO_CONNECTION_PROXY_PORT=''
MADELINEPROTO_CONNECTION_PROXY_USERNAME=''
MADELINEPROTO_CONNECTION_PROXY_PASSWORD=''

MADELINEPROTO_CONNECTION_API_ID=''
MADELINEPROTO_CONNECTION_API_HASH=''

MADELINEPROTO_CONNECTION_BOT_TOKEN=''
###< danog/madelineproto ###

###> mybuilder/cronos-bundle ###
CRON_SHELL='/bin/bash'
CRON_MAILTO='test@gmail.com'
CRON_PHP_EXECUTOR='/usr/bin/php'
###< mybuilder/cronos-bundle ###

###> application ###
DEFAULT_FETCH_GROUP_SUBSCRIBER=''
###< application ###
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
