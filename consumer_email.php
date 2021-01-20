<?php

 chdir(dirname(__DIR__));
 require_once('vendor/autoload.php');
 
 
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exchange\AMQPExchangeType;
use PhpAmqpLib\Message\AMQPMessage;
use Mailgun\Mailgun;
use Aws\Ses\SesClient;
use Aws\Exception\AwsException;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;


class ConsumerEmail
{
    
    public function Email()
    
    {

    $binding_key='email';
    $consumertag='email_con1';
    $exchange = 'ex1';
    $queue = 'email';
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
                array($this, 'readEmail')#callback
                );

    while(count($channel->callbacks)) {
    $channel->wait();
    }

    $channel->close();
    $connection->close();
    }
    


   public function readEmail(AMQPMessage $msg)
   {
       
        $msgbody=json_decode($msg->body);
        $subject=$msgbody->subject;
        $from=$msgbody->from_email;
        $to=$msgbody->to_email;
        $body = $msgbody->body;
        $mailtype=$msgbody->mailtype;
        if($mailtype=='mailgun')
       {
        $api = $msgbody->mailgun_api;
        $domain = $msgbody->mailgun_domain;
        $result=$this->mailgun_sendmail($to,$subject,$body,$from,$api,$domain);
        //return true;
        //exit();
       }

        elseif($mailtype=='sendgrid')
       {
        $api = $msgbody->sendgrid_api;
        $result=403;
        while($result!=202){
        $result=$this->sendgrid_sendmail($to,$subject,$body,$from,$api);
        return true;
        //echo $result;
          }
        }

        elseif($mailtype=='aws')
       {
        $aws_key = $msgbody->aws_key;
        $aws_secretkey = $msgbody->aws_secretkey;
        $aws_region = $msgbody->aws_region;
        $result=$this->aws_sendmail($to,$subject,$body,$from,$aws_key,$aws_secretkey,$aws_region);
        return true;
       }

       elseif($mailtype=='SMTP')
       {
        $smtp_host = $msgbody->smtp_host;
        $smtp_username = $msgbody->smtp_username;
        $smtp_pass =$msgbody->smtp_pass;
        $smtp_port = $msgbody->smtp_port;
        $smtp_security =$msgbody->smtp_security;
        $result=$this->smtp_sendmail($to,$subject,$body,$from,$smtp_host,$smtp_username,$smtp_pass,$smtp_port,$smtp_security);
        echo $result; 
        //return true;
       // return true;
        //exit();
        // echo $result;  
       }

        $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
        //exit();
      
    }

  
    public function mailgun_sendmail($to,$subject,$text,$from,$api,$domain)
    {
      $mgClient = new Mailgun($api);
      $result = $mgClient->sendMessage($domain, array(

	'from'	=> $from,
	'to'	=> $to,
	'subject' => $subject,
	'text'	=> $text
     
    ));
   // var_dump($result);  
    return true;
    }

    public function sendgrid_sendmail($to,$subject,$body,$from,$api)
    {
  
        $email = new \SendGrid\Mail\Mail(); 
        $email->setFrom($from);
        $email->setSubject($subject);
        $email->addTo($to);
        $email->addContent("text/plain", $body);
        $email->addContent(
                      "text/html", $body);
        $sendgrid = new \SendGrid($api);
    try {
        $response = $sendgrid->send($email);
        print_r($response->headers());
        print $response->body() . "\n";
       // return $response->statusCode() . "\n";
        return true;
    } catch (Exception $e) {
        echo 'Caught exception: '. $e->getMessage() ."\n";
    }

    }

    public function aws_sendmail($to,$subject,$body,$from,$aws_key,$aws_secretkey,$aws_region)
   {
 
    $SesClient = new SesClient([

    'version' => '2010-12-01',
    'region'  => $aws_region,
    'credentials'=>[
        'key' => $aws_key,
        'secret' => $aws_secretkey
    ]
    ]);
    $sender_email = $from;

    $recipient_emails =[$to];

    $subject = 'Amazon SES test (AWS SDK for PHP)';
    $plaintext_body = 'This email was sent with Amazon SES using the AWS SDK for PHP.' ;
    $html_body =  '<h1>AWS Amazon Simple Email Service Test Email</h1>'.
              '<p>This email was sent with <a href="https://aws.amazon.com/ses/">'.
              'Amazon SES</a> using the <a href="https://aws.amazon.com/sdk-for-php/">'.
              'AWS SDK for PHP</a>.</p>';
    $char_set = 'UTF-8';

    try {
    $result = $SesClient->sendEmail([
        'Destination' => [
            'ToAddresses' => $recipient_emails,
        ],
        'ReplyToAddresses' => [$sender_email],
        'Source' => $sender_email,
        'Message' => [
          'Body' => [
              'Html' => [
                  'Charset' => $char_set,
                  'Data' => $html_body,
              ],
              'Text' => [
                  'Charset' => $char_set,
                  'Data' => $plaintext_body,
              ],
          ],
          'Subject' => [
              'Charset' => $char_set,
              'Data' => $subject,
          ],
        ]
    ]);
    $messageId = $result['MessageId'];
    //return $messageId;
    //return true;
    } catch (AwsException $e) {
    // output error message if fails
    //echo $e->getMessage();
    echo("The email was not sent. Error message: ".$e->getAwsErrorMessage()."\n");
    //echo "\n";
    }

}

   public function smtp_sendmail($to,$subject,$body,$from,$smtp_host,$smtp_username,$smtp_pass,$smtp_port,$smtp_security)
   {
  
    $mail = new PHPMailer(true);
    try {
       
    $mail->isSMTP();                                            // Send using SMTP
    $mail->Host       = $smtp_host;                             // Set the SMTP server to send through
    $mail->SMTPAuth   = true;                                   // Enable SMTP authentication
    $mail->Username   = $smtp_username;                         // SMTP username
    $mail->Password   = $smtp_pass;                             // SMTP password
    $mail->SMTPSecure = $smtp_security;                         // Enable TLS encryption; `PHPMailer::ENCRYPTION_SMTPS` encouraged
    $mail->Port       = $smtp_port;                                    // TCP port to connect to, use 465 for `PHPMailer::ENCRYPTION_SMTPS` above

    //Recipients
    $mail->setFrom($smtp_username);
    $mail->addAddress($to);     // Add a recipient
    $mail->addReplyTo($smtp_username);
    $mail->Subject =$subject;
    $mail->Body =$body;
    //$mail->addCC('cc@example.com');
    //$mail->addBCC('bcc@example.com');

    // Attachments
    //$mail->addAttachment('/var/tmp/file.tar.gz');         // Add attachments
    //$mail->addAttachment('/tmp/image.jpg', 'new.jpg');    // Optional name
    $mail->send();
    echo 'sent';
    //return True;
    } catch (Exception $e) {
    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    //return False;
    
}

}
    

}
?>