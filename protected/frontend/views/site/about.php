<?php
use yii\helpers\Html;
use yii\helpers\VarDumper;

require_once 'protected/common/helpers/CLQLConst.php';

use common\helpers\MsgList;
use common\helpers\CLQL;
use common\helpers\CLQLReqMgr;


$query_resp = [

	['id' => 123, 'name' =>'Test1', 'mpg' => 12.1, 'horsepower' => 123, 'imgPath' => 'http://x/image1.com',  'overallScore' => 5.5, 'safety' => 4, 'reliability' => 5, 'utility' => 2, 'headroom' => 44.5, 'envy'=> 2.4],
	['id' => 2469, 'name' =>'Test2', 'mpg' => 10.9, 'horsepower' => 145,  'imgPath' => 'http://y/image1.com',  'overallScore' => 9.4, 'safety' => 5, 'reliability' => 4, 'utility' => 4, 'headroom' => 34.5, 'envy'=> 5.0],
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
		  "suv", "truck"
		],

		"id" : [123, 24],

		"make": ["ford", "chevy", "DOG"],

		"safety": {
		  "from": 7
		},

		"seats": {
		  "from": 6
		},

		"weight": {
		  "to": 4000
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
    "name",
    "mpg",
    "imgPath",
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
        "envy",
        {
          "user_specified_2_1": [
            "headroom",
            "utility",
            {
                "user_specified_2_1_1": [
                  "headroom",
                  "utility",
                  "Quababble"
                ]
            }
          ]
        },
        {
          "user_specified_2_2": [
            "headroom",
            "safety"
          ]
        },
        {
          "user_specified_2_3": [
            "Quababble"
          ]
        }


      ]
    },

    {
      "user_specified_3": [
        "Quababble"
        ]
    },

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

        echo '--------------------------------------<br>';

		var_dump($mgr->getConstraintsSection());
        echo '<br>--------------------------------------<br>';
		var_dump($mgr->getFetchSection());
        echo '<br>--------------------------------------<br>';
		var_dump($mgr->getScoreSection());
        echo '<br>--------------------------------------<br>';
		echo 'Map External Field To Internal (id)      : ' . $mgr->mapExternalFldToInternal('id') . '<br>';
		echo 'Map Internal Field To External (trim_id) : ' . $mgr->mapInternalFldToExternal('trim_id') . '<br>';
		echo 'Map Internal Field To External (INVALID) : ' . $mgr->mapInternalFldToExternal('XXXX') . '<br>';
		echo 'Map External Field To Internal (INVALID) : ' . $mgr->mapExternalFldToInternal('YYYYY') . '<br>';
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
        echo '<br>--------------------------------------<br>';

		VarDumper::dump($mgr->getRespData(), 10, true);
        echo '<br>--------------------------------------<br>';

		$j = $mgr->getRespDataJSON();
		echo $j;
        echo '<br>--------------------------------------<br>';

		echo 'Debug Count : ' . $mgr->getDebugCnt();
		echo '<br>Debug String -<br>' . $mgr->getDebugString('<br>', MsgList::OLD_FIRST);
        echo '<br>--------------------------------------<br>';
		VarDumper::dump($query_resp, 10, true);

     ?>
    </br>
    <code><?= __FILE__ ?></code>
</div>
