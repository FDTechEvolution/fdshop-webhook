<?php

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/LineMessage.php';
require __DIR__ . '/DataManagement.php';

use \LINE\LINEBot\SignatureValidator as SignatureValidator;

// load config
$dotenv = new Dotenv\Dotenv(__DIR__);
$dotenv->load();

// initiate app
$configs = [
    'settings' => ['displayErrorDetails' => true],
];
$app = new Slim\App($configs);

/* ROUTES */
$app->get('/', function ($request, $response) {
    return "Lanjutkan!";
});


$app->post('/', function ($request, $response) {
    // get request body and line signature header
    $body = file_get_contents('php://input');
    $signature = $_SERVER['HTTP_X_LINE_SIGNATURE'];

    // log body and signature
    file_put_contents('php://stderr', 'Body: ' . $body);

    // is LINE_SIGNATURE exists in request header?
    if (empty($signature)) {
        return $response->withStatus(400, 'Signature not set');
    }

    // is this request comes from LINE?
    if ($_ENV['PASS_SIGNATURE'] == false && !SignatureValidator::validateSignature($body, $_ENV['CHANNEL_SECRET'], $signature)) {
        return $response->withStatus(400, 'Invalid signature');
    }

    // init bot
    $httpClient = new \LINE\LINEBot\HTTPClient\CurlHTTPClient($_ENV['CHANNEL_ACCESS_TOKEN']);
    $bot = new \LINE\LINEBot($httpClient, ['channelSecret' => $_ENV['CHANNEL_SECRET']]);
    $data = json_decode($body, true);

    $displayName = '';
    $statusMessage = '';
    $pictureUrl = '';
    $lineUserId = '';

    $servername = "localhost";
    $username = "admin_shop";
    $password = "Korn@001";
    $dbname = "admin_shop";



    foreach ($data['events'] as $event) {
        $userMessage = $event['message']['text'];
        $userMessage = str_replace(array("\r", "\n"), ' ', $userMessage);
        $_userMessage = strtolower($userMessage);
        $userId = $event['source']['userId'];
        $message = '';

        $res = $bot->getProfile($userId);
        if ($res->isSucceeded()) {
            $profile = $res->getJSONDecodedBody();
            $displayName = $profile['displayName'];
            $statusMessage = $profile['statusMessage'];
            $pictureUrl = $profile['pictureUrl'];
            $lineUserId = $profile['userId'];
        }

        if ($event['message']['type'] == 'image') {
            $message = 'OK';
        } else {
            if (strpos($_userMessage, 'tr') === 0) {
                $spl = explode('tr', $_userMessage);
                if(sizeof($spl) >0){
                    $mobileNo = $spl[1];
                    $mobileNo = str_replace(" ", "", $mobileNo);
                    $mobileNo = str_replace("-", "", $mobileNo);
                    $mobileNo = str_replace(".", "", $mobileNo);
                    
                    $dataManagement = new DataManagement();
                    $message = $dataManagement->findCustomer($mobileNo);
                }
                
                
            }elseif(strpos($_userMessage, 'vo') === 0){
                $spl = explode('vo', $_userMessage);
                if(sizeof($spl) >0){
                    $orderId = $spl[1];
                    
                    $orderId = str_replace(" ", "", $orderId);
                    $orderId = str_replace("-", "", $orderId);
                    $orderId = str_replace(".", "", $orderId);
                    
                    $dataManagement = new DataManagement();
                    $message = $dataManagement->voidOrder($orderId,$lineUserId);
                }
                
            } elseif ($_userMessage == 'commission') {
                $dataManagement = new DataManagement();
                $commission = $dataManagement->getCommission($lineUserId);
                //$message = $commission;
                $message = sprintf("Commission คงเหลือของคุณคือ %s บาท", number_format($commission));
                $message .= chr(10).'อาจจะมี Order ที่ยังไม่ได้ยืนยันในระบบ';
                
            } elseif (strtolower($userMessage) == 'regis') {
                $message = $lineUserId;
            } elseif (strlen($userMessage) > 150) {
                $dataManagement = new DataManagement();

                $insertResult = $dataManagement->createTmpOrder($userMessage, $lineUserId);
                if ($insertResult['status']) {
                    $username = $insertResult['name'];
                    $cheers = [
                        sprintf('Saved! เยี่ยมมากน้อง %s #order no %s', $username,$insertResult['id']),
                        sprintf('อยากได้อีกน้อง %s ส่งมาอีก! #order no %s', $username,$insertResult['id']),
                        sprintf('สุดยอด! น้อง %s บันทึกแล้ว #order no %s', $username,$insertResult['id']),
                        sprintf('ชอบมาก อยากได้อีกน้อง %s #order no %s', $username,$insertResult['id'])
                    ];
                    $random_key = $cheers[rand(0, count($cheers) - 1)];

                    if (strpos($userMessage, "โอน") != false) {
                        $message =$random_key.chr(10).'อย่าลืมส่งสลิปมาด้วยเน้อ';
                    } else {
                        $message = $random_key;
                    }

                    $lineMsg = new LineMessage();
                    $data = array(
                        //'message' => $displayName . ' ได้ออเดอร์ใหม่' . mb_substr($userMessage, 0, 100, 'UTF-8')
                        'message' => sprintf('[%s] ได้ออเดอร์ใหม่ %s', $insertResult['name'], $userMessage)
                    );
                    $lineMsg->sendMsg($data);
                } else {
                    $message = $insertResult['message'];
                }
            } else {
                $message = 'จ้า';
            }
        }



        //$json = json_encode($event);
        $textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($message);
        $result = $bot->replyMessage($event['replyToken'], $textMessageBuilder);
        return $result->getHTTPStatus() . ' ' . $result->getRawBody();


        /*
          if (strtolower($userMessage) == 'test') {
          $message = $displayName;

          $textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($message);
          $result = $bot->replyMessage($event['replyToken'], $textMessageBuilder);
          return $result->getHTTPStatus() . ' ' . $result->getRawBody();
          }
         * 
         */
    }
});

/* JUST RUN IT */
$app->run();

