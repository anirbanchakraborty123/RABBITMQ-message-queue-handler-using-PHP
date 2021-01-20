<?php

 chdir(dirname(__DIR__));
 require_once('vendor/autoload.php');
 

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exchange\AMQPExchangeType;
use PhpAmqpLib\Message\AMQPMessage;
use paragraph1\phpFCM\Client;
use paragraph1\phpFCM\Message;
use paragraph1\phpFCM\Recipient\Device;
use paragraph1\phpFCM\Notification;


class ConsumerPushNotify   
{
    
    public function PushNotify()
    {
        $binding_key='push_notify';
        $consumertag='push_con1';
        $exchange = 'ex1';
        $queue = 'push_notify';
        
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
                     array($this, 'getpushnotify'));
     
         while(count($channel->callbacks)){
         $channel->wait();
         
        }
     
         $channel->close();
         $connection->close();
    }


    public function getpushnotify(AMQPMessage $msg)
    {
     
     $msgbody=json_decode($msg->body,true);
     //print_r($msgbody['data']);
     //$msg=json_decode($msg->body);
     //$data=$msg->data;

      if(!array_key_exists("apiKey",$msgbody)){
         $msgbody['apiKey']='';
         $apiKey=$msgbody['apiKey'];
         
         }
          else{
          $apiKey=$msgbody['apiKey'];
         }


      if(!array_key_exists("device_id",$msgbody)){
         $msgbody['device_id']='';
         $device_id=$msgbody['device_id'];
            
         }
             else{
             $device_id=$msgbody['device_id'];
            }

         if(!array_key_exists("sound",$msgbody)){
            $msgbody['sound']='default';
            $sound=$msgbody['sound'];
                           
                  }
                  else{
                        $sound=$msgbody['sound'];
                     }

         if(!array_key_exists("app_action",$msgbody)){
            $msgbody['app_action']='';
            $app_action=$msgbody['app_action'];
                                                                                                                     
            }
            else{
               $app_action=$msgbody['app_action'];
            }

        if(!array_key_exists("data",$msgbody)){
                     $msgbody['data']=array();
                     $data1=$msgbody['data'];
                                                                                                                  
                }
               else{
                  
                  $data1=$msgbody['data'];

                  }
         if(!array_key_exists("badge",$msgbody)){
                     $msgbody['badge']='1';
                     $badge=$msgbody['badge'];
                                                                                                                  
                }
               else{
                  
                  $badge=$msgbody['badge'];

                  }
            
                                   
    // print_r($data1);
    // $message = htmlspecialchars_decode($message);
    // $title = htmlspecialchars_decode($title);
     $results=$this->sendpushnotify($apiKey,$device_id,$sound,$app_action,$badge,$data1);
     //echo $results;
     $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
     //return true;
     exit();

   }


    public function sendpushnotify($apiKey,$device_id,$sound,$app_action,$badge,$data1)
    {
      $dat=json_encode($data1);
     $data=json_decode($dat);
    // print_r($data);
     $title=$data->title;
     $message=$data->message;
     $client = new Client();
     $client->setApiKey($apiKey);
     $client->injectHttpClient(new \GuzzleHttp\Client());
    
     $note = new Notification($title,$message);
     $note->setIcon('notification_icon_resource_name')
         ->setSound($sound)
         ->setClickAction($app_action)
         ->setBadge($badge);
     
     $message = new Message();
     $message->addRecipient(new Device($device_id));
     $message->setNotification($note);
     $message->setData($data1);
     $response = $client->send($message);
     if($response->getStatusCode()){
     return true;
     }
     else{
        return false;
     }
    
       return true;
    }
    

   }

?>