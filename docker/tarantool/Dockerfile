FROM tarantool/tarantool:1.7

COPY script/app /usr/share/tarantool
COPY script/app.lua /opt/tarantool

CMD ["tarantool", "/opt/tarantool/app.lua"]

EXPOSE 3301
