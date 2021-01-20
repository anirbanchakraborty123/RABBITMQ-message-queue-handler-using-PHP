<?php
chdir(dirname(__DIR__));
require_once('vendor/autoload.php');
//require_once('consumer_email.php');



use PhpAmqpLib\Connection\AMQPStreamConnection;   
use PhpAmqpLib\Exchange\AMQPExchangeType;
use PhpAmqpLib\Message\AMQPMessage;
class Publisher
{
 public function connect(){
    $config=include('config.php');
    $connection = new AMQPStreamConnection($config['host'],$config['port'],$config['user'],$config['pass']);
    return $connection;
    
    }
    
 public function sendemail($data){
     

$binding_key='email';
$exchange = 'ex1';
$queue = 'email';

$connection=$this->connect();
$channel = $connection->channel();

$channel->set_ack_handler(
    function (AMQPMessage $messageemail){
       /* $status=json_encode(['status'=>1]);
        header('Content-Type: application/json');*/
        print_r( $messageemail->body);
        //print_r( $status);
        return true;
    }
);

$channel->set_nack_handler(
    function (AMQPMessage $messageemail){
      /*  $status=json_encode(['status'=>0]);
        header('Content-Type: application/json');*/
        print_r( $messageemail->body);
      //  print_r( $status);*/
        return false;
        
    }
);

$channel->confirm_select();

/*
    The following code is the same both in the consumer and the producer.
    In this way we are sure we always have a queue to consume from and an
        exchange where to publish messages.
*/

/*
    name: $queue
    passive: false
    durable: true // the queue will survive server restarts
    exclusive: false // the queue can be accessed in other channels
    auto_delete: false //the queue won't be deleted once the channel is closed.
*/
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

$messageEmail=json_encode($data);

    $messageemail = new AMQPMessage($messageEmail, array('content_type' => 'application/json', 'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT));
    $channel->basic_publish($messageemail, $exchange,$binding_key);    
    $channel->wait_for_pending_acks();

    $channel->close();

    $connection->close();
    
 }

    
 public function sendsms($data){

    $binding_key='sms';
    $exchange = 'ex1';
    $queue = 'sms';
    
    
    
    $connection=$this->connect();
    $channel = $connection->channel();
    
    $channel->set_ack_handler(
        function (AMQPMessage $messageemail){
             
          /*  $status=json_encode(['status'=>1]);
            header('Content-Type: application/json');*/
            print_r($messageemail->body);
           // print_r($status);*/
            return true;
          
        }
    );
    
    $channel->set_nack_handler(
        function (AMQPMessage $messageemail){
         
         /*   $status=json_encode(['status'=>0]);
            header('Content-Type: application/json');*/
            print_r($messageemail->body);
           // print_r($status);*/
            return false;
              
        }
    );
    
    $channel->confirm_select();
    /*
        name: $queue
        passive: false
        durable: true // the queue will survive server restarts
        exclusive: false // the queue can be accessed in other channels
        auto_delete: false //the queue won't be deleted once the channel is closed.
    */
    $channel->queue_declare($queue, false, true, false, false);
    /*
        name: $exchange
        type: direct
        passive: false
        durable: true // the exchange will survive server restarts
        auto_delete: false //the exchange won't be deleted once the channel is closed.
    */
    $channel->exchange_declare($exchange, AMQPExchangeType::DIRECT, false, true, false);
    $channel->queue_bind($queue, $exchange,$binding_key);
    
    $messageEmail=json_encode($data);

    $messageemail = new AMQPMessage($messageEmail, array('content_type' => 'application/json', 'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT));
  
    $channel->basic_publish($messageemail, $exchange,$binding_key);
        
    $channel->wait_for_pending_acks();
    
    $channel->close();
    
    $connection->close();

    }


    public function SendPushNotifications($data,$data1){

    
        $binding_key='push_notify';
        $exchange = 'ex1';
        $queue = 'push_notify';
        
        $connection=$this->connect();
        $channel = $connection->channel();
        
        $channel->set_ack_handler(
            function (AMQPMessage $messageemail){
                 
               /* $status=json_encode(['status'=>1]);
                header('Content-Type: application/json');*/
                print_r($messageemail->body);
             //   print_r($status);
              
              return true;
            }
        );
        
        $channel->set_nack_handler(
            function (AMQPMessage $messageemail){
             
               /* $status=json_encode(['status'=>0]);
                header('Content-Type: application/json');*/
                print_r($messageemail->body);
                //print_r($status);*/
                return false;
                  
            }
        );
        
        $channel->confirm_select();
        /*
            name: $queue
            passive: false
            durable: true // the queue will survive server restarts
            exclusive: false // the queue can be accessed in other channels
            auto_delete: false //the queue won't be deleted once the channel is closed.
        */
        $channel->queue_declare($queue, false, true, false, false);
        /*
            name: $exchange
            type: direct
            passive: false
            durable: true // the exchange will survive server restarts
            auto_delete: false //the exchange won't be deleted once the channel is closed.
        */
        $channel->exchange_declare($exchange, AMQPExchangeType::DIRECT, false, true, false);
        $channel->queue_bind($queue, $exchange,$binding_key);
        
        $messageEmail=json_encode($data);
        
    
        $messageemail = new AMQPMessage($messageEmail, array('content_type' => 'application/json', 'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT));
      
        $channel->basic_publish($messageemail, $exchange,$binding_key);
            
        $channel->wait_for_pending_acks();
        
        $channel->close();
        
        $connection->close();
    
        }
}

?>