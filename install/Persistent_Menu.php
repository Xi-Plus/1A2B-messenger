<?php
require(__DIR__."/../config/config.php");
$commend = 'curl -X POST -H "Content-Type: application/json" -d \'{
  "setting_type" : "call_to_actions",
  "thread_state" : "existing_thread",
  "call_to_actions":[
    {
      "type":"postback",
      "title":"開始新遊戲 / 放棄重新開始",
      "payload":"start"
    },
    {
      "type":"web_url",
      "title":"連絡開發者",
      "url":"https://m.me/xiplus"
    }
  ]
}\' "https://graph.facebook.com/v2.6/me/thread_settings?access_token='.$cfg["page_token"].'"';
system($commend);
?>
