#!/usr/bin/env tarantool

box.cfg {
    listen = os.getenv('TARANTOOL_PORT')
}

box.schema.user.create(os.getenv('TARANTOOL_USER_NAME'), {
    password = os.getenv('TARANTOOL_USER_PASSWORD'),
    if_not_exists = true
})

box.schema.user.grant(os.getenv('TARANTOOL_USER_NAME'), 'read,write,execute', 'universe', nil, { if_not_exists = true })

dofile('/usr/share/tarantool/app_queue.lua')
