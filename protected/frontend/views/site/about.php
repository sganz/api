<?php
use yii\helpers\Html;
use yii\helpers\VarDumper;

require_once 'protected/common/helpers/CLQLConst.php';

use common\helpers\MsgList;
use common\helpers\CLQL;
use common\helpers\CLQLReqMgr;


$query_resp = [

	['style_id' => 123, 'images'=>['front'=>'abc', 'rear'=>'def'], 'make_name' => 'ford', 'model_name' => 'mustang', 'full_name' =>'Ford Mustang', 'mpg' => 12.1, 'engine_horsepower' => 123,  'overall_score' => 5.5, 'safety' => 4, 'reliability' => 5, 'utility' => 2, 'headroom' => 44.5, 'envy'=> 2.4],
	//['id' => 2469, 'name' =>'Test2', 'mpg' => 10.9, 'horsepower' => 145, 'overallScore' => 9.4, 'safety' => 5, 'reliability' => 4, 'utility' => 4, 'headroom' => 34.5, 'envy'=> 5.0],
];

$json_req = '{
	"system" : {
		"_debug"	: false,
		"userToken" : "1f3870be274f6c49b3e31a0c6728957f"
	},

    "constraints": {
	    "price": {
		  "from": 20000,
		  "to": 40000
		},

		"bodyStyle": [
		  "4dr suv", "regular cab pickup"
		],

		"id" : [123, 24],

		"make": ["ford", "chevy", "DOG"],

		"safety": {
		  "from": 7
		},

		"seats": {
		  "from": 6
		}
  },

  "score": {
    "price": 9
  },

  "fetch": {
    "sort": "price",
    "limit": 20,
    "offset": 60
  },

  "requesting": [
    "id",
    "make",
    "model",
    "name",
    "mpgCity",
	"images",
    "overallScore",

    {
      "user_specified_1": [
        "safety",
        "reliability",
        "utility"
        ]
    },

    {
      "user_specified_2": [
        "safety",
        "reliability",
        "utility",
        "envy"
      ]
    },

    "horsepower",
    "horsepower"
  ]
}';


/* @var $this yii\web\View */

$this->title = Yii::t('app', 'About');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="site-about">
    <h1><?= Html::encode($this->title) ?></h1>

    <p>This is the About page. You may modify the following file to customize its content:</p>

    <p>Test Hack Page </p></br>

    <?php

		$dbg_stack = new MsgList();
		$mgr = new CLQLReqMgr();

		$mgr->validateCLQLRequest($json_req);

		echo 'Error Count : ' . $mgr->getErrorCnt();
		echo '<br>Error String -<br>' . $mgr->getErrorString('<br>');
        echo '--------------------------------------<br>';
        echo 'Requested Fields -<br>';
		foreach($mgr->getRequestingFields() as $fld_name)
			echo 'Field : ' . $fld_name . '<br>';
        echo '<br>Dumping Constraints  -----------------<br>';
		VarDumper::dump($mgr->getConstraintsSection(), 10, true);
        echo '<br>Dumping Constraints  -----------------<br>';
		VarDumper::dump($mgr->getInternalConstraintsSection(), 10, true);
        echo '<br>Dumping Fetch-------------------------<br>';
		VarDumper::dump($mgr->getFetchSection(), 10, true);
        echo '<br>Dumping Internal Fetch----------------<br>';
		VarDumper::dump($mgr->getInternalFetchSection(), 10, true);
        echo '<br>Dumping Score-------------------------<br>';
		VarDumper::dump($mgr->getScoreSection(), 10, true);
        echo '<br>Dumping Internal Mapped Score---------<br>';
		VarDumper::dump($mgr->getInternalScoreSection(), 10, true);
        echo '<br>--------------------------------------<br>';
		echo 'Map External Field To Internal (id)      : ' . $mgr->mapExternalFldToInternal('id') . '<br>';
		echo 'Map Internal Field To External (trim_id) : ' . $mgr->mapInternalFldToExternal('trim_id') . '<br>';
        echo '<br>--------------------------------------<br>';
		VarDumper::dump($mgr->getInternalFieldMap(), 10, true);
        echo '<br>--------------------------------------<br>';
		VarDumper::dump($mgr->getExternalFieldMap(), 10, true);
        echo '<br>--------------------------------------<br>';
        echo 'User Token : ' . $mgr->getUserToken();
        echo '<br>--------------------------------------<br>';
		$req = json_decode($json_req, true);
		VarDumper::dump($req, 10, true);
        echo '<br>--------------------------------------<br>';
		$mgr->mapCLQLRec($query_resp);
        echo '<br>Output PHP Response------------------<br>';
		VarDumper::dump($mgr->getRespData(), 10, true);
        echo '<br>Output JSON Response------------------<br>';
		$j = $mgr->getRespDataJSON();
		echo $j;
        echo '<br>Query Results-------------------------<br>';
		VarDumper::dump($query_resp, 10, true);
     ?>
    </br>
    <code><?= __FILE__ ?></code>
</div>
