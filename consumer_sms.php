<?php

 chdir(dirname(__DIR__));
 require_once('vendor/autoload.php');
 include_once ("vendor/zenvia/human_gateway_client_api/HumanClientMain.php");
 
 
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exchange\AMQPExchangeType;
use PhpAmqpLib\Message\AMQPMessage;
use Twilio\Rest\Client;

class ConsumerSms
{
    
    public function Sms()
{

   $binding_key='sms';
   $consumertag='sms_con1';
   $exchange = 'ex1';
   $queue = 'sms';
   
   $config=include('config.php');
   $connection = new AMQPStreamConnection($config['host'],$config['port'],$config['user'],$config['pass']);
   
    $channel = $connection->channel();
    $channel->queue_declare($queue, false, true, false, false);
    /*
    name: $exchange
    type: direct
    passive: false
    durable: true // the exchange will survive server restarts
    auto_delete: false //the exchange won't be deleted once the channel is closed.
    */
    $channel->exchange_declare($exchange, AMQPExchangeType::DIRECT, false, true, false);
    $channel->queue_bind($queue, $exchange,$binding_key );
    $channel->basic_qos(
            null,   #prefetch size - prefetch window size in octets, null meaning "no specific limit"
            1,      #prefetch count - prefetch window in terms of whole messages
            null    #global - global=null to mean that the QoS settings should apply per-consumer, global=true to mean that the QoS settings should apply per-channel
           );
        
    $channel->basic_consume(
                $queue,        #queue
                $consumertag,                     #consumer tag - Identifier for the consumer, valid within the current channel. just string
                false,                  #no local - TRUE: the server will not send messages to the connection that published them
                false,                  #no ack, false - acks turned on, true - off.  send a proper acknowledgment from the worker, once we're done with a task
                false,                  #exclusive - queues may only be accessed by the current connection
                false,                  #no wait - TRUE: the server will not respond to the method. The client should not wait for a reply method
                array($this, 'readSms')#callback
                );

    while(count($channel->callbacks)) {
    $channel->wait();
    
    }

    $channel->close();
    $connection->close();
}

