<?php
require_once(__DIR__.'/config/config.php');

$method = $_SERVER['REQUEST_METHOD'];
if ($method == 'GET' && $_GET['hub_mode'] == 'subscribe' &&  $_GET['hub_verify_token'] == $cfg['verify_token']) {
	echo $_GET['hub_challenge'];
} else if ($method == 'POST') {
	$inputJSON = file_get_contents('php://input');
	$input = json_decode($inputJSON, true);
	require(__DIR__."/function/1a2b.php");
	foreach ($input['entry'] as $entry) {
		foreach ($entry['messaging'] as $messaging) {
			$page_id = $messaging['recipient']['id'];
			if ($page_id != $cfg['page_id']) {
				continue;
			}
			$user_id = $messaging['sender']['id'];
			$data = file_get_contents("data/".$user_id.".json");
			if (!$data) {
				$data = array(
					"count"=> 0,
					"guess"=> array(),
					"text"=> "",
					"ans"=>randomans()
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
							"message"=>array("text"=>"遊戲開始！")
						);
					} else {
						$messageData=array(
							"recipient"=>array("id"=>$user_id),
							"message"=>array("text"=>"你猜了 ".$data["count"]." 次就放棄了，答案是".implode($data["ans"])."\n".$data["text"])
						);
					}
					$data = array(
						"count"=> 0,
						"guess"=> array(),
						"text"=> "",
						"ans"=>randomans()
					);
				}
			} else if (isset($messaging['message'])) {
				$guess = $messaging['message']['text'];
				$guessarr = str_split($guess);
				if (!preg_match("/^\d{4}$/", $guess)) {
					$messageData=array(
						"recipient"=>array("id"=>$user_id),
						"message"=>array("text"=>"你的答案不符合格式，必須是4個不重複數字\n".$data["text"])
					);
				} else if(!checkdiff($guessarr)) {
					$messageData=array(
						"recipient"=>array("id"=>$user_id),
						"message"=>array("text"=>"數字不可重複！\n".$data["text"])
					);
				} else if(in_array($guess, $data["guess"])) {
					$messageData=array(
						"recipient"=>array("id"=>$user_id),
						"message"=>array("text"=>"這個答案你猜過了！\n".$data["text"])
					);
				} else {
					$data["count"]++;
					$stat=checkans($data["ans"], $guessarr);
					$data["guess"][]=$guess;
					$data["text"].="\n".$guess." ".$stat[0]."A".$stat[1]."B";
					$res="";
					if ($stat[0]==4) {
						$res.="你花了 ".$data["count"]." 次猜中\n".$data["text"]."\n\n已開始新遊戲！";
						$data = array(
							"count"=> 0,
							"guess"=> array(),
							"text"=> "",
							"ans"=>randomans()
						);
					} else {
						$res.="你已猜了 ".$data["count"]." 次\n".$data["text"];
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
