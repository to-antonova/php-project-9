# Makefile
PORT ?= 8000
start:  #Эта команда запускает веб сервер по адресу http://0.0.0.0:8000 если в переменных окружения не указан порт
	PHP_CLI_SERVER_WORKERS=5 php -S 0.0.0.0:$(PORT) -t public

install: # установить зависимости
	composer install
validate: # проверка файла composer.json
	composer validate

lint: # запуск phpcs
	composer exec --verbose phpcs -- --standard=PSR12 src bin tests
test: # запуск тестов
	composer exec --verbose phpunit tests
test-coverage: # покрытие тестами
	composer exec --verbose phpunit tests -- --coverage-clover build/logs/clover.xml
