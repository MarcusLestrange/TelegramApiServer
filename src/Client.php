<?php

namespace TelegramSwooleClient;

use \danog\MadelineProto;

class Client {

    public $MadelineProto;

    /**
     * Client constructor.
     */
    public function __construct($root)
    {
        $sessionFile = $root . '/session.madeline';

        $config = Config::getInstance()->get('telegram');

        if (empty($config['connection_settings']['all']['proxy_extra']['address'])) {
            unset($config['connection_settings']);
        }

        //При каждой инициализации настройки обновляются из массива $config
        echo PHP_EOL . 'Starting telegram client ...' . PHP_EOL;
        $time = microtime(true);
        $this->MadelineProto = new MadelineProto\API($sessionFile, $config);
        $this->MadelineProto->start();
        $time = round(microtime(true) - $time, 3);
        echo PHP_EOL . "Client started: $time sec" . PHP_EOL;

    }

    /**
     * Получает последние сообщения из указанных каналов
     *
     * @param array $data
     * <pre>
     * [
     *     'peer'          => '',
     *     'offset_id'     => 0, // (optional)
     *     'offset_date'   => 0, // (optional)
     *     'add_offset'    => 0, // (optional)
     *     'limit'         => 0, // (optional)
     *     'max_id'        => 0, // (optional)
     *     'min_id'        => 0, // (optional)
     *     'hash'          => 0, // (optional)
     * ]
     * </pre>
     * @return array
     */
    public function getHistory($data): array
    {
        $data = array_merge([
            'peer'          => '',
            'offset_id'     => 0,
            'offset_date'   => 0,
            'add_offset'    => 0,
            'limit'         => 0,
            'max_id'        => 0,
            'min_id'        => 0,
            'hash'          => 0,
        ], $data);

        return $this->MadelineProto->messages->getHistory($data);
    }

    /**
     * Пересылает сообщения без ссылки на оригинал
     *
     * @param array $data
     * <pre>
     * [
     *  'from_peer' => '',
     *  'to_peer'   => '',
     *  'id'        => [], //Id сообщения, или нескольких сообщений
     * ]
     * </pre>
     * @return array
     */
    public function copyMessages($data):array {

        $data = array_merge([
            'from_peer' => '',
            'to_peer'   => '',
            'id'        => [],
        ],$data);

        $response = $this->MadelineProto->channels->getMessages([
            'channel'   => $data['from_peer'],
            'id'        => $data['id'],
        ]);
        $result = [];
        if (!$response || !is_array($response) || !array_key_exists('messages', $response)){
            return $result;
        }

        $hasMedia = function($media = []) {
            if (empty($media['_'])) {
                return false;
            }
            if ($media['_'] == 'messageMediaWebPage'){
                return false;
            }
            return true;
        };

        foreach ($response['messages'] as $message) {
            usleep(mt_rand(300,2000)*1000);
            $messageData = [
                'message'   => $message['message'] ?? '',
                'peer'      => $data['to_peer'],
                'entities'  => $message['entities'] ?? [],
            ];
            if (
                $hasMedia($message['media'] ?? [])
            ) {
                $messageData['media'] = $message; //MadelineProto сама достанет все media из сообщения.
                $result[] = $this->sendMedia($messageData);
            } else {
                $result[] = $this->sendMessage($messageData);
            }
        }

        return $result;

    }

    /**
     * @param array $data
     * <pre>
     * [
     *  'q'             => '',  //Поисковый запрос
     *  'offset_id'     => 0,   // (optional)
     *  'offset_date'   => 0,   // (optional)
     *  'limit'         => 10,  // (optional)
     * ]
     * </pre>
     * @return array
     */
    public function searchGlobal(array $data): array
    {
        $data = array_merge([
            'q'             => '',
            'offset_id'     => 0,
            'offset_date'   => 0,
            'limit'         => 10,
        ],$data);
        return $this->MadelineProto->messages->searchGlobal($data);
    }

    /**
     * @param $data
     * <pre>
     * [
     *  'peer'              => '',
     *  'message'           => '',      // Текст сообщения
     *  'reply_to_msg_id'   => 0,       // (optional)
     *  'parse_mode'        => 'HTML',  // (optional)
     * ]
     * </pre>
     * @return array
     */
    public function sendMessage($data = []): array
    {
        $data = array_merge([
            'peer'              => '',
            'message'           => '',
            'reply_to_msg_id'   => 0,
            'parse_mode'        => 'HTML',
        ], $data);

        return $this->MadelineProto->messages->sendMessage($data);
    }

    /**
     * @param $data
     * <pre>
     * [
     *  'peer'              => '',
     *  'message'           => '',      // Текст сообщения,
     *  'media'             => [],      // MessageMedia, Update, Message or InputMedia
     *  'reply_to_msg_id'   => 0,       // (optional)
     *  'parse_mode'        => 'HTML',  // (optional)
     * ]
     * </pre>
     * @return array
     */
    public function sendMedia($data = []): array
    {
        $data = array_merge([
            'peer'              => '',
            'message'           => '',
            'media'             => [],
            'reply_to_msg_id'   => 0,
            'parse_mode'        => 'HTML',
        ], $data);

        return $this->MadelineProto->messages->sendMedia($data);
    }



}