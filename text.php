
<?php
$json = file_get_contents('https://shop.fdtech.co.th/service-user/get-user-by-line/U8d64510d9381bd6a5010148f9a1df72a');
$obj = json_decode($json,true);

echo $obj['commissionamt'];