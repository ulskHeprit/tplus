<?php

use Carbon\Carbon;
use DI\Container;
use Slim\Middleware\MethodOverrideMiddleware;
use TPlus\Code\Db\Db;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

require_once '../vendor/autoload.php';

$requiredEnvVariables = [
    'DB_USER',
    'DB_PASSWORD',
    'DB_HOST',
    'DB_PORT',
    'DB_DATABASE',
];
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();
$dotenv->required($requiredEnvVariables);

$dbParams['DB_USER'] = $_ENV['DB_USER'];
$dbParams['DB_PASSWORD'] = $_ENV['DB_PASSWORD'];
$dbParams['DB_HOST'] = $_ENV['DB_HOST'];
$dbParams['DB_PORT'] = $_ENV['DB_PORT'];
$dbParams['DB_DATABASE'] = $_ENV['DB_DATABASE'];

$container = new Container();

$loader = new FilesystemLoader('../templates');
$twig = new Environment($loader);

$menu = [
    'main' => [
        'name' => 'Главная',
        'href' => '/'
    ],
    'departments' => [
        'name' => 'Филиалы',
        'href' => '/departments',
    ],
    'thermal_units' => [
        'name' => 'Тепловые узлы',
        'href' => '/thermal_units',
    ],
    'damages' => [
        'name' => 'Повреждения',
        'href' => '/damages',
    ],
];

$db = Db::get($dbParams);

$container->set('renderer', $twig);
$container->set('db', $db);
$container->set('menu', $menu);

$app = AppFactory::createFromContainer($container);

$app->addRoutingMiddleware();
$methodOverrideMiddleware = new MethodOverrideMiddleware();
$app->add($methodOverrideMiddleware);

$app->addErrorMiddleware(true, true, true);
$routeParser = $app->getRouteCollector()->getRouteParser();

$app->get('/', function (Request $request, Response $response) {
    $data = [
        'selected_menu' => 'main',
        'this' => $this,
    ];

    /** @var Db $db */
    $db = $this->get('db');
    $data['departments'] = $db->fetchAll(
        'SELECT * FROM departments'
    );

    $data['content'] = $this->get('renderer')->render('damage/create.html.twig', $data);

    return $response->write($this->get('renderer')->render('index.html.twig', $data));
});

$app->get('/departments', function (Request $request, Response $response) {
    $data = [
        'selected_menu' => 'departments',
        'this' => $this,
    ];

    /** @var Db $db */
    $db = $this->get('db');
    $data['rows'] = $db->fetchAll(
        'SELECT * FROM departments'
    );

    $data['content'] = $this->get('renderer')->render('department/index.html.twig', $data);

    return $response->write($this->get('renderer')->render('index.html.twig', $data));
});

$app->get('/thermal_units', function (Request $request, Response $response) {
    $data = [
        'selected_menu' => 'thermal_units',
        'this' => $this,
    ];

    /** @var Db $db */
    $db = $this->get('db');
    $data['rows'] = $db->fetchAll(
        'SELECT tu.id AS id, tu.name AS name, d.name AS department_name FROM thermal_units AS tu'
        . ' LEFT JOIN departments AS d ON tu.department_id = d.id'
    );

    $data['content'] = $this->get('renderer')->render('thermal_unit/index.html.twig', $data);

    return $response->write($this->get('renderer')->render('index.html.twig', $data));
});

$app->get('/api/thermal_units', function (Request $request, Response $response) {
    /** @var Db $db */
    $db = $this->get('db');
    $sql = 'SELECT * FROM thermal_units WHERE 1=1';

    if ($department_id = $request->getQueryParams()['department_id'] ?? null) {
        $sql .= sprintf(' AND department_id = %d', $department_id);
    }

    $payload = json_encode($db->fetchAll($sql), JSON_UNESCAPED_UNICODE);

    $response->getBody()->write($payload);
    return $response
        ->withHeader('Content-Type', 'application/json');
});

$app->get('/damages', function (Request $request, Response $response) {
    $data = [
        'selected_menu' => 'damages',
        'this' => $this,
    ];

    /** @var Db $db */
    $db = $this->get('db');
    $data['rows'] = $db->fetchAll(
        'SELECT damages.id AS id, damages.longitude AS longitude, damages.latitude AS latitude,'
        . ' damages.leakage_amount AS leakage_amount, damages.date AS date,'
        . ' damages.thermal_unit_id AS thermal_unit_id, thermal_units.name AS thermal_unit_name,'
        . ' departments.id AS department_id, departments.name AS department_name FROM damages'
        . ' LEFT JOIN thermal_units ON damages.thermal_unit_id = thermal_units.id'
        . ' LEFT JOIN departments ON thermal_units.department_id = departments.id'
        . ' ORDER BY id DESC'
    );

    $data['content'] = $this->get('renderer')->render('damage/index.html.twig', $data);

    return $response->write($this->get('renderer')->render('index.html.twig', $data));
});

