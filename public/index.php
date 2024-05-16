<?php

// Подключение автозагрузки через composer
require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use DI\Container;
use Slim\Middleware\MethodOverrideMiddleware;
use Hexlet\Code\Connection;
use Hexlet\Code\PostgreSQLCreateTable;
use Hexlet\Code\Database;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Exception\ConnectException;
use DiDom\Document;
use Carbon\Carbon;

session_start();

const MAIN_PAGE = "MAIN_PAGE";
const SITES_PAGE = "SITES_PAGE";

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->safeload();

$container = new Container();
$container->set('renderer', function () {
    return new Slim\Views\PhpRenderer(__DIR__ . '/../templates');
});

$container->set('flash', function () {
    return new Slim\Flash\Messages();
});

$container->set('connection', Connection::connect());

$app = AppFactory::createFromContainer($container);
$app->add(MethodOverrideMiddleware::class);
$app->addErrorMiddleware(true, true, true);

$container->set('router', function () use ($app) {
    $router = $app->getRouteCollector()->getRouteParser();
    return $router;
});

$app->get('/', function ($request, $response) {
    $params = [
        'navLink' => MAIN_PAGE,
        'router' => $this->get('router')
    ];
    $this->get('renderer')->setLayout('layout.php');
    return $this->get('renderer')->render($response, 'index.phtml', $params);
})->setName('main');


//////////////////////////////////////      /urls       ///////////////////////////////////////////////

$app->get('/urls', function ($request, $response) {
    $dataBase = new Database($this->get('connection'));
    $dataFromDB = $dataBase->query(
        'SELECT MAX(urls_checks.created_at) AS created_at, urls_checks.status_code, urls.id, urls.name
        FROM urls
        LEFT OUTER JOIN urls_checks ON urls_checks.url_id = urls.id
        GROUP BY urls_checks.url_id, urls.id, urls_checks.status_code
        ORDER BY urls.id DESC'
    );
    $params = [
        'data' => $dataFromDB,
        'navLink' => SITES_PAGE,
        'router' => $this->get('router')
    ];

    $this->get('renderer')->setLayout('layout.php');
    return $this->get('renderer')->render($response, 'urls/index.phtml', $params);
})->setName('urls.index');


$app->post('/urls', function ($request, $response) {
    $urls = $request->getParsedBodyParam('url');
    $dataBase = new Database($this->get('connection'));
    $errors = [];

    try {
        $tableCreator = new PostgreSQLCreateTable($this->get('connection'));
        $tables = $tableCreator->createTables();
        $tablesCheck = $tableCreator->createTablesWithChecks();
    } catch (\PDOException $e) {
        echo $e->getMessage();
    }

    $v = new Valitron\Validator(array('name' => $urls['name'], 'count' => strlen((string) $urls['name'])));
    $v->rule('required', 'name')->rule('lengthMax', 'count.*', 255)->rule('url', 'name');
    if ($v->validate()) {
        $parseUrl = parse_url($urls['name']);
        $urls['name'] = $parseUrl['scheme'] . '://' . $parseUrl['host'];

        $searchName = $dataBase->query('SELECT id FROM urls WHERE name = :name', $urls);

        if (count($searchName) !== 0) {
            $url = $this->get('router')->urlFor('urls.show', ['id' => $searchName[0]['id']]);
            $this->get('flash')->addMessage('success', 'Страница уже существует');
            return $response->withRedirect($url);
        }
        $urls['time'] = Carbon::now();
        $insertInto = $dataBase->query('INSERT INTO urls(name, created_at) VALUES(:name, :time) RETURNING id', $urls);

        $id = $dataBase->query('SELECT MAX(id) FROM urls');

        $this->get('flash')->addMessage('success', 'Страница успешно добавлена');
        $url = $this->get('router')->urlFor('urls.show', ['id' => $id[0]['max']]);
        return $response->withRedirect($url);
    } else {
        if (isset($urls) and strlen($urls['name']) < 1) {
            $errors['type'] = 'URL не должен быть пустым';
        } elseif (isset($urls)) {
            $errors['type'] = 'Некорректный URL';
        }
    }

    $params = [
        'errors' => $errors,
        'router' => $this->get('router')
    ];

    $this->get('renderer')->setLayout('layout.php');
    return $this->get('renderer')->render($response->withStatus(422), 'index.phtml', $params);
})->setName('urls.store');