   public function readSms(AMQPMessage $msg)
   {
    
     $msgbody=json_decode($msg->body);
     $from=$msgbody->from;
     $to=$msgbody->to;
     $body = $msgbody->body;
     $smstype= $msgbody->smstype;

     if($smstype=='clicksend'){
        
        $clicksend_username= $msgbody->clicksend_username;
        $clicksend_key= $msgbody->clicksend_key;
        $clicksend_senderid= $msgbody->clicksend_senderid;
        //$clicksend_senderno= $msgbody->clicksend_senderno;
        $results=$this->clicksend_sms($to,$body,$clicksend_username,$clicksend_key,$clicksend_senderid);
       echo $results;
       exit();

      }

      elseif($smstype=='twilio')
      {
        $sid = $msgbody->twilio_senderid;
        $token =  $msgbody->twilio_token;
        $sendernumber=  $msgbody->twilio_sendernumber;
        $results=$this->twilio_sms($to,$body,$sid,$token,$sendernumber);
       // echo $results;
        return true;
      }

      elseif($smstype=='smsgatewayhub')
      {
        $sms_api=$msgbody->smsgatewayhub_api;
        $sms_sender=$msgbody->smsgatewayhub_sendername;
        $results=$this->smsgatewayhub_sms($to,$body,$sms_api,$sms_sender);
        //echo $results;
        return true;
      }

      elseif($smstype=='smsalert')
      {
        $sms_api=$msgbody->smsalert_api;
        $sms_sender=$msgbody->smsalert_sendername;
        $results=$this->smsalert_sms($to,$body,$sms_api,$sms_sender);
        //echo 'smsalert';
        //echo $results;
        return true;

      }

      elseif($smstype=='zenvia')
      {
        $zenvia_token=$msgbody->zenvia_token;
        $zenvia_apiendPoint=$msgbody->zenvia_apiendpoint;
        $zenvia_senderid=$msgbody->zenvia_senderid;
        $results=$this->zenvia_sms($to,$body,$zenvia_token,$zenvia_senderid,$zenvia_apiendpoint);
        //echo $results;
         return true;
      }

      elseif($smstype=='uwazii')
      {
        $username=$msgbody->uwazii_username;
        $password=$msgbody->uwazii_password;
        $from=$msgbody->uwazii_fromnumber;
        $curl = curl_init();
        $message=$msgbody->body;
        //$authToken = 'VlNMVEQ6VlNMVEQxMjM0NQ==';
        //$from = $this->config->get('config_uwaziimobile_sms_number');
        //$from = 'cer';
        //$username  = $this->config->get('config_uwaziimobile_sms_token');
        //$password  = $this->config->get('config_uwaziimobile_sms_sender_id');
        //echo "<pre>";print_r($from."c".$username."d".$password);die;0
        $str = $username.":".$password;
        $authToken = base64_encode($str);
        $apiEndPoint = 'http://107.20.199.106/restapi/sms/1/text/single';
       // $apiEndPoint = 'https://api.uwaziimobile.com/api/v2/SendSMS';
        $postData = array(
                            'from' => $from,
                            "to"=>$to,
                            "text"=>$message);
        curl_setopt($curl, CURLOPT_URL,$apiEndPoint);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Accept: application/json','Content-type: application/json','Authorization: Basic'.$authToken)); 
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS,json_encode($postData) );
        try {
          //$response = $request->send();
          $response = curl_exec($curl);
          echo $response;
          $response = json_decode($response,true);
          //echo "<pre>";print_r($response);die;
          /*if(isset($response['messages']) && isset($response['messages'][0]['status']) && $response['messages'][0]['status']['id'] == 0 ) {
            $result['status'] = true;
          } else {
            $result['status'] = false;
            $result['message'] = $response['messages'][0]['status']['description'];
          }*/
          //
        } catch (HttpException $ex) {
         // echo $ex;
            $result['status'] = false;
        }
        //return $result;
        return true;
        }

