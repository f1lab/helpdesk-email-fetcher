<?php

use Assert\Assertion;
use EmailReplyParser\EmailReplyParser;
use Fetch\Attachment;
use Fetch\Message;
use Fetch\Server;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

class Fetcher
{
    private $webhook;
    private $credentials;
    private $limit;

    /**
     * @param string $webhook
     * @param array  $credentials
     * @param int    $limit
     */
    public function __construct($webhook, array $credentials, $limit = 10)
    {
        Assertion::url($webhook);
        Assertion::keyIsset($credentials, 'host');
        Assertion::keyIsset($credentials, 'port');
        Assertion::keyIsset($credentials, 'user');
        Assertion::keyIsset($credentials, 'password');
        Assertion::greaterThan($limit, 0);

        $this->webhook = $webhook;
        $this->credentials = $credentials;
        $this->limit = $limit;
    }

    public function fetch()
    {
        $unread = $this->getMessages();
        foreach ($unread as $message) {
            $this->sendHook($message);
        }
    }

    /**
     * @return Message[]
     */
    private function getMessages()
    {
        $server = new Server($this->credentials['host'], $this->credentials['port']);
        $server->setAuthentication($this->credentials['user'], $this->credentials['password']);

        // $server->setMailBox('Foo');

        return $server->search('UNSEEN', $this->limit);
    }

    private function sendHook(Message $message)
    {
        $from = $message->getAddresses('from');
        $from = $from['address'];

        if ($from === 'support@helpdesk.f1lab.ru' || $from === 'support@f1lab' || $from === 'helpdesk@f1lab.ru') {
            $message->setFlag(Message::FLAG_SEEN, true);
            return;
        }

        $subject = $message->getSubject();

        $body = EmailReplyParser::parseReply($message->getMessageBody());

        $postData = [
            ['name' => 'sender', 'contents' => $from],
            ['name' => 'subject', 'contents' => $subject],
            ['name' => 'body-plain', 'contents' => $body],
        ];
        if ($message->getAttachments() !== false) {
            /** @var Attachment $attachment */
            foreach ((array)$message->getAttachments() as $attachment) {
                $postData[] = [
                    'name' => $attachment->getFileName(),
                    'filename' => $attachment->getFileName(),
                    'contents' => $attachment->getData(),
                ];
            }
        }

        $client = $this->getClient();
        $client->post($this->webhook, [
            RequestOptions::MULTIPART => $postData,
        ]);

        $message->setFlag(Message::FLAG_SEEN, true);
    }

    private function getClient()
    {
        static $client = null;

        if ($client === null) {
            $client = new Client();
        }

        return $client;
    }
}