//////////////////////////////////////      /urls/{id}       ///////////////////////////////////////////////

$app->get('/urls/{id}', function ($request, $response, $args) {
    $messages = $this->get('flash')->getMessages();

    $dataBase = new Database($this->get('connection'));
    $dataFromDB = $dataBase->query('SELECT * FROM urls WHERE id = :id', $args);
    $dataCheckUrl = $dataBase->query('SELECT * FROM urls_checks WHERE url_id = :id ORDER BY id DESC', $args);

    $params = [
        'id' => $dataFromDB[0]['id'],
        'name' => $dataFromDB[0]['name'],
        'created_at' => $dataFromDB[0]['created_at'],
        'flash' => $messages,
        'urls' => $dataCheckUrl,
        'router' => $this->get('router')
    ];

    $this->get('renderer')->setLayout('layout.php');
    return $this->get('renderer')->render($response, 'urls/show.phtml', $params);
})->setName('urls.show');


$app->post('/urls/{id}/checks', function ($request, $response, $args) {
    $url_id = $args['id'];
    $pdo = $this->get('connection');
    $dataBase = new Database($pdo);

    $checkUrl['url_id'] = $args['id'];
    $name = $dataBase->query('SELECT name FROM urls WHERE id = :url_id', $checkUrl);

    try {
        $client = new Client();
        $res = $client->request('GET', $name[0]['name']);
        $checkUrl['status'] = $res->getStatusCode();
    } catch (ConnectException $e) {
        $this->get('flash')->addMessage('failure', 'Произошла ошибка при проверке, не удалось подключиться');

        $url = $this->get('router')->urlFor('urls.show', ['id' => $url_id]);
        return $response->withRedirect($url);
    } catch (ClientException $e) {
        if ($e->getResponse()->getStatusCode() != 200) {
            $checkUrl['status'] = $e->getResponse()->getStatusCode();
            $checkUrl['title'] = 'Доступ ограничен: проблема с IP';
            $checkUrl['h1'] = 'Доступ ограничен: проблема с IP';
            $checkUrl['meta'] = 'Доступ ограничен: проблема с IP';
            $checkUrl['time'] = Carbon::now();
            $dataBase->query('INSERT INTO urls_checks(url_id, status_code, title, h1, description, created_at)
            VALUES(:url_id, :status, :title, :h1, :meta, :time)', $checkUrl);
            $this->get('flash')->addMessage('warning', 'Проверка была выполнена успешно, но сервер ответил с ошибкой');

            $url = $this->get('router')->urlFor('urls.show', ['id' => $url_id]);
            return $response->withRedirect($url);
        }
    } catch (Throwable $e) {
        $this->get('flash')->addMessage('failure', 'Произошла ошибка при проверке');

        $url = $this->get('router')->urlFor('urls.show', ['id' => $url_id]);
        return $response->withRedirect($url);
    }

    $client = new Client();
    $res = $client->request('GET', $name[0]['name']);
    $document = new Document($res->getBody()->getContents(), false);
    $title = optional($document->first('title'));
    $h1 = optional($document->first('h1'));
    $meta = optional($document->first('meta[name="description"]'));

    if ($title?->text()) {
        $title = mb_substr($title->text(), 0, 255);
        $checkUrl['title'] = $title;
    } else {
        $checkUrl['title'] = '';
    }

    if ($h1?->text()) {
        $h1 = mb_substr($h1->text(), 0, 255);
        $checkUrl['h1'] = $h1;
    } else {
        $checkUrl['h1'] = '';
    }

    if ($meta?->getAttribute('content')) {
        $meta = mb_substr($meta->getAttribute('content'), 0, 255);
        $checkUrl['meta'] = $meta;
    } else {
        $checkUrl['meta'] = '';
    }

    $checkUrl['time'] = Carbon::now();

    if (isset($checkUrl['status'])) {
        try {
            $dataBase->query('INSERT INTO urls_checks(url_id, status_code, title, h1, description, created_at)
            VALUES(:url_id, :status, :title, :h1, :meta, :time)', $checkUrl);
        } catch (\PDOException $e) {
            echo $e->getMessage();
        }
        $this->get('flash')->addMessage('success', 'Страница успешно проверена');
    }

    $url = $this->get('router')->urlFor('urls.show', ['id' => $url_id]);
    return $response->withRedirect($url, 302);
})->setName('urls.checks');

$app->run();