      $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
      exit();
    }

   public function clicksend_sms($to,$body,$clicksend_username,$clicksend_key,$clicksend_senderid){

    $config = ClickSend\Configuration::getDefaultConfiguration()
              ->setUsername($clicksend_username)
              ->setPassword($clicksend_key);

    $apiInstance = new ClickSend\Api\SMSApi(new GuzzleHttp\Client(),$config);
    $msg = new \ClickSend\Model\SmsMessage();
    $msg->setBody($body); 
    $msg->setTo($to);
    $msg->setFrom($clicksend_senderid);
    $sms_messages = new \ClickSend\Model\SmsMessageCollection(); 
    $sms_messages->setMessages([$msg]);
    try {
    $result = $apiInstance->smsSendPost($sms_messages);
    echo $result;
    
    } 
    catch (Exception $e) {
    echo 'Exception when calling SMSApi->smsSendPost: ', $e->getMessage(), PHP_EOL;
    }
    return true;
    
  
   }

   public function twilio_sms($to='',$body='',$sid='',$token='',$sendernumber=''){
    

    $client = new Client($sid, $token);
    if(substr($to,0,1) != '+') {
      $to = '+'.$to;
  }
    try {
        $sms = $client->messages->create(
        $to,
            array(
                'from' => $sendernumber,
                'body' => $body
            )
        );
    } catch (Exception $exception) { 
      echo $exception;         
      return False;
    } 
    return True;

   }

   public function smsgatewayhub_sms($to='',$body='',$smsgatewayhub_api='',$smsgatewayhub_sender='')
   {
    $apikey = $smsgatewayhub_api;
    $apisender = $smsgatewayhub_sender;
    $msg =$body;
    $num = $to; // MULTIPLE NUMBER VARIABLE PUT HERE...!
    $ms = rawurlencode($msg); //This for encode your message content
    $url = 'https://www.smsgatewayhub.com/api/mt/SendSMS?APIKey='.$apikey.'&senderid='.$apisender.'&channel=2&DCS=0&flashsms=0&number='.$num.'&text='.$ms.'&route=1';
    //echo $url;
    $ch=curl_init($url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch,CURLOPT_POST,1);
    curl_setopt($ch,CURLOPT_POSTFIELDS,"");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER,2);
    $data = curl_exec($ch);
    echo '';
    //echo $data;
    if($data){
    return true;}
   }

   public function smsalert_sms($to='',$body='',$api='',$sendername='')
    {
       
      $curl = curl_init();
      $ms = rawurlencode($body);
      curl_setopt_array($curl, array(
      CURLOPT_URL =>"https://www.smsalert.co.in/api/push.json?apikey=".$api."&sender=".$sendername."&mobileno=".$to."&text=".$ms,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_ENCODING => "",
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "POST", 
      ));
    
      $response = curl_exec($curl);
  
      curl_close($curl);
      //return $response;
      return true;
    }


    public function zenvia_sms($to,$body,$zenvia_token,$zenvia_senderid,$zenvia_apiendpoint)
              
    {


      $postData = array(
        'sendSmsRequest' => array(
            'from' => $zenvia_senderid,
            "to"=>$to,
            "msg"=>$body,
            "callbackOption"=>"NONE",
            "id"=>uniqid(),
            "aggregateId"=>"1111"

         ));
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL,$zenvia_apiendpoint);
        //curl_setopt($curl, CURLOPT_URL,"https://api-rest.zenvia360.com.br/services/send-sms");
        //https://api-rest.zenvia360.com.br/services/send-sms
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Accept: application/json','Content-type: application/json','Authorization: Basic '.$authToken)); 

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS,json_encode($postData) );

        $response = curl_exec($curl);

        $response = json_decode($response,true);

        if (isset($response['sendSmsResponse'])) {
            if(is_array($response['sendSmsResponse'])) {
                  if($response['sendSmsResponse']['statusCode'] == 00) {
                    return true;

                  } else {
                       return false;    
                  }
            
                }    
         }
      curl_close($curl);

         return true;
    }

  /* public function zenvia_sms($to='',$body='',$account='',$password='',$msgid='')
    {
    
      $callbackOption=  HumanSimpleSend::CALLBACK_INACTIVE;
      $sender = new HumanSimpleSend($account, $password);
      $message = new HumanSimpleMessage();
      $message->setBody($body);
      $message->setTo($to);
      $message->setMsgId($msgId);
      $response = $sender->sendMessage($message, $callbackOption);
      echo $response->getCode() . " - " . $response->getMessage() . "<br />";
    
    }*/




    //uwazii New Skeleton
   /* public function uwazii_sms($to='',$body='',$senderid='',$apikey='')
    {
     
      $ch = curl_init();

      curl_setopt($ch, CURLOPT_URL, "https://api.uwaziimobile.com/api/v2/SendSMS");
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
      curl_setopt($ch, CURLOPT_HEADER, FALSE);
      
      curl_setopt($ch, CURLOPT_POST, TRUE);
      
      curl_setopt($ch, CURLOPT_POSTFIELDS, "{
        \"sendSmsRequest\": {
          \"Senderid\": \"$senderid\",
          \"to\": \"$to\",
          \"msg\": \"$body\",
          \"id\": \"002\",
          \"Is_Unicode\":\" true\",  
          \"Is_Flash\": \"true\", 
          \"ApiKey\": \"$apikey\"
        }
      }");
      
      curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "Content-Type: application/json",
        "Authorization: Basic YWRtaW46YWRtaW4=",
        "Accept: application/json"
      ));
      
      $response = curl_exec($ch);
      curl_close($ch);
      
      var_dump($response);

   
    }*/

  






}

  


?>