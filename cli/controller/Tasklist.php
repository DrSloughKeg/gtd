<?php
namespace go\modules\tutorial\gtd\cli\controller;

use go\core\Controller;
use go\core\jmap\Response;
use go\core\jmap\Router;
use go\core\orm\LoggingTrait;
use go\core\util\JSON;

class Thoughtlist extends Controller {

	/**
	 * ./cli.php community/thoughts/Thoughtlist/export --thoughtlistId=1 --format=csv
	 */
	public function export($params) {

		extract($this->checkParams($params, ['thoughtlistId', 'format'=>'csv']));

		$json = <<<JSON
[
  [
    "Thought/query", {
      "filter": {
        "thoughtlistId": [$thoughtlistId]
      }
    },
    "call-2"
  ],
  [
    "Thought/export", {
      "#ids": {
        "path": "/ids",
        "resultOf": "call-2"
      },
      "extension": "$format"
    },
    "call-3"
  ],
  [
    "core/System/blob", {
    "#id": {
      "path": "/blob/id",
      "resultOf": "call-3"
    }
  },
    "call-3"
  ]
]
JSON;

		$requests = JSON::decode($json, true);

		Response::get()->jsonOptions = JSON_PRETTY_PRINT;

		$router = new Router();
		$router->run($requests);

	}

	/**
	 * /cli.php community/thoughts/Thoughtlist/delete --thoughtlistId=1.
	 */
	public function delete($params) {

		extract($this->checkParams($params, ['thoughtlistId']));

		$json = <<<JSON
[
  [
    "Thoughtlist/set", {
      "destroy": [$thoughtlistId]
    },
    "call-1"
  ]
]
JSON;

		$requests = JSON::decode($json, true);

		Response::get()->jsonOptions = JSON_PRETTY_PRINT;

		$router = new Router();
		$router->run($requests);

	}


}