<?php

use Phalcon\Loader;
use Phalcon\Di\FactoryDefault;
use Phalcon\Db\Adapter\Pdo\Mysql as DbAdapter;
use Phalcon\Mvc\Micro;
use Phalcon\Config\Adapter\Ini;

// Define some absolute path constants to aid in locating resources
define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');

require(BASE_PATH . '/vendor/autoload.php');

// Use config to define parameters
$config = new Ini(APP_PATH . '/config.ini');

// Use Loader() to autoload model
$loader = new Loader();
$loader->registerDirs(
    [
        APP_PATH . $config->phalcon->modelsDir,
        APP_PATH . $config->phalcon->componentsDir
    ]
);
$loader->register();

// Create a DI
$di = new FactoryDefault();

// Setup the config service
$di->setShared(
    'config', $config
);

// Setup the database service
$di->setShared(
    'db', function () {
        $config = $this->get('config');
        return new DbAdapter((array) $config->database);
    }
);

// Setup the cache service
$di->setShared(
    'cache', [
    'className' => 'Phalcon\Cache\Backend\File',
    'arguments' => [
        [
            'type' => 'instance',
            'className' => 'Phalcon\Cache\Frontend\Data',
            'arguments' => [
                [
                    'lifetime' => 10 // 7*24*60*60 // prod: 1 week, dev: 10 seconds
                ]
            ]
        ],
        [
            'type' => 'parameter',
            'value' => [
                'cacheDir' => APP_PATH . '/cache/'
            ]
        ]
    ]
    ]
);

// Setup the cache service
$di->setShared(
    'logger',
    [
        'className' => 'Phalcon\Logger\Adapter\File',
        'arguments' => [
            [
                'type' => 'parameter',
                'value' => APP_PATH . '/logs/error.log'
            ]
        ]
    ]
);

// Setup the cache service
$di->setShared(
    'apiHostaway',
    [
        'className' => 'ApiHostaway',
        'properties' => [
            [
                'name' => 'cache',
                'value' => [
                    'type' => 'service',
                    'name' => 'cache'
                ]
            ],
            [
                'name' => 'logger',
                'value' => [
                    'type' => 'service',
                    'name' => 'logger'
                ]
            ]
        ]
    ]
);

$app = new Micro($di);

// Retrieves all contacts
$app->get(
    '/api/contacts',
	function () use($app) {
		$contacts = Contacts::find();

		// Prepare a content
		$content = [];
		foreach ($contacts as $contact) {
			$content[] = $contact->toArray();
		}
		$data = [
			'status' => 'success',
			'result' => &$content
		];

		// Create a response
		$response = $app->response;
		$response->setJsonContent($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

		return $response;
	}
);

// Retrieves contacts based on primary key
$app->get(
    '/api/contacts/{id:[0-9]+}',
	function ($id) use ($app) {
		$contact = Contacts::findFirstById($id);

		// Prepare a content
		if ($contact) {
			$data = [
				'status' => 'success',
				'result' => $contact->toArray()
			];
		} else {
			$data = [
				'status' => 'NOT-FOUND'
			];
		}

		// Create a response
		$response = $app->response;
		$response->setJsonContent($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

		return $response;
	}
);

// Searches for contacts with $name in their names
$app->get(
    '/api/contacts/search/{name}',
	function ($name) use ($app) {
		$contacts = Contacts::query()
			->where('firstName LIKE :firstName: OR lastName LIKE :lastName:')
			->bind(['firstName' => '%' . $name . '%', 'lastName' => '%' . $name . '%'])
//			->orderBy('lastName,firstName,id')
			->execute();

		// Prepare a content
		$content = [];
		foreach ($contacts as $contact) {
			$content[] = $contact->toArray();
		}
		$data = [
			'status' => 'success',
			'result' => &$content
		];

		// Create a response
		$response = $app->response;
		$response->setJsonContent($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

		return $response;
	}
);

// Adds a new contact
$app->post(
    '/api/contacts',
	function () use ($app) {
		$input = $app->request->getJsonRawBody(TRUE);

		// Create a response
		$response = $app->response;

		$contact = new Contacts();
		$contact->setApiHostaway($app->apiHostaway);
		$contact->assign($input);

		if ($contact->create() === FALSE) {
			// Change the HTTP status
			$response->setStatusCode(409, 'Conflict');

			// Send errors to the client
			$data = [
				'status' => 'ERROR',
				'messages' => $contact->errors()
			];
		} else {
			// Change the HTTP status
			$response->setStatusCode(201, 'Created');
			$data = [
				'status' => 'success',
				'result' => $contact->toArray()
			];
		}

		$response->setJsonContent($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

		return $response;
	}
);

// Updates contacts based on primary key
$app->put(
    '/api/contacts/{id:[0-9]+}',
    function ($id) use ($app) {
        $input = $app->request->getJsonRawBody(TRUE);

        // Create a response
        $response = $app->response;

        $contact = Contacts::findFirstById($id);
        $contact->setApiHostaway($app->apiHostaway);

        // Not found
        if ($contact === FALSE) {
            $response->setJsonContent(['status' => 'NOT-FOUND'], JSON_PRETTY_PRINT);
            return $response;
        }

        // Found
        $contact->assign($input);

        if ($contact->update() === FALSE) {
            // Change the HTTP status
            $response->setStatusCode(409, 'Conflict');

            // Send errors to the client
            $data = [
                'status' => 'ERROR',
                'messages' => $contact->errors()
            ];
        } else {
            $data = [
                'status' => 'success',
                'result' => $contact->toArray()
            ];
        }

        $response->setJsonContent($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return $response;
    }
);

// Deletes contacts based on primary key
$app->delete(
    '/api/contacts/{id:[0-9]+}',
    function ($id) use ($app) {
        // Create a response
        $response = $app->response;

        $contact = Contacts::findFirstById($id);

        // Not found
        if ($contact === FALSE) {
            $response->setJsonContent(['status' => 'NOT-FOUND'], JSON_PRETTY_PRINT);
            return $response;
        }

        // Found
        if ($contact->delete() === FALSE) {
            // Change the HTTP status
            $response->setStatusCode(409, 'Conflict');

            // Send errors to the client
            $content = [
                'status' => 'ERROR',
                'messages' => $contact->errors()
            ];
        } else {
            $content = [
                'status' => 'success'
            ];
        }

        $response->setJsonContent($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return $response;
    }
);

$app->handle(
    $_SERVER["REQUEST_URI"]
);
