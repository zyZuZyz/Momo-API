<?php
# utf-8
# Đơn vị xây dựng và hỗ trợ phát triển nội dung số NA Digital.
class MoMo
{
    public $config = array();
    private $send = array();
    private $keys;
    public $momo_data_config = array(
        "appVer" => "40171",
        "appCode" => "4.0.17"
    );
    private $URLAction = array(
        "CHECK_USER_BE_MSG" => "https://api.momo.vn/backend/auth-app/public/CHECK_USER_BE_MSG", //Check người dùng
        "SEND_OTP_MSG"      => "https://api.momo.vn/backend/otp-app/public/", //Gửi OTP
        "REG_DEVICE_MSG"    => "https://api.momo.vn/backend/otp-app/public/", // Xác minh OTP
        "QUERY_TRAN_HIS_MSG" => "https://owa.momo.vn/api/QUERY_TRAN_HIS_MSG", // Check ls giao dịch
        "USER_LOGIN_MSG"     => "https://owa.momo.vn/public/login", // Đăng Nhập
        "GENERATE_TOKEN_AUTH_MSG" => "https://api.momo.vn/backend/auth-app/public/GENERATE_TOKEN_AUTH_MSG", // Get Token
        "QUERY_TRAN_HIS_MSG_NEW" => "https://m.mservice.io/hydra/v2/user/noti", // check ls giao dịch noti 
        "M2MU_INIT"         => "https://owa.momo.vn/api/M2MU_INIT", // Chuyển tiền
        "M2MU_CONFIRM"      => "https://owa.momo.vn/api/M2MU_CONFIRM", // Chuyển tiền
        'CHECK_USER_PRIVATE' => 'https://owa.momo.vn/api/CHECK_USER_PRIVATE', // Check người dùng ẩn
        'GET_TRANS_BY_TID'          => 'https://api.momo.vn/sync/transhis/details',
        'TRAN_HIS_LIST'             => 'https://api.momo.vn/sync/transhis/browse',
        'M2M_VALIDATE_MSG'  => 'https://owa.momo.vn/api/M2M_VALIDATE_MSG', // Ko rõ chức 
    );
    function namemomo($sdt)
    {
        $ch = curl_init();
    
        curl_setopt($ch, CURLOPT_URL, 'https://nhantien.momo.vn/'.$sdt);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');
        
        $headers = array();
        $headers[] = 'Connection: keep-alive';
        $headers[] = 'Cache-Control: max-age=0';
        $headers[] = 'Sec-Ch-Ua: \" Not;A Brand\";v=\"99\", \"Google Chrome\";v=\"91\", \"Chromium\";v=\"91\"';
        $headers[] = 'Sec-Ch-Ua-Mobile: ?0';
        $headers[] = 'Upgrade-Insecure-Requests: 1';
        $headers[] = 'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.77 Safari/537.36';
        $headers[] = 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9';
        $headers[] = 'Sec-Fetch-Site: none';
        $headers[] = 'Sec-Fetch-Mode: navigate';
        $headers[] = 'Sec-Fetch-User: ?1';
        $headers[] = 'Sec-Fetch-Dest: document';
        $headers[] = 'Accept-Language: vi-VN,vi;q=0.9,en-US;q=0.8,en;q=0.7,fr-FR;q=0.6,fr;q=0.5';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                $result = curl_exec($ch);
        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        }
        curl_close($ch);
        $name = explode("</div>", explode('<div class="d-flex justify-content-center" style="padding-top: 15px;padding-bottom: 15px">', $result)[1])[0]; //Lấy tên chủ khoản momo
        if($name != '' && $name != '<img src="https://img.mservice.io/momo_app_v2/new_version/img/appx_image/ic_empty_document.png">'){
            return  json_encode([
                "error"   => 1,
                "msg"    => $name,
            ]);
        }else{
            return  json_encode([
                "error"   => 0,
                "msg"    => "Số điện thoại chưa đăng ký momo",
            ]);
        }
    }
     public function M2M_VALIDATE_MSG($phone, $message = '')
    {
        $microtime = $this->get_microtime();
        $requestkeyRaw = $this->generateRandom(32);
        $requestkey = $this->RSA_Encrypt($this->config["RSA_PUBLIC_KEY"], $requestkeyRaw);
        $header = array(
            "agent_id: " . $this->config["agent_id"],
            "user_phone: " . $this->config["phone"],
            "sessionkey: " . $this->config["sessionkey"],
            "authorization: Bearer " . $this->config["authorization"],
            "msgtype: M2M_VALIDATE_MSG",
            "userid: " . $this->config["phone"],
            "requestkey: " . $requestkey,
            "Host: owa.momo.vn"
        );
        $Data = '{
            "user":"' . $this->config['phone'] . '",
            "msgType":"M2M_VALIDATE_MSG",
            "cmdId":"' . $microtime . '000000",
            "lang":"vi",
            "time":' . (int) $microtime . ',
            "channel":"APP",
            "appVer": ' . $this->momo_data_config["appVer"] . ',
            "appCode": "' . $this->momo_data_config["appCode"] . '",
            "deviceOS":"ANDROID",
            "buildNumber":1916,
            "appId":"vn.momo.transfer",
            "result":true,
            "errorCode":0,
            "errorDesc":"",
            "momoMsg":
            {
                "partnerId":"' . $phone . '",
                "_class":"mservice.backend.entity.msg.ForwardMsg",
                "message":"' . $this->get_string($message) . '"
            },
            "extra":
            {
                "checkSum":"' . $this->generateCheckSum('M2M_VALIDATE_MSG', $microtime) .'"
            }
        }';
        return $this->CURL("M2M_VALIDATE_MSG", $header, $this->Encrypt_data($Data, $requestkeyRaw));
    }

    public function SendMoney($receiver, $amount = 100, $comment = "")
    {
        $result = $this->CHECK_USER_PRIVATE($receiver);
        if(!empty($result["errorCode"]) ){
            return array(
                "status" => "error",
                "code"   => $result["errorCode"],
                "message" => $result["errorDesc"],
                "full" => json_encode($result)
            );
        }else if(is_null($result)){
            return array(
                "status" => "error",
                "code"   => -5,
                "message"=> "Hết thời gian truy cập vui lòng đăng nhập lại"
            );
        }
        $results = $this->M2M_VALIDATE_MSG($receiver, $comment);
        if(!empty($result["errorCode"]) && $result["errorDesc"] != "Lỗi cơ sở dữ liệu. Quý khách vui lòng thử lại sau"){
            return array(
                "status" => "error",
                "code"   => $result["errorCode"],
                "message"=> $result["errorDesc"],
                "full" => json_encode($result)
            );
        }else if(is_null($result)){
            return array(
                "status" => "error",
                "code"   => -5,
                "message"=> "Đã xảy ra lỗi ở momo hoặc bạn đã hết hạn truy cập vui lòng đăng nhập lại"
            );
        }
        $message = $results['momoMsg']['message'];
        $this->send = array(
            "amount" => (int)$amount,
            "comment"=> $message,
            "receiver"=> $receiver,
            "partnerName"=> $result["extra"]["NAME"]
        );
        $result = $this->M2MU_INIT();
        if(!empty($result["errorCode"]) && $result["errorDesc"] != "Lỗi cơ sở dữ liệu. Quý khách vui lòng thử lại sau"){
            return array(
                "status" => "error",
                "code"   => $result["errorCode"],
                "message"=> $result["errorDesc"],
                "nodata" => "true"
            );
        }else if(is_null($result)){
            return array(
                "status" => "error",
                "code"   => -5,
                "message"=> "Đã xảy ra lỗi ở momo hoặc bạn đã hết hạn truy cập vui lòng đăng nhập lại"
            );
        }else{
            $ID = $result["momoMsg"]["replyMsgs"]["0"]["ID"];
            $result = $this->M2MU_CONFIRM($ID);
            $balance = $result["extra"]["BALANCE"];
            $tranHisMsg = $result["momoMsg"]["replyMsgs"]["0"]["tranHisMsg"];
            if($tranHisMsg["status"] != 999 && $result["errorDesc"] != "Lỗi cơ sở dữ liệu. Quý khách vui lòng thử lại sau"){
                return array(
                    "status"   => "1",
                    "message"  => $tranHisMsg["desc"],
                    "tranDList"=> array(
                        "balance" => $balance,
                        "ID"   => $tranHisMsg["ID"],
                        "tranId"=> $tranHisMsg["tranId"],
                        "partnerId"=> $tranHisMsg["partnerId"],
                        "partnerName"=> $tranHisMsg["partnerName"],
                        "amount"   => $tranHisMsg["amount"],
                        "comment"  => (empty($tranHisMsg["comment"])) ? "" : $tranHisMsg["comment"],
                        "status"   => $tranHisMsg["status"],
                        "desc"     => $tranHisMsg["desc"],
                        "ownerNumber" => $tranHisMsg["ownerNumber"],
                        "ownerName"=> $tranHisMsg["ownerName"],
                        "millisecond" => $tranHisMsg["finishTime"]
                    ),
                    "full" => json_encode($result)
                );
            }else{
                return array(
                    "status" => "2",
                    "message"=> $tranHisMsg["desc"],
                    "tranDList" => array(
                        "balance" => $balance,
                        "ID"    => $tranHisMsg["ID"],
                        "tranId"=> $tranHisMsg["tranId"],
                        "partnerId"=> $tranHisMsg["partnerId"],
                        "partnerName"=> $tranHisMsg["partnerName"],
                        "amount"     => $tranHisMsg["amount"],
                        "comment"    => (empty($tranHisMsg["comment"])) ? "" : $tranHisMsg["comment"],
                        "status"     => $tranHisMsg["status"],
                        "desc"       => $tranHisMsg["desc"],
                        "ownerNumber"=> $tranHisMsg["ownerNumber"],
                        "ownerName"  => $tranHisMsg["ownerName"],
                        "millisecond"=> $tranHisMsg["finishTime"]
                    ),
                    "full" => json_encode($result)
                );
            }

        }
    }
    public function CheckHis($day)
    {
        $limit = 100;
        $from = date("d/m/Y",strtotime("-$day days ago"));
        $result = $this->TRAN_HIS_LIST($from,date("d/m/Y",time()),$limit)['momoMsg'];
        if(empty($result)){
            return array(
                "status" => "error",
                "code"   => -5,
                "message"=> 'Hết thời gian đăng nhập vui lòng đăng nhập lại'
            );
        }
        if(!empty($result["errorCode"])){
            return array(
                "status" => "error",
                "code"   => $result["errorCode"],
                "message"=> $result["errorDesc"]
            );
        }
        foreach ($result as $transaction){
            $his = $this->GET_TRANS_BY_TID($transaction['transId']);
            if(empty($his)){
                return array(
                    "status" => "error",
                    "code"   => -5,
                    "message"=> 'Hết thời gian đăng nhập vui lòng đăng nhập lại'
                );
            }
            if(!empty($his["errorCode"])){
                return array(
                    "status" => "error",
                    "code"   => $his["errorCode"],
                    "message"=> $his["errorDesc"]
                );
            }
        }
        foreach ($his as $history) {
             $cc[] = $his['momoMsg'];
    }
   foreach ($cc as $tran) {
        $phone = $tran['username'];
     $time = $tran['lastUpdate'];
     $transId = $tran['transId'];
     $amount = $tran['totalAmount'];
     $partnerId = $tran['targetId'];
     $partnerName = $tran['targetName'];
     $io = $tran['io'];

     $transhisData = $tran['transhisData'];
     $serviceData = $tran['serviceData'];
     $service = json_decode($serviceData, true);
     $comment = $service['COMMENT_VALUE'];  

     $tranhis[] = array(
                "tranId" => $transId,
                "id" => $time.'_'.$phone,
                "io" => $io,
                "partnerId" => $partnerId,
                "partnerName" => $partnerName,
                "comment" => $comment,
                "amount" => $amount,
                "millisecond" => $time
         );
}
        return array(
            "status"  => "success",
            "message" => "Thành công",
            "Transaction_List"=> $tranhis
        );
    }
    public function CheckHisNew($hours = 24)
    {
        $begin =  (time() - (3600 * $hours)) * 1000;
        $header = array(
            "authorization: Bearer " . $this->config["authorization"],
            "user_phone: " . $this->config["phone"],
            "sessionkey: " . $this->config["sessionkey"],
            "agent_id: " . $this->config["agent_id"],
            'app_version: ' . $this->momo_data_config["appVer"],
            'app_code: ' . $this->momo_data_config["appCode"],
            "Host: m.mservice.io"
        );
        $Data = '{
            "userId": "' . $this->config['phone'] . '",
            "fromTime": ' . $begin . ',
            "toTime": ' . $this->get_microtime() . ',
            "limit": 1000,
            "cursor": "",
            "cusPhoneNumber": ""
        }';
        $result =  $this->CURL("QUERY_TRAN_HIS_MSG_NEW", $header, $Data);
        #return $result;
        if (!is_array($result)) {
            return array(
                "status" => "error",
                "code" => -5,
                "message" => "Hết thời gian truy cập vui lòng đăng nhập lại"
            );
        }
        $tranHisMsg =  $result["message"]["data"]["notifications"];
        $return = array();
        foreach ($tranHisMsg as $value) {
         if (($value['type']) ==  77) {
                $data = json_decode($value['extra']);
                $amount  = $data->amount;
                $comment = $data->comment;
                $name    = $data->partnerName;
                $id      = $data->partnerId;
         
                $return[] = array(
                    "tranId"  => $value["tranId"],
                    "id"  => $value["ID"],
                    "partnerId" => $id,
                    "partnerName" => trim($name),
                    "comment" => $comment,
                    "amount" => (int)str_replace([',', '.'], '', $amount),
                    "millisecond" => $value["time"]
                );
            }
        }
        return json_encode(array(
            "status" => 'success',
            "data" => $return
      ));
    }
   
    public function TRAN_HIS_INIT_MSG($tranHisMsg)
    {
        $microtime = $this->get_microtime();
        $requestkeyRaw = $this->generateRandom(32);
        $requestkey = $this->RSA_Encrypt($this->config["RSA_PUBLIC_KEY"], $requestkeyRaw);
        $header = array(
            "agent_id: " . $this->config["agent_id"],
            "user_phone: " . $this->config["phone"],
            "sessionkey: " . $this->config["sessionkey"],
            "authorization: Bearer " . $this->config["authorization"],
            "msgtype: TRAN_HIS_INIT_MSG",
            "userid: " . $this->config["phone"],
            "requestkey: " . $requestkey,
            "Host: owa.momo.vn"
        );
        $Data = array(
            "user" =>  $this->config['phone'],
            "msgType" => "TRAN_HIS_INIT_MSG",
            "cmdId"   => (string) $microtime . '000000',
            "lang"    => "vi",
            "time"    =>  (int) $microtime,
            "channel" => "APP",
            "appVer"  =>  $this->momo_data_config["appVer"],
            "appCode" => $this->momo_data_config["appCode"],
            "deviceOS" => "ANDROID",
            "buildNumber" => 0,
            "appId"   => "vn.momo.platform",
            "result"  => true,
            "errorCode" => 0,
            "errorDesc" => "",
            "momoMsg" => $tranHisMsg,
            "extra" => array(
                "checkSum" => $this->generateCheckSum('TRAN_HIS_INIT_MSG', $microtime)
            )
        );
        return $this->CURL("TRAN_HIS_INIT_MSG", $header, $this->Encrypt_data($Data, $requestkeyRaw));
    }

    public function TRAN_HIS_CONFIRM_MSG($tranHisMsg = [])
    {
        $microtime = $this->get_microtime();
        $requestkeyRaw = $this->generateRandom(32);
        $requestkey = $this->RSA_Encrypt($this->config["RSA_PUBLIC_KEY"], $requestkeyRaw);
        $header = array(
            "agent_id: " . $this->config["agent_id"],
            "user_phone: " . $this->config["phone"],
            "sessionkey: " . $this->config["sessionkey"],
            "authorization: Bearer " . $this->config["authorization"],
            "msgtype: TRAN_HIS_CONFIRM_MSG",
            "userid: " . $this->config["phone"],
            "requestkey: " . $requestkey,
            "Host: owa.momo.vn"
        );
        $Data =  array(
            'user'    => $this->config['phone'],
            'pass'    => $this->config['password'],
            'msgType' => 'TRAN_HIS_CONFIRM_MSG',
            'cmdId'   => (string) $microtime . '000000',
            'lang'    => 'vi',
            'time'    => $microtime,
            'channel' => 'APP',
            'appVer'  => $this->momo_data_config["appVer"],
            'appCode' => $this->momo_data_config["appCode"],
            'deviceOS' => 'ANDROID',
            'buildNumber' => 0,
            'appId'   => 'vn.momo.platform',
            'result'  => true,
            'errorCode' => 0,
            'errorDesc' => '',
            'momoMsg' => $tranHisMsg,
            'extra' =>
            array(
                'checkSum' => $this->generateCheckSum('TRAN_HIS_CONFIRM_MSG', $microtime),
            ),
        );
        return $this->CURL("TRAN_HIS_CONFIRM_MSG", $header, $this->Encrypt_data($Data, $requestkeyRaw));
    }

    public function M2MU_CONFIRM($ID)
    {
        $microtime = $this->get_microtime();
        $requestkeyRaw = $this->generateRandom(32);
        $requestkey = $this->RSA_Encrypt($this->config["RSA_PUBLIC_KEY"], $requestkeyRaw);
        $header = array(
            "agent_id: " . $this->config["agent_id"],
            "user_phone: " . $this->config["phone"],
            "sessionkey: " . $this->config["sessionkey"],
            "authorization: Bearer " . $this->config["authorization"],
            "msgtype: M2MU_INIT",
            "userid: " . $this->config["phone"],
            "requestkey: " . $requestkey,
            "Host: owa.momo.vn"
        );
        $ipaddress = $this->get_ip_address();
        $Data =  array(
            'user' => $this->config['phone'],
            'pass' => $this->config['password'],
            'msgType' => 'M2MU_CONFIRM',
            'cmdId' => (string) $microtime . '000000',
            'lang' => 'vi',
            'time' => (int) $microtime,
            'channel' => 'APP',
            'appVer' => $this->momo_data_config["appVer"],
            'appCode' => $this->momo_data_config["appCode"],
            'deviceOS' => 'ANDROID',
            'buildNumber' => 0,
            'appId' => 'vn.momo.platform',
            'result' => true,
            'errorCode' => 0,
            'errorDesc' => '',
            'momoMsg' =>
            array(
                'ids' =>
                array(
                    0 => $ID,
                ),
                'totalAmount' => $this->send['amount'],
                'originalAmount' => $this->send['amount'],
                'originalClass' => 'mservice.backend.entity.msg.M2MUConfirmMsg',
                'originalPhone' => $this->config['phone'],
                'totalFee' => '0.0',
                'id' => $ID,
                'GetUserInfoTaskRequest' => $this->send['receiver'],
                'tranList' =>
                array(
                    0 =>
                    array(
                        '_class' => 'mservice.backend.entity.msg.TranHisMsg',
                        'user' => $this->config['phone'],
                        'clientTime' => (int) ($microtime - 211),
                        'tranType' => 36,
                        'amount' => (int) $this->send['amount'],
                        'receiverType' => 1,
                    ),
                    1 =>
                    array(
                        '_class' => 'mservice.backend.entity.msg.TranHisMsg',
                        'user' => $this->config['phone'],
                        'clientTime' => (int) ($microtime - 211),
                        'tranType' => 36,
                        'partnerId' => $this->send['receiver'],
                        'amount' => 100,
                        'comment' => '',
                        'ownerName' => $this->config['Name'],
                        'receiverType' => 0,
                        'partnerExtra1' => '{"totalAmount":' . $this->send['amount'] . '}',
                        'partnerInvNo' => 'borrow',
                    ),
                ),
                'serviceId' => 'transfer_p2p',
                'serviceCode' => 'transfer_p2p',
                'clientTime' => (int) ($microtime - 211),
                'tranType' => 2018,
                'comment' => '',
                'ref' => '',
                'amount' => $this->send['amount'],
                'partnerId' => $this->send['receiver'],
                'bankInId' => '',
                'otp' => '',
                'otpBanknet' => '',
                '_class' => 'mservice.backend.entity.msg.M2MUConfirmMsg',
                'extras' => '{"appSendChat":false,"vpc_CardType":"SML","vpc_TicketNo":"' . $ipaddress . '"","vpc_PaymentGateway":""}',
            ),
            'extra' =>
            array(
                'checkSum' => $this->generateCheckSum('M2MU_CONFIRM', $microtime),
            ),
        );
        return $this->CURL("M2MU_CONFIRM", $header, $this->Encrypt_data($Data, $requestkeyRaw));
    }
      public function M2MU_INIT()
    {
        $microtime = $this->get_microtime();
        $requestkeyRaw = $this->generateRandom(32);
        $requestkey = $this->RSA_Encrypt($this->config['RSA_PUBLIC_KEY'], $requestkeyRaw);
        $header = array(
            "agent_id: " . $this->config['agent_id'],
            "user_phone: " . $this->config['phone'],
            "sessionkey: " . $this->config['sessionkey'],
            "authorization: Bearer " . $this->config['authorization'],
            "msgtype: M2MU_INIT",
            "userid: " . $this->config['phone'],
            "requestkey: " . $requestkey,
            "Host: owa.momo.vn"
        );
        $ipaddress = $this->get_ip_address();
        $Data = array(
            'user' => $this->config['phone'],
            'msgType' => 'M2MU_INIT',
            'cmdId' => (string) $microtime . '000000',
            'lang' => 'vi',
            'time' => (int) $microtime,
            'channel' => 'APP',
            'appVer' => $this->momo_data_config["appVer"],
            'appCode' => $this->momo_data_config["appCode"],
            'deviceOS' => 'ANDROID',
            'buildNumber' => 0,
            'appId' => 'vn.momo.platform',
            'result' => true,
            'errorCode' => 0,
            'errorDesc' => '',
            'momoMsg' =>
            array(
                'clientTime' => (int) $microtime - 221,
                'tranType' => 2018,
                'comment' => $this->send['comment'],
                'amount' => $this->send['amount'],
                'partnerId' => $this->send['receiver'],
                'partnerName' => $this->send['partnerName'],
                'ref' => '',
                'serviceCode' => 'transfer_p2p',
                'serviceId' => 'transfer_p2p',
                '_class' => 'mservice.backend.entity.msg.M2MUInitMsg',
                'tranList' =>
                array(
                    0 =>
                    array(
                        'partnerName' => $this->send['partnerName'],
                        'partnerId' => $this->send['receiver'],
                        'originalAmount' => $this->send['amount'],
                        'serviceCode' => 'transfer_p2p',
                        'stickers' => '',
                        'themeBackground' => '#f5fff6',
                        'themeUrl' => 'https://cdn.mservice.com.vn/app/img/transfer/theme/Corona_750x260.png',
                        'transferSource' => '',
                        'socialUserId' => '',
                        '_class' => 'mservice.backend.entity.msg.M2MUInitMsg',
                        'tranType' => 2018,
                        'comment' => $this->send['comment'],
                        'moneySource' => 1,
                        'partnerCode' => 'momo',
                        'serviceMode' => 'transfer_p2p',
                        'serviceId' => 'transfer_p2p',
                        'extras' => '{"loanId":0,"appSendChat":false,"loanIds":[],"stickers":"","themeUrl":"https://cdn.mservice.com.vn/app/img/transfer/theme/Corona_750x260.png","hidePhone":false,"vpc_CardType":"SML","vpc_TicketNo":"' . $ipaddress . '","vpc_PaymentGateway":""}',
                    ),
                ),
                'extras' => '{"loanId":0,"appSendChat":false,"loanIds":[],"stickers":"","themeUrl":"https://cdn.mservice.com.vn/app/img/transfer/theme/Corona_750x260.png","hidePhone":false,"vpc_CardType":"SML","vpc_TicketNo":"' . $ipaddress . '","vpc_PaymentGateway":""}',
                'moneySource' => 1,
                'partnerCode' => 'momo',
                'rowCardId' => '',
                'giftId' => '',
                'useVoucher' => 0,
                'prepaidIds' => '',
                'usePrepaid' => 0,
            ),
            'extra' =>
            array(
                'checkSum' => $this->generateCheckSum('M2MU_INIT', $microtime),
            ),
        );
        return $this->CURL("M2MU_INIT", $header, $this->Encrypt_data($Data, $requestkeyRaw));
    }

    public function TRAN_HIS_LIST($from, $to, $limit)
    {

        $microtime = $this->get_microtime();
        $requestkeyRaw = $this->generateRandom(32);
        $requestkey = $this->RSA_Encrypt($this->config["RSA_PUBLIC_KEY"], $requestkeyRaw);
        $header = array(
            "agent_id: " . $this->config["agent_id"],
            "user_phone: " . $this->config["phone"],
            "sessionkey: " . $this->config["sessionkey"],
            "authorization: Bearer " . $this->config["authorization"],
            "userid: " . $this->config["phone"],
            "Host: api.momo.vn",
            'requestkey: ' . $requestkey
        );
        $Data = array(
            'requestId' => (string) $microtime,
            'startDate' => $from,
            'endDate' => $to,
            'offset' => 0,
            'limit' => $limit,
            'appCode' => $this->momo_data_config["appCode"],
            'appVer' => $this->momo_data_config["appVer"],
            'lang' => 'vi',
            'deviceOS' => 'ANDROID',
            'channel' => 'APP',
            'buildNumber' => 4155,
            'appId' => 'vn.momo.transactionhistory',
        );

        return $this->CURL("TRAN_HIS_LIST", $header, $this->Encrypt_data($Data, $requestkeyRaw));
    }
    public function GET_TRANS_BY_TID($tranId)
    {
        $microtime = $this->get_microtime();
        $requestkeyRaw = $this->generateRandom(32);
        $requestkey = $this->RSA_Encrypt($this->config["RSA_PUBLIC_KEY"],$requestkeyRaw);
        $header = array(
            "agent_id: ".$this->config["agent_id"],
            "user_phone: ".$this->config["phone"],
            "sessionkey: ".$this->config["sessionkey"],
            "authorization: Bearer ".$this->config["authorization"],
            "userid: ".$this->config["phone"],
            "Host: api.momo.vn",
            'requestkey: '.$requestkey
        );
        $Data = array (
            'requestId' => $microtime,
            'transId' => $tranId,
            'serviceId' => 'transfer_p2p',
            'appCode' => $this->momo_data_config["appCode"],
            'appVer' => $this->momo_data_config["appVer"],
            'lang' => 'vi',
            'deviceOS' => 'ANDROID',
            'channel' => 'APP',
            'buildNumber' => '7312',
            'appId' => 'vn.momo.transactionhistory',
        );
        return $this->CURL("GET_TRANS_BY_TID",$header,$this->Encrypt_data($Data,$requestkeyRaw));
    }
    public function QUERY_TRAN_HIS_MSG($hours)
    {
        $requestkeyRaw = $this->generateRandom(32);
        $requestkey = $this->RSA_Encrypt($this->config["RSA_PUBLIC_KEY"], $requestkeyRaw);
        $header = array(
            "agent_id: " . $this->config["agent_id"],
            "user_phone: " . $this->config["phone"],
            "sessionkey: " . $this->config["sessionkey"],
            "authorization: Bearer " . $this->config["authorization"],
            "msgtype: QUERY_TRAN_HIS_MSG",
            "userid: " . $this->config["phone"],
            "requestkey: " . $requestkey,
            "Host: owa.momo.vn"
        );
        $begin =  (time() - (3600 * $hours)) * 1000;
        $microtime = $this->get_microtime();
        $Data = array(
            'user' => $this->config['phone'],
            'msgType' => 'QUERY_TRAN_HIS_MSG',
            'cmdId' => (string) $microtime . '000000',
            'time' => $microtime,
            'lang' => 'vi',
            'channel' => 'APP',
            'appVer' => $this->momo_data_config["appVer"],
            'appCode' => $this->momo_data_config["appCode"],
            'deviceOS' => 'ANDROID',
            'appId' => 'vn.momo.platform',
            'result' => true,
            'buildNumber' => 0,
            'errorCode' => 0,
            'errorDesc' => '',
            'extra' =>
            array(
                'checkSum' => $this->generateCheckSum('QUERY_TRAN_HIS_MSG', $microtime),
            ),
            'momoMsg' =>
            array(
                '_class' => 'mservice.backend.entity.msg.QueryTranhisMsg',
                'begin' => $begin,
                'end' => $microtime,
            ),
        );
        return $this->CURL("QUERY_TRAN_HIS_MSG", $header, $this->Encrypt_data($Data, $requestkeyRaw));
    }
    public function CHECK_USER_PRIVATE($receiver)
    {
        $microtime = $this->get_microtime();
        $requestkeyRaw = $this->generateRandom(32);
        $requestkey = $this->RSA_Encrypt($this->config["RSA_PUBLIC_KEY"], $requestkeyRaw);
        $header = array(
            "agent_id: " . $this->config["agent_id"],
            "user_phone: " . $this->config["phone"],
            "sessionkey: " . $this->config["sessionkey"],
            "authorization: Bearer " . $this->config["authorization"],
            "msgtype: CHECK_USER_PRIVATE",
            "userid: " . $this->config["phone"],
            "requestkey: " . $requestkey,
            "Host: owa.momo.vn"
        );
        $Data = '{
            "user":"' . $this->config['phone'] . '",
            "msgType":"CHECK_USER_PRIVATE",
            "cmdId":"' . $microtime . '000000",
            "lang":"vi",
            "time":' . (int) $microtime . ',
            "channel":"APP",
            "appVer": ' . $this->momo_data_config["appVer"] . ',
            "appCode": "' . $this->momo_data_config["appCode"] . '",
            "deviceOS":"ANDROID",
            "buildNumber":1916,
            "appId":"vn.momo.transfer",
            "result":true,
            "errorCode":0,
            "errorDesc":"",
            "momoMsg":
            {
                "_class":"mservice.backend.entity.msg.LoginMsg",
                "getMutualFriend":false
            },
            "extra":
            {
                "CHECK_INFO_NUMBER":"' . $receiver . '",
                "checkSum":"' . $this->generateCheckSum('CHECK_USER_PRIVATE', $microtime) . '"
            }
        }';
        return $this->CURL("CHECK_USER_PRIVATE", $header, $this->Encrypt_data($Data, $requestkeyRaw));
    }

    public function USER_LOGIN_MSG()
    {
        $microtime = $this->get_microtime();
        $header = array(
            "agent_id: " . $this->config["agent_id"],
            "user_phone: " . $this->config["phone"],
            "sessionkey: " . (!empty($this->config["sessionkey"])) ? $this->config["sessionkey"] : "",
            "authorization: Bearer " . $this->config["authorization"],
            "msgtype: USER_LOGIN_MSG",
            "Host: owa.momo.vn",
            "user_id: " . $this->config["phone"],
            "User-Agent: okhttp/3.14.17",
            "app_version: " . $this->momo_data_config["appVer"],
            "app_code: " . $this->momo_data_config["appCode"],
            "device_os: ANDROID"
        );
        $Data = array(
            'user' => $this->config['phone'],
            'msgType' => 'USER_LOGIN_MSG',
            'pass' => $this->config['password'],
            'cmdId' => (string) $microtime . '000000',
            'lang' => 'vi',
            'time' => $microtime,
            'channel' => 'APP',
            'appVer' => $this->momo_data_config["appVer"],
            'appCode' => $this->momo_data_config["appCode"],
            'deviceOS' => 'ANDROID',
            'buildNumber' => 0,
            'appId' => 'vn.momo.platform',
            'result' => true,
            'errorCode' => 0,
            'errorDesc' => '',
            'momoMsg' =>
            array(
                '_class' => 'mservice.backend.entity.msg.LoginMsg',
                'isSetup' => false,
            ),
            'extra' =>
            array(
                'pHash' => $this->get_pHash(),
                'AAID' => $this->config['AAID'],
                'IDFA' => '',
                'TOKEN' => $this->config['TOKEN'],
                'SIMULATOR' => '',
                'SECUREID' => $this->config['SECUREID'],
                'MODELID' => $this->config['MODELID'],
                'checkSum' => $this->generateCheckSum('USER_LOGIN_MSG', $microtime),
            ),
        );
        return $this->CURL("USER_LOGIN_MSG", $header, $Data);
    }
    public function GENERATE_TOKEN_AUTH_MSG()
    {
        $microtime = $this->get_microtime();
        $header = array(
            "agent_id: " . $this->config["agent_id"],
            "user_phone: " . $this->config["phone"],
            "sessionkey: " . (!empty($this->config["sessionkey"])) ? $this->config["sessionkey"] : "",
            "authorization: Bearer " . $this->config["authorization"],
            "msgtype: GENERATE_TOKEN_AUTH_MSG",
            "Host: api.momo.vn",
            "user_id: " . $this->config["phone"],
            "User-Agent: MoMoPlatform-Release/31062 CFNetwork/1325.0.1 Darwin/21.1.0",
            "app_version: " . $this->momo_data_config["appVer"],
            "app_code: " . $this->momo_data_config["appCode"],
            "device_os: ANDROID"
        );
        $Data = array(
            'user' => $this->config['phone'],
            'msgType' => 'GENERATE_TOKEN_AUTH_MSG',
            'cmdId' => (string) $microtime . '000000',
            'lang' => 'vi',
            'time' => $microtime,
            'channel' => 'APP',
            'appVer' => $this->momo_data_config["appVer"],
            'appCode' => $this->momo_data_config["appCode"],
            'deviceOS' => 'ANDROID',
            'buildNumber' => 0,
            'appId' => 'vn.momo.platform',
            'result' => true,
            'errorCode' => 0,
            'errorDesc' => '',
            'momoMsg' =>
            array(
                '_class' => 'mservice.backend.entity.msg.RefreshTokenMsg',
                'refreshToken' => $this->config["refreshToken"],
            ),
            'extra' =>
            array(
                'pHash' => $this->get_pHash(),
                'AAID' => $this->config['AAID'],
                'IDFA' => '',
                'TOKEN' => $this->config['TOKEN'],
                'SIMULATOR' => '',
                'SECUREID' => $this->config['SECUREID'],
                'MODELID' => $this->config['MODELID'],
                'checkSum' => $this->generateCheckSum('GENERATE_TOKEN_AUTH_MSG', $microtime),
            ),
        );
        return $this->CURL("GENERATE_TOKEN_AUTH_MSG", $header, $Data);
    }
    public function CHECK_USER_BE_MSG()
    {
        $microtime = $this->get_microtime();
         $curl = curl_init();

        curl_setopt_array($curl, array(
          CURLOPT_URL => 'https://api.momo.vn/backend/auth-app/public/CHECK_USER_BE_MSG',
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => '',
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => 'POST',
          CURLOPT_POSTFIELDS =>'{
          "cmdId" : "'.(string) $microtime . '000000'.'",
          "momoMsg" : {
            "cname" : "Vietnam",
            "manufacture" : "Apple",
            "icc" : "",
            "mcc" : "452",
            "_class" : "mservice.backend.entity.msg.RegDeviceMsg",
            "secure_id" : "",
            "mnc" : "04",
            "imei" : "'.$this->config["imei"].'",
            "number" : "'.$this->config["phone"].'",
            "ccode" : "084",
            "device_os" : "ios",
            "csp" : "Viettel",
            "firmware" : "15.6",
            "device" : "'.$this->config["device"].'",
            "hardware" : "'.$this->config["hardware"].'"
          },
          "channel" : "APP",
          "appId" : "vn.momo.platform",
          "appVer" : 40171,
          "time" : '.$microtime.',
          "msgType" : "CHECK_USER_BE_MSG",
          "appCode" : "4.0.17",
          "deviceOS" : "ios",
          "buildNumber" : 0,
          "lang" : "vi",
          "user" : "'.$this->config["phone"].'"
        }',
          CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json'
          ),
        ));
        
        $response = curl_exec($curl);
        
        curl_close($curl);
        return $response;
    }

    public function REG_DEVICE_MSG()
    {
        $microtime = $this->get_microtime();
        $header = array(
            "agent_id: undefined",
            "sessionkey:",
            "user_phone: undefined",
            "authorization: Bearer undefined",
            "msgtype: REG_DEVICE_MSG",
            "Host: api.momo.vn",
            "User-Agent: okhttp/3.14.17",
            "app_version: " . $this->momo_data_config["appVer"],
            "app_code: " . $this->momo_data_config["appCode"],
            "device_os: ANDROID"
        );
        $Data = '{
            "user": "' . $this->config["phone"] . '",
            "msgType": "REG_DEVICE_MSG",
            "cmdId": "' . $microtime . '000000",
            "lang": "vi",
            "time": ' . $microtime . ',
            "channel": "APP",
            "appVer": ' . $this->momo_data_config["appVer"] . ',
            "appCode": "' . $this->momo_data_config["appCode"] . '",
            "deviceOS": "ANDROID",
            "buildNumber": 0,
            "appId": "vn.momo.platform",
            "result": true,
            "errorCode": 0,
            "errorDesc": "",
            "momoMsg": {
              "_class": "mservice.backend.entity.msg.RegDeviceMsg",
              "number": "' . $this->config["phone"] . '",
              "imei": "' . $this->config["imei"] . '",
              "cname": "Vietnam",
              "ccode": "084",
              "device": "' . $this->config["device"] . '",
              "firmware": "23",
              "hardware": "' . $this->config["hardware"] . '",
              "manufacture": "' . $this->config["facture"] . '",
              "csp": "",
              "icc": "",
              "mcc": "",
              "device_os": "Android",
              "secure_id": "' . $this->config["SECUREID"] . '"
            },
            "extra": {
              "ohash": "' . $this->config['ohash'] . '",
              "AAID": "' . $this->config["AAID"] . '",
              "IDFA": "",
              "TOKEN": "' . $this->config["TOKEN"] . '",
              "SIMULATOR": "",
              "SECUREID": "' . $this->config["SECUREID"] . '",
              "MODELID": "' . $this->config["MODELID"] . '",
              "checkSum": ""
            }
          }';
        return $this->CURL("REG_DEVICE_MSG", $header, $Data);
    }

    public function SEND_OTP_MSG()
    {
        $this->CHECK_USER_BE_MSG();
        $header = array(
            "agent_id: undefined",
            "sessionkey:",
            "user_phone: undefined",
            "authorization: Bearer undefined",
            "msgtype: SEND_OTP_MSG",
            "Host: api.momo.vn",
            "User-Agent: okhttp/3.14.17",
            "app_version: ".$this->momo_data_config["appVer"],
            "app_code: ".$this->momo_data_config["appCode"],
            "device_os: ANDROID"
        );
        $microtime = $this->get_microtime();
        $Data = array (
            'user' => $this->config['phone'],
            'msgType' => 'SEND_OTP_MSG',
            'cmdId' => (string) $microtime. '000000',
            'lang' => 'vi',
            'time' => $microtime,
            'channel' => 'APP',
            'appVer' => $this->momo_data_config["appVer"],
            'appCode' => $this->momo_data_config["appCode"],
            'deviceOS' => 'ANDROID',
            'buildNumber' => 0,
            'appId' => 'vn.momo.platform',
            'result' => true,
            'errorCode' => 0,
            'errorDesc' => '',
            'momoMsg' => 
            array (
              '_class' => 'mservice.backend.entity.msg.RegDeviceMsg',
              'number' => $this->config['phone'],
              'imei' => $this->config["imei"],
              'cname' => 'Vietnam',
              'ccode' => '084',
              'device' => $this->config["device"],
              'firmware' => '23',
              'hardware' => $this->config["hardware"],
              'manufacture' => $this->config["facture"],
              'csp' => '',
              'icc' => '',
              'mcc' => '452',
              'device_os' => 'Android',
              'secure_id' => $this->config['SECUREID'],
            ),
            'extra' => 
            array (
              'action' => 'SEND',
              'rkey' => $this->config["rkey"],
              'AAID' => $this->config["AAID"],
              'IDFA' => '',
              'TOKEN' => $this->config["TOKEN"],
              'SIMULATOR' => '',
              'SECUREID' => $this->config['SECUREID'],
              'MODELID' => $this->config["MODELID"],
              'isVoice' => false,
              'REQUIRE_HASH_STRING_OTP' => true,
              'checkSum' => '',
            ),
        );
        return $this->CURL("SEND_OTP_MSG",$header,$Data);
}

    public function get_ip_address()
    {
        $isValid = filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
        if (!empty($isValid)) {
            return $_SERVER['REMOTE_ADDR'];
        }
        try {
            $isIpv4 = json_decode(file_get_contents('https://api.ipify.org?format=json'), true);
            return $isIpv4['ip'];
        } catch (\Throwable $e) {
            return '116.107.187.109';
        }
    }

    public function get_TOKEN()
    {
        return  $this->generateRandom(22) . ':' . $this->generateRandom(9) . '-' . $this->generateRandom(20) . '-' . $this->generateRandom(12) . '-' . $this->generateRandom(7) . '-' . $this->generateRandom(7) . '-' . $this->generateRandom(53) . '-' . $this->generateRandom(9) . '_' . $this->generateRandom(11) . '-' . $this->generateRandom(4);
    }
   public function CURL($Action, $header, $data)
    {
        $Data = is_array($data) ? json_encode($data) : $data;
        $curl = curl_init();
        // echo strlen($Data); die;
        $header[] = 'Content-Type: application/json';
        $header[] = 'accept: application/json';
        $header[] = 'Content-Length: ' . strlen($Data);
        $opt = array(
            CURLOPT_URL => $this->URLAction[$Action],
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_POST => empty($data) ? FALSE : TRUE,
            CURLOPT_POSTFIELDS => $Data,
            CURLOPT_CUSTOMREQUEST => empty($data) ? 'GET' : 'POST',
            CURLOPT_HTTPHEADER => $header,
            CURLOPT_ENCODING => "",
            CURLOPT_HEADER => FALSE,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_TIMEOUT => 20,
        );
        curl_setopt_array($curl, $opt);
        $body = curl_exec($curl);
        // echo strlen($body); die;
        if (is_object(json_decode($body))) {
            return json_decode($body, true);
        }
        return json_decode($this->Decrypt_data($body), true);
    }

    public function RSA_Encrypt($key, $content)
    {
        if (empty($this->rsa)) {
            $this->INCLUDE_RSA($key);
        }
        return base64_encode($this->rsa->encrypt($content));
    }

    public function INCLUDE_RSA($key)
    {
        require_once(__DIR__.'/../RSA/Crypt/RSA.php');
        $this->rsa = new Crypt_RSA();
        $this->rsa->loadKey($key);
        $this->rsa->setEncryptionMode(CRYPT_RSA_ENCRYPTION_PKCS1);
        return $this;
    }

    public function Encrypt_data($data, $key)
    {

        $iv = pack('C*', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
        $this->keys = $key;
        return base64_encode(openssl_encrypt(is_array($data) ? json_encode($data) : $data, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv));
    }

    public function Decrypt_data($data)
    {
        $iv = pack('C*', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
        return openssl_decrypt(base64_decode($data), 'AES-256-CBC', $this->keys, OPENSSL_RAW_DATA, $iv);
    }

    public function generateCheckSum($type, $microtime)
    {
        $Encrypt =   $this->config["phone"] . $microtime . '000000' . $type . ($microtime / 1000000000000.0) . 'E12';
        $iv = pack('C*', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
        return base64_encode(openssl_encrypt($Encrypt, 'AES-256-CBC', $this->config["setupKeyDecrypt"], OPENSSL_RAW_DATA, $iv));
    }

    public function get_pHash()
    {
        $data = $this->config["imei"] . "|" . $this->config["password"];
        $iv = pack('C*', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
        return base64_encode(openssl_encrypt($data, 'AES-256-CBC', $this->config["setupKeyDecrypt"], OPENSSL_RAW_DATA, $iv));
    }

    public function get_setupKey($setUpKey)
    {
        $iv = pack('C*', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
        return openssl_decrypt(base64_decode($setUpKey), 'AES-256-CBC', $this->config["ohash"], OPENSSL_RAW_DATA, $iv);
    }

    public function generateRandom($length = 20)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }
    public function get_SECUREID($length = 17)
    {
        $characters = '0123456789abcdef';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    public function generateImei()
    {
        return $this->generateRandomString(8) . '-' . $this->generateRandomString(4) . '-' . $this->generateRandomString(4) . '-' . $this->generateRandomString(4) . '-' . $this->generateRandomString(12);
    }

    public function generateRandomString($length = 20)
    {
        $characters = '0123456789abcdef';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    public function get_string($data)
    {
        return str_replace(array('<', "'", '>', '?', '/', "\\", '--', 'eval(', '<php', '-'), array('', '', '', '', '', '', '', '', '', ''), htmlspecialchars(addslashes(strip_tags($data))));
    }

    public function get_microtime()
    {
        return round(microtime(true) * 1000);
    }
}
?>
