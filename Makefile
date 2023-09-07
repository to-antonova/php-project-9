# Makefile
PORT ?= 8000
start:  #Эта команда запускает веб сервер по адресу http://0.0.0.0:8000 если в переменных окружения не указан порт
	PHP_CLI_SERVER_WORKERS=5 php -S 0.0.0.0:$(PORT) -t public
