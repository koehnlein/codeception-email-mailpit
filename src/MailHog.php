<?php

/*
 * This file is part of the Mailhog service provider for the Codeception Email Testing Framework.
 * (c) 2015-2016 Eric Martel
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Codeception\Module;

use Codeception\Module;
use Codeception\TestInterface;
use Exception;
use GuzzleHttp\Client;

class MailHog extends Module
{
    use TestsEmails;

    /**
     * Codeception exposed variables.
     */
    protected array $config = [
        'url',
        'port',
        'guzzleRequestOptions',
        'deleteEmailsAfterScenario',
        'timeout',
    ];

    /**
     * Codeception required variables.
     */
    protected array $requiredFields = ['url', 'port'];

    /**
     * HTTP Client to interact with MailHog.
     */
    protected Client $mailhog;

    /**
     * Raw email header data converted to JSON.
     */
    protected array $fetchedEmails;

    /**
     * Currently selected set of email headers to work with.
     */
    protected array $currentInbox;

    /**
     * Starts as the same data as the current inbox, but items are removed as they're used.
     */
    protected array $unreadInbox;

    /**
     * Contains the currently open email on which test operations are conducted.
     */
    protected mixed $openedEmail;

    public function _initialize(): void
    {
        $url = trim($this->config['url'], '/') . ':' . $this->config['port'];

        $config = ['base_uri' => $url, 'timeout' => $this->config['timeout'] ?? 1];
        if (isset($this->config['guzzleRequestOptions']) && is_array($this->config['guzzleRequestOptions'])) {
            foreach ($this->config['guzzleRequestOptions'] as $option => $value) {
                $config[$option] = $value;
            }
        }

        $this->mailhog = new Client($config);
    }

    /**
     * Method executed after each scenario.
     */
    public function _after(TestInterface $test): void
    {
        if (isset($this->config['deleteEmailsAfterScenario']) && $this->config['deleteEmailsAfterScenario']) {
            $this->deleteAllEmails();
        }
    }

    /**
     * Fetch Emails.
     *
     * Accessible from tests, fetches all emails
     */
    public function fetchEmails(): void
    {
        $this->fetchedEmails = [];

        try {
            $response = $this->mailhog->request('GET', '/api/v1/messages');
            $this->fetchedEmails = json_decode($response->getBody(), false)->messages;
        } catch (Exception $e) {
            $this->fail('Exception: ' . $e->getMessage());
        }
        $this->sortEmails($this->fetchedEmails);

        // by default, work on all emails
        $this->setCurrentInbox($this->fetchedEmails);
    }

    /**
     * Access Inbox For *.
     *
     * Filters emails to only keep those that are received by the provided address
     *
     * @param string $address Recipient address' inbox
     */
    public function accessInboxFor(string $address): void
    {
        $inbox = [];

        foreach ($this->fetchedEmails as $email) {
            if (
                $this->findInNamesAndAddresses($email->To ?? [], $address)
                || $this->findInNamesAndAddresses($email->Cc ?? [], $address)
                || $this->findInNamesAndAddresses($email->Bcc ?? [], $address)
            ) {
                $inbox[] = $email;
            }
        }
        $this->setCurrentInbox($inbox);
    }

    /**
     * Access Inbox For To.
     *
     * Filters emails to only keep those that are received by the provided address
     *
     * @param string $address Recipient address' inbox
     */
    public function accessInboxForTo(string $address): void
    {
        $inbox = [];

        foreach ($this->fetchedEmails as $email) {
            if ($this->findInNamesAndAddresses($email->To ?? [], $address)) {
                $inbox[] = $email;
            }
        }
        $this->setCurrentInbox($inbox);
    }

    /**
     * Access Inbox For CC.
     *
     * Filters emails to only keep those that are received by the provided address
     *
     * @param string $address Recipient address' inbox
     */
    public function accessInboxForCc(string $address): void
    {
        $inbox = [];

        foreach ($this->fetchedEmails as $email) {
            if ($this->findInNamesAndAddresses($email->Cc ?? [], $address)) {
                $inbox[] = $email;
            }
        }
        $this->setCurrentInbox($inbox);
    }

    /**
     * Access Inbox For BCC.
     *
     * Filters emails to only keep those that are received by the provided address
     *
     * @param string $address Recipient address' inbox
     */
    public function accessInboxForBcc($address): void
    {
        $inbox = [];

        foreach ($this->fetchedEmails as $email) {
            if ($this->findInNamesAndAddresses($email->Bcc ?? [], $address)) {
                $inbox[] = $email;
            }
        }
        $this->setCurrentInbox($inbox);
    }

    /**
     * Delete All Emails.
     *
     * Accessible from tests, deletes all emails
     */
    public function deleteAllEmails(): void
    {
        try {
            $this->mailhog->request('DELETE', '/api/v1/messages');
        } catch (Exception $e) {
            $this->fail('Exception: ' . $e->getMessage());
        }
    }

    /**
     * Open Next Unread Email.
     *
     * Pops the most recent unread email and assigns it as the email to conduct tests on
     */
    public function openNextUnreadEmail(): void
    {
        $this->openedEmail = $this->getMostRecentUnreadEmail();
    }

    /**
     * Find Address/Name item which contains the current search string
     *
     * @param array<\stdClass> $haystack
     */
    protected function findInNamesAndAddresses(array $haystack, string $needle): ?\stdClass
    {
        foreach ($haystack as $row) {
            if ($row->Address === $needle || $row->Name === $needle) {
                return $row;
            }
        }

        return null;
    }

    /**
     * Get Opened Email.
     *
     * Main method called by the tests, providing either the currently open email or the next unread one
     *
     * @param bool $fetchNextUnread Goes to the next Unread Email
     *
     * @return mixed Returns a JSON encoded Email
     */
    protected function getOpenedEmail($fetchNextUnread = false)
    {
        if ($fetchNextUnread || $this->openedEmail === null) {
            $this->openNextUnreadEmail();
        }

        return $this->openedEmail;
    }

    /**
     * Get Most Recent Unread Email.
     *
     * Pops the most recent unread email, fails if the inbox is empty
     *
     * @return mixed Returns a JSON encoded Email
     */
    protected function getMostRecentUnreadEmail()
    {
        if (count($this->unreadInbox) === 0) {
            $this->fail('Unread Inbox is Empty');
        }

        $email = array_shift($this->unreadInbox);

        return $this->getFullEmail($email->ID);
    }

    /**
     * Get Full Email.
     *
     * Returns the full content of an email
     *
     * @param string $id ID from the header
     *
     * @return mixed Returns a JSON encoded Email
     */
    protected function getFullEmail($id)
    {
        try {
            $response = $this->mailhog->request('GET', "/api/v1/messages/{$id}");

            return json_decode($response->getBody(), false);
        } catch (Exception $e) {
            $this->fail('Exception: ' . $e->getMessage());
        }
    }

    /**
     * Get Email Subject.
     *
     * Returns the subject of an email
     *
     * @param mixed $email Email
     *
     * @return string Subject
     */
    protected function getEmailSubject($email): string
    {
        return $this->getDecodedEmailProperty($email, $email->Content->Headers->Subject[0]);
    }

    /**
     * Get Email Body.
     *
     * Returns the body of an email
     *
     * @param mixed $email Email
     *
     * @return string Body
     */
    protected function getEmailTextBody($email): string
    {
        return $email->Text;
    }

    /**
     * Get Email HTML Body.
     *
     * Returns the body of an email
     *
     * @param mixed $email Email
     *
     * @return string Body
     */
    protected function getEmailHtmlBody($email): string
    {
        return $email->HTML;
    }

    /**
     * Get Email To.
     *
     * Returns the string containing the persons included in the To field
     *
     * @param mixed $email Email
     *
     * @return string To
     */
    protected function getEmailTo($email): string
    {
        return $this->getDecodedEmailProperty($email, $email->Content->Headers->To[0]);
    }

    /**
     * Get Email CC.
     *
     * Returns the string containing the persons included in the CC field
     *
     * @param mixed $email Email
     *
     * @return string CC
     */
    protected function getEmailCC($email): string
    {
        $emailCc = '';
        if (isset($email->Content->Headers->Cc)) {
            $emailCc = $this->getDecodedEmailProperty($email, $email->Content->Headers->Cc[0]);
        }

        return $emailCc;
    }

    /**
     * Get Email BCC.
     *
     * Returns the string containing the persons included in the BCC field
     *
     * @param mixed $email Email
     *
     * @return string BCC
     */
    protected function getEmailBCC($email): string
    {
        $emailBcc = '';
        if (isset($email->Content->Headers->Bcc)) {
            $emailBcc = $this->getDecodedEmailProperty($email, $email->Content->Headers->Bcc[0]);
        }

        return $emailBcc;
    }

    /**
     * Get Email Recipients.
     *
     * Returns the string containing all of the recipients, such as To, CC and if provided BCC
     *
     * @param mixed $email Email
     *
     * @return string Recipients
     */
    protected function getEmailRecipients($email): string
    {
        $recipients = [];
        if (isset($email->Content->Headers->To)) {
            $recipients[] = $this->getEmailTo($email);
        }
        if (isset($email->Content->Headers->Cc)) {
            $recipients[] = $this->getEmailCC($email);
        }
        if (isset($email->Content->Headers->Bcc)) {
            $recipients[] = $this->getEmailBCC($email);
        }

        $recipients = implode(' ', $recipients);

        return $recipients;
    }

    /**
     * Get Email Sender.
     *
     * Returns the string containing the sender of the email
     *
     * @param mixed $email Email
     *
     * @return string Sender
     */
    protected function getEmailSender($email): string
    {
        return $this->getDecodedEmailProperty($email, $email->Content->Headers->From[0]);
    }

    /**
     * Get Email Reply To.
     *
     * Returns the string containing the address to reply to
     *
     * @param mixed $email Email
     *
     * @return string ReplyTo
     */
    protected function getEmailReplyTo($email): string
    {
        return $this->getDecodedEmailProperty($email, $email->Content->Headers->{'Reply-To'}[0]);
    }

    /**
     * Get Email Priority.
     *
     * Returns the priority of the email
     *
     * @param mixed $email Email
     *
     * @return string Priority
     */
    protected function getEmailPriority($email): string
    {
        return $this->getDecodedEmailProperty($email, $email->Content->Headers->{'X-Priority'}[0]);
    }

    /**
     * Returns the decoded email property.
     *
     * @param $email
     * @param $property
     *
     * @return string
     */
    protected function getDecodedEmailProperty($email, $property): string
    {
        if ($property === '') {
            return $property;
        }

        if (stripos($property, '=?utf-8?Q?') !== false) {
            if (extension_loaded('iconv')) {
                return iconv_mime_decode($property);
            }
            if (extension_loaded('mbstring')) {
                return mb_decode_mimeheader($property);
            }
        }

        if (!empty($email->Content->Headers->{'Content-Transfer-Encoding'}) && in_array(
            'quoted-printable',
            $email->Content->Headers->{'Content-Transfer-Encoding'},
            true
        )
        ) {
            return quoted_printable_decode($property);
        }

        if (!empty($email->Content->Headers->{'Content-Type'}[0]) &&
            strpos($email->Content->Headers->{'Content-Type'}[0], 'multipart/mixed') !== false
        ) {
            return quoted_printable_decode($property);
        }

        return $property;
    }

    /**
     * Set Current Inbox.
     *
     * Sets the current inbox to work on, also create a copy of it to handle unread emails
     *
     * @param array $inbox Inbox
     */
    protected function setCurrentInbox($inbox): void
    {
        $this->currentInbox = $inbox;
        $this->unreadInbox = $inbox;
    }

    /**
     * Get Current Inbox.
     *
     * Returns the complete current inbox
     *
     * @return array Current Inbox
     */
    protected function getCurrentInbox(): array
    {
        return $this->currentInbox;
    }

    /**
     * Get Unread Inbox.
     *
     * Returns the inbox containing unread emails
     *
     * @return array Unread Inbox
     */
    protected function getUnreadInbox(): array
    {
        return $this->unreadInbox;
    }

    /**
     * Sort Emails.
     *
     * Sorts the inbox based on the timestamp
     *
     * @param array $inbox Inbox to sort
     */
    protected function sortEmails($inbox): void
    {
        usort($inbox, [$this, 'sortEmailsByCreationDatePredicate']);
    }

    /**
     * Get Email To.
     *
     * Returns the string containing the persons included in the To field
     *
     * @param mixed $emailA Email
     * @param mixed $emailB Email
     *
     * @return int Which email should go first
     */
    protected static function sortEmailsByCreationDatePredicate($emailA, $emailB): int
    {
        $sortKeyA = $emailA->Content->Headers->Date;
        $sortKeyB = $emailB->Content->Headers->Date;

        return ($sortKeyA > $sortKeyB) ? -1 : 1;
    }
}
