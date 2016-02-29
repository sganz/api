<?php
use yii\helpers\Html;
use yii\helpers\VarDumper;

require_once 'protected/common/helpers/CLQLConst.php';

use common\helpers\MsgList;
use common\helpers\CLQL;
use common\helpers\CLQLMgr;

$json_req = '{
	"system" : {
		"_debug"	: false
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
                  "utility"
                ]
            }
          ]
        },
        {
          "user_specified_2_2": [
            "headroom",
            "safety"
          ]
        }

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
		$mgr = new CLQLMgr();

		$mgr->validateCLQLRequest($json_req);

		echo 'Error Count : ' . $mgr->getErrorCnt();
		echo '<br>Error String -<br>' . $mgr->getErrorString('<br>');

        echo '--------------------------------------<br>';
		echo 'Debug Count : ' . $mgr->getDebugCnt();
		//getDebugString($line_sep = "\n", $output_order = MsgList::NEW_FIRST, $line_numbers = true)
		echo '<br>Debug String -<br>' . $mgr->getDebugString('<br>', MsgList::OLD_FIRST);
        echo '--------------------------------------<br>';
        echo 'Requested Fields -<br>';
		foreach($mgr->getRequestingFields() as $fld_name)
			echo 'Field : ' . $fld_name . '<br>';

        echo '--------------------------------------<br>';

		$req = json_decode($json_req, true);

		VarDumper::dump($req, 10, true);

     ?>
    </br>
    <code><?= __FILE__ ?></code>
</div>
