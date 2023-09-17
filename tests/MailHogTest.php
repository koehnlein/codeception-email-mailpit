<?php

namespace Codeception\Module\Tests;

use Codeception\Lib\ModuleContainer;
use Codeception\Module\MailHog;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Http\Factory\Guzzle\StreamFactory;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class MailHogTest extends TestCase
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

    /** @var MockObject&MailHog */
    private $mailHog;

    public function testFetchEmailsPositive(): void
    {
        $client = $this->buildClient();
        $this->mailHog->setClient($client);
        $this->mailHog->fetchEmails();

        $this->assertEquals(json_decode(self::$allMessagesJson, false)->messages, $this->mailHog->getCurrentInbox());
        $this->assertEquals(json_decode(self::$allMessagesJson, false)->messages, $this->mailHog->getUnreadInbox());
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

        $this->mailHog->setClient($client);

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Exception: Test exception');

        $this->mailHog->fetchEmails();
    }

    /**
     * @dataProvider dataAccessInboxFor
     */
    public function testAccessInboxFor(string $address, array $expectedInbox): void
    {
        $this->mailHog->setClient($this->buildClient());
        $this->mailHog->fetchEmails();
        $this->mailHog->accessInboxFor($address);

        self::assertEquals($expectedInbox, $this->mailHog->getCurrentInbox());
        self::assertEquals($expectedInbox, $this->mailHog->getUnreadInbox());
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
        $this->mailHog->setClient($this->buildClient());
        $this->mailHog->fetchEmails();
        $this->mailHog->accessInboxForTo($address);

        self::assertEquals($expectedInbox, $this->mailHog->getCurrentInbox());
        self::assertEquals($expectedInbox, $this->mailHog->getUnreadInbox());
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
        $this->mailHog->setClient($this->buildClient());
        $this->mailHog->fetchEmails();
        $this->mailHog->accessInboxForCc($address);

        self::assertEquals($expectedInbox, $this->mailHog->getCurrentInbox());
        self::assertEquals($expectedInbox, $this->mailHog->getUnreadInbox());
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
        $this->mailHog->setClient($this->buildClient());
        $this->mailHog->fetchEmails();
        $this->mailHog->accessInboxForBcc($address);

        self::assertEquals($expectedInbox, $this->mailHog->getCurrentInbox());
        self::assertEquals($expectedInbox, $this->mailHog->getUnreadInbox());
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

        $this->mailHog->setClient($client);
        $this->mailHog->deleteAllEmails();
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

        $this->mailHog->setClient($client);

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Exception: Test exception');

        $this->mailHog->deleteAllEmails();
    }

    public function testOpenNextUnreadEmailPositive(): void
    {
        $this->mailHog->setUnreadInbox(json_decode(self::$allMessagesJson, false)->messages);

        $client = $this->buildClient();
        $this->mailHog->setClient($client);
        $this->mailHog->fetchEmails();

        $this->mailHog->openNextUnreadEmail();

        self::assertEquals(json_decode(self::$singleMessageJson, false), $this->mailHog->getPropOpenedEmail());
    }

    public function testOpenNextUnreadEmailNegative(): void
    {
        $this->mailHog->setUnreadInbox([]);

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Unread Inbox is Empty');

        $this->mailHog->openNextUnreadEmail();
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

        $mailHog = new class ($mockContainer, $params) extends MailHog {
            public function setClient(ClientInterface $mailhog): void
            {
                $this->mailhog = $mailhog;
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

        $this->mailHog = $mailHog;
        $this->mailHog->_initialize();
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

        /** @var MockObject&ClientInterface $client */
        $client = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->getMock();
        $client
            ->method('request')
            ->willReturnCallback(
                function (string $method, string $url) use ($allMessagesResponse, $singleMessageResponse): ResponseInterface {
                    switch ($url) {
                        case '/api/v1/messages':
                            return $allMessagesResponse;
                        case '/api/v1/messages/fc5666d2-b7f2-4c53-a213-75029127887a':
                            return $singleMessageResponse;
                    }
                }
            );
        return $client;
    }
}
