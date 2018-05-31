<?php
namespace Megaads\Apify\Models;

use Illuminate\Database\Eloquent\Model;

class BaseModel extends Model
{

    protected $guarded = ['id'];
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
        parent::boot();
        static::created(function ($model) {
            $model->publish($model->toJson(), 'megabet.services', 'topic', 'data.' . $model->getTableName() . '.created');
        });
        static::updated(function ($model) {
            $payload = $model->toArray();
            $payload['updated_data'] = $model->getDirty();
            $model->publish(json_encode($payload), 'megabet.services', 'topic', 'data.' . $model->getTableName() . '.updated');
        });
        static::deleted(function ($model) {
            $model->publish($model->toJson(), 'megabet.services', 'topic', 'data.' . $model->getTableName() . '.deleted');
        });
    }

    /**
     * Publish to mesasage queue
     * @params $data
     * @params $exchange: exchange name
     * @params $exchange_type = (fanout (default): emit to queue for all consumers without filter, direct: emit to queue with filter message for consumer)
     * @params $routing_key: using for filter consumers (with exchange_type = 'direct', if exchange_type = 'fanout' => routing_key = '')
     */

    public function publish($data, $exchange, $exchange_type = 'fanout', $routing_key = '')
    {
        $enable = env('APIFY_MQ_ENABLE');
        if ($enable != null && $enable == true) {
            $host = env('APIFY_MQ_HOST');
            $username = env('APIFY_MQ_USERNAME');
            $password = env('APIFY_MQ_PASSWORD');
            $port = env('APIFY_MQ_PORT');
            $connection = new AMQPStreamConnection($host, $port, $username, $password);
            $queueChannel = $connection->channel();
            $queueChannel->exchange_declare($exchange, $exchange_type, false, false, false);
            $messages = new AMQPMessage($data);
            $queueChannel->basic_publish($messages, $exchange, $routing_key);
            $queueChannel->close();
            $connection->close();
        }
    }
}
