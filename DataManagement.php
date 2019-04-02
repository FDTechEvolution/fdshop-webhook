<?php
//require __DIR__ . '/LineMessage.php';
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Utils
 *
 * @author sakorn.s
 */
class DataManagement {

    public $Servername = "localhost";
    public $Username = "admin_shop";
    public $Password = "Korn@001";
    public $Dbname = "admin_shop";
    public $DocStatus = [
        'P1' => 'พร้อมปริ้น',
        'P2' => 'ปริ้นที่อยู่แล้ว',
        'WS' => 'รอส่ง',
        'ST' => 'ส่งแล้ว',
        'CF' => 'ยืนยันแล้ว',
        'DR' => 'ร่าง',
        'SP' => 'กรณีพิเศษ'
    ];

    public function help() {
        
    }

    public function getCommission($lineUserId = '') {
        $json = file_get_contents('https://shop.fdtech.co.th/service-user/get-user-by-line/' . $lineUserId);
        $obj = json_decode($json, true);

        return $obj['commissionamt'];
    }

    public function getUser($lineUserId = '') {
        $json = file_get_contents('https://shop.fdtech.co.th/service-user/get-user-by-line/' . $lineUserId);
        $obj = json_decode($json, true);

        return $obj;
    }

    public function findCustomer($search) {
        $json = file_get_contents('https://shop.fdtech.co.th/service-customers/find?search=' . $search);
        $obj = json_decode($json, true);
        $message = 'ไม่พบข้อมูลลูกค้า';

        if (sizeof($obj) > 0) {
            $message = '';
            $_mobile = '';
            foreach ($obj as $key2 => $customer) {
                if ($_mobile != $customer['mobile']) {
                    $_mobile = $customer['mobile'];
                    $message .= $customer['fullname'];

                    $address = sprintf('ที่อยู่: %s ต.%s อ.%s จ.%s %s', $customer['address_line1'], $customer['subdistrict'], $customer['district'], $customer['province'], $customer['zipcode']);
                    $message .= chr(10) . $address;

                    $contact = sprintf('โทร %s, %s', $customer['mobile'], $customer['description']);
                    $message .= chr(10) . $contact;
                }


                $message .= chr(10) . '-- รายการสั่งซื้อ --';
                foreach ($customer['orders'] as $key => $order) {

                    $paymentMethod = $order['payment_method'] == 'cod' ? 'COD' : 'โอนเงิน';
                    if ($order['status'] == 'ST') {
                        $orderDes = sprintf('%s %s ราคา %s %s สถานะ%s https://th.kerryexpress.com/th/track/?track=%s', date('d-m-Y H:i', strtotime($order['record_date'])), $order['order_line_des'], $order['totalamt'], $paymentMethod, $this->DocStatus[$order['status']], $order['trackingno']);
                    } else {
                        $orderDes = sprintf('%s %s ราคา %s %s สถานะ%s', date('d-m-Y H:i', strtotime($order['record_date'])), $order['order_line_des'], $order['totalamt'], $paymentMethod, $this->DocStatus[$order['status']]);
                    }

                    $message .= chr(10) . $orderDes . chr(10);
                }
                $message .= chr(10);
            }
        }



        return $message;
    }

    public function voidOrder($orderId = null,$lineUserId = '') {
        $json = file_get_contents('https://shop.fdtech.co.th/service-orders/void-tmp-order/' . $orderId);
        $obj = json_decode($json, true);
        $message = '';


        if ($obj['status'] == 200) {
            $message = '*ยกเลิกรายการ*' . chr(10);
            $message .= $obj['order']['body'];

            $user = $this->getUser($lineUserId);
            $lineMsg = new LineMessage();
            $data = array(
                //'message' => $displayName . ' ได้ออเดอร์ใหม่' . mb_substr($userMessage, 0, 100, 'UTF-8')
                'message' => sprintf('[%s] %s', $user['name'], $message)
            );
            $lineMsg->sendMsg($data);
            
        } elseif ($obj['status'] == 404) {
            $message = 'ไม่พบรายการ หรือ รายการโดนยืนยันไปแล้ว กรุณาติดต่อ admin.';
        } else {
            $message = $obj['message'];
        }


        return $message;
    }

    public function salesOfDay() {
        $conn = new mysqli($this->Servername, $this->Username, $this->Password, $this->Dbname);
        $sql = "INSERT INTO tmp_orders (name, body,created) VALUES ('" . $displayName . "', '" . $userMessage . "',now())";

        $conn->close();
    }

    public function createTmpOrder($userMessage, $lineUserId) {
        $conn = new mysqli($this->Servername, $this->Username, $this->Password, $this->Dbname);

        $user = $this->getUser($lineUserId);
        $userMessage = str_replace("&#8203;", "", $userMessage);
        $userMessage = str_replace("\xE2\x80\x8C", "", $userMessage);
        $userMessage = str_replace("\xE2\x80\x8B", "", $userMessage);
        
        $sql = "INSERT INTO tmp_orders (name, body,created) VALUES ('" . $user['name'] . "', '" . $userMessage . "',now())";
        if (isset($user['id'])) {
            $sql = "INSERT INTO tmp_orders (name, body,created,user_id) VALUES ('" . $user['name'] . "', '" . $userMessage . "',now(),'" . $user['id'] . "')";
        }



        $data = [
            'status' => false,
            'message' => '',
            'name' => '',
            'id' => ''
        ];

        if ($conn->query($sql) === TRUE) {
            $data['status'] = true;
            $data['message'] = "Inserted successfully";
            $data['name'] = $user['name'];
            $data['id'] = $conn->insert_id;
            //echo "Inserted successfully";
        } else {
            $message = "Error: " . $sql . " " . $conn->error;
            $data['message'] = 'ไม่สามารถบันทึกได้ กรุณาติดต่อ Admin ด่วน!' . chr(10) . $message;
        }
        $conn->close();

        return $data;
    }

}