$app->get('/damages/{id:[0-9]+}/edit', function (Request $request, Response $response, array $args) {
    $data = [
        'selected_menu' => 'damages',
        'this' => $this,
    ];

    /** @var Db $db */
    $db = $this->get('db');
    $data['damage'] = $db->fetch(sprintf(
        'SELECT damages.id AS id, damages.longitude AS longitude, damages.latitude AS latitude,'
        . ' damages.leakage_amount AS leakage_amount, damages.date AS date,'
        . ' damages.thermal_unit_id AS thermal_unit_id, thermal_units.name AS thermal_unit_name,'
        . ' departments.id AS department_id, departments.name AS department_name FROM damages'
        . ' LEFT JOIN thermal_units ON damages.thermal_unit_id = thermal_units.id'
        . ' LEFT JOIN departments ON thermal_units.department_id = departments.id'
        . ' WHERE damages.id = %d',
        $args['id']
    ));

    if (!$data['damage']) {
        return $response->withStatus(404);
    }

    $data['departments'] = $db->fetchAll(
        'SELECT * FROM departments'
    );

    $data['thermal_units'] = $db->fetchAll(sprintf(
        'SELECT tu.id AS id, tu.name AS name, d.name AS department_name FROM thermal_units AS tu'
        . ' LEFT JOIN departments AS d ON tu.department_id = d.id WHERE d.id = %d',
        $data['damage']['department_id']
    ));

    $data['content'] = $this->get('renderer')->render('damage/edit.html.twig', $data);

    return $response->write($this->get('renderer')->render('index.html.twig', $data));
});

$app->post('/damages', function (Request $request, Response $response) {
    $damageData = $request->getParsedBodyParam('damage');
    $damageData['date'] = Carbon::now()->format('Y-m-d H:i:s');

    if (empty($damageData['thermal_unit_id'])) {
        return $response->withStatus(404);
    }

    $damageData['leakage_amount'] = str_replace(',', '.', $damageData['leakage_amount']);

    if (!is_numeric($damageData['leakage_amount']) || strpos($damageData['leakage_amount'], '.') === false) {
        return $response->withStatus(404);
    }

    $damageData['longitude'] = array_key_exists('longitude', $damageData) ? sprintf("'%s'", $damageData['longitude']) : null;
    $damageData['latitude'] = array_key_exists('latitude', $damageData) ? sprintf("'%s'", $damageData['latitude']) : null;

    /** @var Db $db */
    $db = $this->get('db');
    $thermal_unit = $db->fetch(sprintf('SELECT * FROM thermal_units WHERE id = %d', $damageData['thermal_unit_id']));

    if (!$thermal_unit) {
        return $response->withStatus(404);
    }

    $db->exec(
        sprintf(
            "INSERT INTO damages (thermal_unit_id, longitude, latitude, leakage_amount, date) VALUES (%d, %f, '%s')",
            $damageData['thermal_unit_id'],
            $damageData['longitude'],
            $damageData['latitude'],
            $damageData['leakage_amount'],
            $damageData['date']
        )
    );

    return $response->withStatus(302)->withHeader('Location', '/damages');
});

$app->put('/damages/{id:[0-9]+}', function (Request $request, Response $response, array $args) {
    $damageData = $request->getParsedBodyParam('damage');

    /** @var Db $db */
    $db = $this->get('db');
    $oldDamage = $db->fetch(sprintf(
        'SELECT * FROM damages WHERE id = %d',
        $args['id']
    ));

    if (!$oldDamage) {
        return $response->withStatus(404);
    }

    if (!empty($damageData['thermal_unit_id'])) {
        $thermal_unit = $db->fetch(sprintf(
        'SELECT * FROM thermal_units WHERE id = %d',
            $damageData['thermal_unit_id']
        ));

        if (!$thermal_unit) {
            return $response->withStatus(404);
        }

        $oldDamage['thermal_unit_id'] = $damageData['thermal_unit_id'];
    }

    if (!empty($damageData['leakage_amount'])) {
        $damageData['leakage_amount'] = str_replace(',', '.', $damageData['leakage_amount']);

        if (!is_numeric($damageData['leakage_amount']) || strpos($damageData['leakage_amount'], '.') === false) {
            return $response->withStatus(404);
        }

        $oldDamage['leakage_amount'] = $damageData['leakage_amount'];
    }

    if (
        array_key_exists('longitude', $damageData)
        && array_key_exists('latitude', $damageData)
    ) {
        $oldDamage['longitude'] = sprintf("'%s'", $damageData['longitude']);
        $oldDamage['latitude'] = sprintf("'%s'", $damageData['latitude']);
    }

    /** @var Db $db */
    $db = $this->get('db');
    $db->exec(sprintf(
        'UPDATE damages SET thermal_unit_id = %d, longitude = %s, latitude = %s, leakage_amount = %f WHERE id = %d',
        $oldDamage['thermal_unit_id'],
        $oldDamage['longitude'],
        $oldDamage['latitude'],
        $oldDamage['leakage_amount'],
        $oldDamage['id']
    ));

    return $response->withStatus(302)->withHeader('Location', '/damages');
});

$app->delete('/damages/{id:[0-9]+}', function (Request $request, Response $response, array $args) {
    /** @var Db $db */
    $db = $this->get('db');
    $db->exec(sprintf('DELETE FROM damages WHERE id = %d', $args['id']));

    return $response->withStatus(302)->withHeader('Location', '/damages');
});

$app->run();
