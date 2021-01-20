To install rabbitmq for windows

1)Install from here and run run the exe. after installing erlang 
    - https://www.rabbitmq.com/install-windows.html#installer


2) There must be supported Erlang version installed (https://www.rabbitmq.com/which-erlang.html) .

3) Erlang must be installed using an administrative account

4) It is highly recommended that RabbitMQ is also installed as an administrative account.

To install rabbitmq in macos:

1 First install supported erlang version in admin mode .to check compatibiity(https://www.rabbitmq.com/which-erlang. html)

2 Then download and intsall rabbitmq server 
  (https://www.rabbitmq.com/install-generic-unix.html#installation)

3 or directly download from here https://github.com/rabbitmq/rabbitmq-server/releases/download/v3.8.9/      rabbitmq-server-generic-unix-3.8.9.tar.xz.

Then run the  rabbit-mq service:

1 For macos:

Starting the Server-

To start the server, run the sbin/rabbitmq-server script. This displays a short banner message, concluding with the message "completed with [n] plugins.", indicating that the RabbitMQ broker has been started successfully. To start the server in "detached" mode, use rabbitmq-server -detached. This will run the node process in the background.

Stopping the Server-

To stop a running node, use sbin/rabbitmqctl shutdown. The command will wait for the node process to stop. If the target node is not running, it will exit with an error.
    
2 For windows:

Starting the server- run the rabbitmq-service start app from start menu

Stopping the server- run the rabbitmq-service stop app from start menu

4.Setup php-ampq library for php:

1 Read documentation from here-(https://github.com/php-amqplib/php-amqplib)

2 Ensure you have composer installed on the project file, then run the following command in terminal:

$ composer require php-amqplib/php-amqplib
   
3 That will fetch the library and its dependencies inside your vendor folder. Then you can add the following to your .php    files in order to use the library

require_once __DIR__.'/vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
    
5. Then open the rabbitmq server managment into your local:
      localhost/:15672 as default username and pass as guest

6. Then install mailgun into project path using composer- composer require mailgun/mailgun-php kriswallsmith/buzz nyholm/psr7
7.  Then run test_sendmail.php(as it will send json data into publish.php and publish.php will send it to deicated queue)

8.  Then run test_recemail.php(as it will run the consumer and fetch details from queue and send email with dedicated      protocol and returns a json object).
      
