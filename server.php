<?php
require_once(__DIR__.'/config/config.php');

$method = $_SERVER['REQUEST_METHOD'];
if ($method == 'GET' && $_GET['hub_mode'] == 'subscribe' &&  $_GET['hub_verify_token'] == $cfg['verify_token']) {
	echo $_GET['hub_challenge'];
} else if ($method == 'POST') {
	$inputJSON = file_get_contents('php://input');
	$input = json_decode($inputJSON, true);
	require(__DIR__."/function/1a2b.php");
	require(__DIR__."/function/time.php");
	foreach ($input['entry'] as $entry) {
		foreach ($entry['messaging'] as $messaging) {
			$page_id = $messaging['recipient']['id'];
			if ($page_id != $cfg['page_id']) {
				continue;
			}
			$user_id = $messaging['sender']['id'];
			$data = file_get_contents("data/".$user_id.".json");
			if (!$data) {
				$data=array(
					"count"=> 0,
					"text"=> ""
				);
			} else {
				$data = json_decode($data, true);
			}
			if (isset($messaging['message']['quick_reply']) || isset($messaging['postback'])) {
				$payload = $messaging['message']['quick_reply']['payload'] ?? $messaging['postback']['payload'];
				if ($payload == 'start') {
					if ($data["count"]==0) {
						$messageData=array(
							"recipient"=>array("id"=>$user_id),
							"message"=>array("text"=>"已開始新遊戲！將根據輸入決定答案數字個數")
						);
					} else {
						$messageData=array(
							"recipient"=>array("id"=>$user_id),
							"message"=>array("text"=>"你猜了 ".$data["count"]." 次就放棄了，答案是".implode($data["ans"])."\n".$data["text"]."\n\n已開始新遊戲！將根據輸入決定答案數字個數")
						);
					}
					$data=array(
						"count"=> 0,
						"text"=> ""
					);
				}
			} else if (isset($messaging['message'])) {
				$guess = $messaging['message']['text'];
				$guesslen = strlen($guess);
				$guessarr = str_split($guess);
				if (!preg_match("/^\d{1,10}$/", $guess)) {
					$messageData=array(
						"recipient"=>array("id"=>$user_id),
						"message"=>array("text"=>"答案不符合格式，必須是1~10個不重複數字\n".$data["text"])
					);
				} else if(!checkdiff($guessarr, $guesslen)) {
					$messageData=array(
						"recipient"=>array("id"=>$user_id),
						"message"=>array("text"=>"數字不可重複！\n".$data["text"])
					);
				} else if($data["count"]!=0 && $data["len"]!=$guesslen) {
					$messageData=array(
						"recipient"=>array("id"=>$user_id),
						"message"=>array("text"=>"答案不符合目前規則，必須是".$data["len"]."個數字\n".$data["text"])
					);
				} else if(in_array($guess, $data["guess"])) {
					$messageData=array(
						"recipient"=>array("id"=>$user_id),
						"message"=>array("text"=>"這個答案你猜過了！\n".$data["text"])
					);
				} else {
					$res="";
					if ($data["count"]==0) {
						$data=array(
							"count"=> 0,
							"guess"=> array(),
							"text"=> "",
							"time"=>time(),
							"ans"=>randomans($guesslen),
							"len"=>$guesslen
						);
						$res.="已開始 ".$data["len"]." 個數字的遊戲，欲重玩請在輸入框左方選單選擇\n";
					}
					$data["count"]++;
					$stat=checkans($data["ans"], $guessarr, $data["len"]);
					$data["guess"][]=$guess;
					$data["text"].="\n".$guess." ".$stat[0]."A".$stat[1]."B";
					if ($stat[0]==$data["len"]) {
						$res.="你花了 ".timedifftext(time()-$data["time"])." 在 ".$data["count"]." 次猜中\n".$data["text"]."\n\n已開始新遊戲！將根據輸入決定答案數字個數";
						$data=array(
							"count"=> 0,
							"text"=> ""
						);
					} else {
						$res.="你已花了 ".timedifftext(time()-$data["time"])." 猜了 ".$data["count"]." 次\n".$data["text"];
					}
					$messageData=array(
						"recipient"=>array("id"=>$user_id),
						"message"=>array("text"=>$res)
					);
				}
			} else {
				$messageData=array(
					"recipient"=>array("id"=>$user_id),
					"message"=>array("text"=>"Something went wrong!")
				);
			}
			file_put_contents("data/".$user_id.".json", json_encode($data));
			$commend = 'curl -X POST -H "Content-Type: application/json" -d \''.json_encode($messageData,JSON_HEX_APOS|JSON_HEX_QUOT).'\' "https://graph.facebook.com/v2.7/me/messages?access_token='.$cfg['page_token'].'"';
			system($commend);
		}
	}
}
