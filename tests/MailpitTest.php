<?php

namespace Codeception\Module\Tests;

use Codeception\Lib\ModuleContainer;
use Codeception\Module\Mailpit;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Http\Factory\Guzzle\StreamFactory;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class MailpitTest extends TestCase
{
    private const URL = 'http://test';
    private const PORT = 8888;

    private static $allMessagesJson = <<<JSON
{
    "total": 1,
    "unread": 1,
    "count": 1,
    "messages_count": 1,
    "start": 0,
    "tags": [],
    "messages": [
        {
            "ID": "fc5666d2-b7f2-4c53-a213-75029127887a",
            "MessageID": "fd791a87dace8d118813757450302242@test.com",
            "Read": false,
            "From": {
                "Name": "Jane Smith",
                "Address": "from@test.com"
            },
            "To": [
                {
                    "Name": "John Doe",
                    "Address": "to@test.com"
                }
            ],
            "Cc": [
                {
                    "Name": "Carbon Copy",
                    "Address": "carbon@copy.ru"
                }
            ],
            "Bcc": [
                {
                    "Name": "",
                    "Address": "blind-carbon@copy.ru"
                }
            ],
            "Subject": "An email",
            "Created": "2023-09-16T19:18:33.164Z",
            "Tags": [],
            "Size": 1886,
            "Attachments": 0
        }
    ]
}
JSON;

    private static $singleMessageJson = <<<JSON
{
    "ID": "fc5666d2-b7f2-4c53-a213-75029127887a",
    "MessageID": "fd791a87dace8d118813757450302242@test.com",
    "Read": true,
    "From": {
        "Name": "Jane Smith",
        "Address": "from@test.com"
    },
    "To": [
        {
            "Name": "John Doe",
            "Address": "to@test.com"
        }
    ],
    "Cc": [
        {
            "Name": "Carbon Copy",
            "Address": "carbon@copy.ru"
        }
    ],
    "Bcc": [
        {
            "Name": "",
            "Address": "blind-carbon@copy.ru"
        }
    ],
    "ReplyTo": [
        {
            "Name": "Reply To",
            "Address": "reply-to@test.com"
        }
    ],
    "ReturnPath": "from@test.com",
    "Subject": "An email",
    "Date": "2023-09-16T19:18:33Z",
    "Tags": [],
    "Text": "Plain Text",
    "HTML": "\u003cbody\u003eHTML Text\u003c/body\u003e",
    "Size": 1886,
    "Inline": [],
    "Attachments": []
}
JSON;

    private static $singleHeaderJson = <<<JSON
{
    "X-Priority": [
        "high"
    ]
}
JSON;


    /** @var MockObject&Mailpit */
    private $mailpit;

    public function testFetchEmailsPositive(): void
    {
        $client = $this->buildClient();
        $this->mailpit->setClient($client);
        $this->mailpit->fetchEmails();

        $this->assertEquals(json_decode(self::$allMessagesJson, false)->messages, $this->mailpit->getCurrentInbox());
        $this->assertEquals(json_decode(self::$allMessagesJson, false)->messages, $this->mailpit->getUnreadInbox());
    }

    public function testFetchEmailsNegative(): void
    {
        /** @var MockObject&ClientInterface $client */
        $client = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->getMock();
        $client->expects(self::atLeastOnce())
            ->method('request')
            ->with('GET', '/api/v1/messages')
            ->willThrowException(new \Exception('Test exception'));

        $this->mailpit->setClient($client);

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Exception: Test exception');

        $this->mailpit->fetchEmails();
    }

    /**
     * @dataProvider dataAccessInboxFor
     */
    public function testAccessInboxFor(string $address, array $expectedInbox): void
    {
        $this->mailpit->setClient($this->buildClient());
        $this->mailpit->fetchEmails();
        $this->mailpit->accessInboxFor($address);

        self::assertEquals($expectedInbox, $this->mailpit->getCurrentInbox());
        self::assertEquals($expectedInbox, $this->mailpit->getUnreadInbox());
    }

    public function dataAccessInboxFor(): iterable
    {
        $address1 = 'other-address@mail.ru';
        $decodedEmail = json_decode(self::$allMessagesJson, false)->messages;

        yield 'Mismatch address in "To", "Cc" and "Bcc"' => [$address1, []];

        $address2 = 'to@test.com';

        yield 'Match "To" only' => [$address2, $decodedEmail];

        $address3 = 'carbon@copy.ru';

        yield 'Match "Cc" only' => [$address3, $decodedEmail];

        $address4 = 'blind-carbon@copy.ru';

        yield 'Match "Bcc" only' => [$address4, $decodedEmail];
    }

    /**
     * @dataProvider dataAccessInboxForTo
     */
    public function testAccessInboxForTo(string $address, array $expectedInbox): void
    {
        $this->mailpit->setClient($this->buildClient());
        $this->mailpit->fetchEmails();
        $this->mailpit->accessInboxForTo($address);

        self::assertEquals($expectedInbox, $this->mailpit->getCurrentInbox());
        self::assertEquals($expectedInbox, $this->mailpit->getUnreadInbox());
    }

    public function dataAccessInboxForTo(): iterable
    {
        $address1 = 'other-address@mail.ru';
        $decodedEmail = json_decode(self::$allMessagesJson, false)->messages;

        yield 'Mismatch address in "To"' => [$address1, []];

        $address2 = 'to@test.com';

        yield 'Match "To"' => [$address2, $decodedEmail];
    }

    /**
     * @dataProvider dataAccessInboxForCc
     */
    public function testAccessInboxForCc(string $address, array $expectedInbox): void
    {
        $this->mailpit->setClient($this->buildClient());
        $this->mailpit->fetchEmails();
        $this->mailpit->accessInboxForCc($address);

        self::assertEquals($expectedInbox, $this->mailpit->getCurrentInbox());
        self::assertEquals($expectedInbox, $this->mailpit->getUnreadInbox());
    }

    public function dataAccessInboxForCc(): iterable
    {
        $address1 = 'other-address@mail.ru';
        $decodedEmail = json_decode(self::$allMessagesJson, false)->messages;

        yield 'Mismatch address in "Cc"' => [$address1, []];

        $address2 = 'carbon@copy.ru';

        yield 'Match "Cc"' => [$address2, $decodedEmail];
    }

    /**
     * @dataProvider dataAccessInboxForBcc
     */
    public function testAccessInboxForBcc(string $address, array $expectedInbox): void
    {
        $this->mailpit->setClient($this->buildClient());
        $this->mailpit->fetchEmails();
        $this->mailpit->accessInboxForBcc($address);

        self::assertEquals($expectedInbox, $this->mailpit->getCurrentInbox());
        self::assertEquals($expectedInbox, $this->mailpit->getUnreadInbox());
    }

    public function dataAccessInboxForBcc(): iterable
    {
        $address1 = 'other-address@mail.ru';
        $decodedEmail = json_decode(self::$allMessagesJson, false)->messages;

        yield 'Mismatch address in "Cc"' => [$address1, []];

        $address2 = 'blind-carbon@copy.ru';

        yield 'Match "Bcc"' => [$address2, $decodedEmail];
    }

    public function testDeleteAllEmailsPositive(): void
    {
        /** @var MockObject&ResponseInterface $response */
        $response = $this->getMockBuilder(ResponseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        /** @var MockObject&ClientInterface $client */
        $client = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->getMock();
        $client->expects(self::atLeastOnce())
            ->method('request')
            ->with('DELETE', '/api/v1/messages')
            ->willReturn($response);

        $this->mailpit->setClient($client);
        $this->mailpit->deleteAllEmails();
    }

    public function testDeleteAllEmailsNegative(): void
    {
        /** @var MockObject&ClientInterface $client */
        $client = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->getMock();
        $client->expects(self::atLeastOnce())
            ->method('request')
            ->with('DELETE', '/api/v1/messages')
            ->willThrowException(new \Exception('Test exception'));

        $this->mailpit->setClient($client);

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Exception: Test exception');

        $this->mailpit->deleteAllEmails();
    }

    public function testOpenNextUnreadEmailPositive(): void
    {
        $client = $this->buildClient();
        $this->mailpit->setClient($client);
        $this->mailpit->fetchEmails();

        $this->mailpit->openNextUnreadEmail();

        self::assertEquals(json_decode(self::$singleMessageJson, false), $this->mailpit->getPropOpenedEmail());
    }

    public function testOpenNextUnreadEmailNegative(): void
    {
        $this->mailpit->setUnreadInbox([]);

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Unread Inbox is Empty');

        $this->mailpit->openNextUnreadEmail();
    }

    public function testGrabHeaderFromOpenedEmail()
    {
        $client = $this->buildClient();
        $this->mailpit->setClient($client);
        $this->mailpit->fetchEmails();
        $this->mailpit->openNextUnreadEmail();

        self::assertSame(
            [0 => 'high'],
            $this->mailpit->grabHeaderFromOpenedEmail('X-Priority')
        );
    }

    protected function setUp(): void
    {
        parent::setUp();

        /** @var MockObject&ModuleContainer $mockContainer */
        $mockContainer = $this->getMockBuilder(ModuleContainer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $params = [
            'url' => self::URL,
            'port' => self::PORT,
            'guzzleRequestOptions' => [
                'strict' => false,
            ],
        ];

        $this->mailpit = new class ($mockContainer, $params) extends Mailpit {
            public function setClient(ClientInterface $mailpit): void
            {
                $this->mailpit = $mailpit;
            }

            public function getCurrentInbox(): array
            {
                return $this->currentInbox;
            }

            public function getUnreadInbox(): array
            {
                return $this->unreadInbox;
            }

            public function setUnreadInbox($inbox): void
            {
                $this->unreadInbox = $inbox;
            }

            public function getPropOpenedEmail()
            {
                return $this->openedEmail;
            }
        };
        $this->mailpit->_initialize();
    }

    protected function buildClient(): MockObject&ClientInterface
    {
        /** @var MockObject&ResponseInterface $allMessagesResponse */
        $allMessagesResponse = $this->getMockBuilder(ResponseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $allMessagesResponse
            ->method('getBody')
            ->willReturn((new StreamFactory())->createStream(self::$allMessagesJson));

        /** @var MockObject&ResponseInterface $singleMessageResponse */
        $singleMessageResponse = $this->getMockBuilder(ResponseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $singleMessageResponse
            ->method('getBody')
            ->willReturn((new StreamFactory())->createStream(self::$singleMessageJson));

        /** @var MockObject&ResponseInterface $singleHeaderResponse */
        $singleHeaderResponse = $this->getMockBuilder(ResponseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $singleHeaderResponse
            ->method('getBody')
            ->willReturn((new StreamFactory())->createStream(self::$singleHeaderJson));

        /** @var MockObject&ClientInterface $client */
        $client = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->getMock();
        $client
            ->method('request')
            ->willReturnCallback(
                function (string $method, string $url) use ($allMessagesResponse, $singleMessageResponse, $singleHeaderResponse): ResponseInterface {
                    switch ($url) {
                        case '/api/v1/messages':
                            return $allMessagesResponse;
                        case '/api/v1/message/fc5666d2-b7f2-4c53-a213-75029127887a':
                            return $singleMessageResponse;
                        case '/api/v1/message/fc5666d2-b7f2-4c53-a213-75029127887a/headers':
                            return $singleHeaderResponse;
                    }
                }
            );
        return $client;
    }
}
