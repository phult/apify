<?php
namespace Megaads\Apify\Models;

use Illuminate\Database\Eloquent\Model;

class BaseModel extends Model
{
    protected $guarded = ['id', 'created_at', 'updated_at'];
    protected $table = null;

    public function bind($table)
    {
        $this->setTable($table);
    }

    public function newInstance($attributes = [], $exists = false)
    {
        $model = parent::newInstance($attributes, $exists);
        $model->setTable($this->table);
        return $model;
    }

    public static function getTableName()
    {
        return with(new static )->getTable();
    }
    
    protected static function boot()
    {
        // parent::boot();
        // static::created(function ($model) {
        //     $payload = $model->toJson();
        //     $model->publish($payload, config('queue.exchange'), 'topic', 'data.' . $model->getTableName() . '.created');
        // });
        // static::updated(function ($model) {
        //     $payload = $model->toJson();
        //     $payload['updated'] = $model->getDirty();
        //     $model->publish($payload, config('queue.exchange'), 'topic', 'data.' . $model->getTableName() . '.updated');
        // });
        // static::deleted(function ($model) {
        //     $payload = $model->toJson();
        //     $model->publish($payload, config('queue.exchange'), 'topic', 'data.' . $model->getTableName() . '.deleted');
        // });
    }

    /**
     * Publish an event
     * @params $data
     * @params $exchange: queue_name,
     * @params $exchange_type = (direct: emit to queue with filter message for consumer, fanout: emit to queue for all consumers without filter),
     * @params $routing_key: using for filter consumers (with exchange_type = 'direct', if exchange_type = 'fanout' => routing_key = '')
     */
    public function publish($data, $exchange, $exchange_type = 'fanout', $routing_key = '')
    {
        // $host = config('queue.host');
        // $username = config('queue.username');
        // $password = config('queue.password');
        // $port = config('queue.port');
        // $connection = new AMQPStreamConnection($host, $port, $username, $password);
        // $queueChannel = $connection->channel();
        // $queueChannel->exchange_declare($exchange, $exchange_type, false, false, false);
        // $messages = new AMQPMessage($data);
        // $queueChannel->basic_publish($messages, $exchange, $routing_key);
        // $queueChannel->close();
        // $connection->close();
    }
}
