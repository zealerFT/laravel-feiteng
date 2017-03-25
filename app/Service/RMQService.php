<?php
namespace App\Service;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
class RMQService
{
    private $conn;

    function __construct()
    {
        $host = config('rmq.connect.host');
        $port = config('rmq.connect.port');
        $user = config('rmq.connect.user');
        $pass = config('rmq.connect.pass');
        $vhost = config('rmq.connect.vhost');
        $this->conn = new AMQPStreamConnection($host, $port, $user, $pass, $vhost);
    }

    /**
     * 向指定queue中推入数据
     * @param $queue
     * @param $msgBody
     * @param $exchange
     * @param $routingKey
     * @param string $exchangeType
     */
    function sendMsg($queue,$msgBody,$exchange,$routingKey,$exchangeType = 'topic')
    {

        //$conn = new AMQPStreamConnection('139.196.108.39', '5670', 'test_admin', '11fd9690-1cc6-499a-ba7d-ca5b77e27c51', 'ghlm_test');
        $ch = $this->conn->channel();
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
        $ch->queue_declare($queue, false, true, false, false);

        /*
            name: $exchange
            type: direct
            passive: false
            durable: true // the exchange will survive server restarts
            auto_delete: false //the exchange won't be deleted once the channel is closed.
        */

        $ch->exchange_declare($exchange, $exchangeType, false, true, false);

        $ch->queue_bind($queue, $exchange ,$routingKey);
        $msg = new AMQPMessage($msgBody, ['content_type' => 'text/json', 'delivery_mode' => 2]);//delivery_mode 1 Non-persistent非连续 2.连续的 persistent
        $ch->basic_publish($msg, $exchange,$routingKey);
        $ch->close();
        $this->conn->close();
    }
}