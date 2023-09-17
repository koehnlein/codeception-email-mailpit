<?php

/*
 * This file is part of the Email test framework for Codeception.
 * (c) 2015-2016 Eric Martel
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Codeception\Module;

// todo:
// text & html content
// attachments

trait TestsEmails
{
    /**
     * Have Emails
     *
     * Checks if there are any emails in the inbox
     */
    public function haveEmails()
    {
        $currentInbox = $this->getCurrentInbox();
        $this->assertGreaterThan(0, count($currentInbox));
    }

    /**
     * Have Number Of Emails
     *
     * Checks that the amount of emails in the inbox is exactly $expected
     * @params int $expected Number of expected emails
     */
    public function haveNumberOfEmails($expected)
    {
        $currentInbox = $this->getCurrentInbox();
        $this->assertEquals($expected, count($currentInbox));
    }

    /**
     * Dont Have Emails
     *
     * Checks that there are no emails in the inbox
     */
    public function dontHaveEmails()
    {
        $currentInbox = $this->getCurrentInbox();
        $this->assertEquals(0, count($currentInbox));
    }

    /**
     * Have Unread Emails
     *
     * Checks that there is at least one unread email
     **/
    public function haveUnreadEmails()
    {
        $unreadInbox = $this->getUnreadInbox();
        $this->assertGreaterThan(0, count($unreadInbox));
    }

    /**
     * Have Number Of Unread Emails
     *
     * Checks that the amount of emails in the unread inbox is exactly $expected
     * @params int $expected Number of expected emails
     */
    public function haveNumberOfUnreadEmails($expected)
    {
        $unreadInbox = $this->getUnreadInbox();
        $this->assertEquals($expected, count($unreadInbox));
    }

    /**
     * Dont Have Unread Emails
     *
     * Checks that there are no unread emails in the inbox
     */
    public function dontHaveUnreadEmails()
    {
        $unreadInbox = $this->getUnreadInbox();
        $this->assertEquals(0, count($unreadInbox));
    }

    /**
     * See In Opened Email Plain Text Body
     *
     * Validates that $expected can be found in the opened email text body
     *
     * @param string $expected Text
     */
    public function seeInOpenedEmailTextBody($expected)
    {
        $email = $this->getOpenedEmail();
        $this->seeInEmailTextBody($email, $expected);
    }

    /**
     * See In Opened Email HTML Body
     *
     * Validates that $expected can be found in the opened email html body
     *
     * @param string $expected Text
     */
    public function seeInOpenedEmailHtmlBody($expected)
    {
        $email = $this->getOpenedEmail();
        $this->seeInEmailHtmlBody($email, $expected);
    }

    /**
     * See In Opened Email Subject
     *
     * Validates that $expected can be found in the opened email subject
     *
     * @param string $expected Text
     */
    public function seeInOpenedEmailSubject($expected)
    {
        $email = $this->getOpenedEmail();
        $this->seeInEmailSubject($email, $expected);
    }

    /**
     * Don't See In Opened Email Plain Text Body
     *
     * Checks that $expected cannot be found in the opened email text body
     *
     * @param string $expected Text
     */
    public function dontSeeInOpenedEmailTextBody($expected)
    {
        $email = $this->getOpenedEmail();
        $this->dontSeeInEmailTextBody($email, $expected);
    }

    /**
     * Don't See In Opened Email HTML Body
     *
     * Checks that $expected cannot be found in the opened email html body
     *
     * @param string $expected Text
     */
    public function dontSeeInOpenedEmailHtmlBody($expected)
    {
        $email = $this->getOpenedEmail();
        $this->dontSeeInEmailHtmlBody($email, $expected);
    }

    /**
     * Dont See In Opened Email Subject
     *
     * Checks that $expected cannot be found in the opened email subject
     *
     * @param string $expected Text
     */
    public function dontSeeInOpenedEmailSubject($expected)
    {
        $email = $this->getOpenedEmail();
        $this->dontSeeInEmailSubject($email, $expected);
    }

    /**
     * See In Email Plain Text Body
     *
     * Checks that the body of $email contains $expected
     *
     * @param mixed $email a JSON encoded email
     * @param string $expected Text
     */
    public function seeInEmailTextBody($email, $expected)
    {
        $this->assertStringContainsString($expected, $this->getEmailTextBody($email), "Email Plain Text Body Contains");
    }

    /**
     * See In Email HML Body
     *
     * Checks that the body of $email contains $expected
     *
     * @param mixed $email a JSON encoded email
     * @param string $expected Text
     */
    public function seeInEmailHtmlBody($email, $expected)
    {
        $this->assertStringContainsString($expected, $this->getEmailHtmlBody($email), "Email HTML Body Contains");
    }

    /**
     * Don't See In Email Plain Text Body
     *
     * Checks that the body of $email does not contain $expected
     *
     * @param mixed $email a JSON encoded email
     * @param string $expected Text
     */
    public function dontSeeInEmailTextBody($email, $expected)
    {
        $this->assertStringNotContainsString($expected, $this->getEmailTextBody($email), "Email Plain Text Body Doesn't Contain");
    }

    /**
     * Don't See In Email HTML Body
     *
     * Checks that the body of $email does not contain $expected
     *
     * @param mixed $email a JSON encoded email
     * @param string $expected Text
     */
    public function dontSeeInEmailHtmlBody($email, $expected)
    {
        $this->assertStringNotContainsString($expected, $this->getEmailHtmlBody($email), "Email HTML Body Doesn't Contain");
    }

    /**
     * See In Email Subject
     *
     * Checks that the subject of $email contains $expected
     *
     * @param mixed $email a JSON encoded email
     * @param string $expected Text
     */
    public function seeInEmailSubject($email, $expected)
    {
        $this->assertStringContainsString($expected, $this->getEmailSubject($email), "Email Subject Contains");
    }

    /**
     * Dont See In Email Subject
     *
     * Checks that the subject of $email does not contain $expected
     *
     * @param mixed $email a JSON encoded email
     * @param string $expected Text
     */
    public function dontSeeInEmailSubject($email, $expected)
    {
        $this->assertStringNotContainsString($expected, $this->getEmailSubject($email), "Email Subject Doesn't Contain");
    }

    /**
     * See In Opened Email Sender
     *
     * Checks if the sender of the opened email contains $expected
     *
     * @param string $expected Text
     */
    public function seeInOpenedEmailSender($expected)
    {
        $email = $this->getOpenedEmail();
        $this->seeInEmailSender($email, $expected);
    }

    /**
     * Dont See In Opened Email Sender
     *
     * Checks if the sender of the opened email does not contain $expected
     *
     * @param string $expected Text
     */
    public function dontSeeInOpenedEmailSender($expected)
    {
        $email = $this->getOpenedEmail();
        $this->dontSeeInEmailSender($email, $expected);
    }

    /**
     * See In Email Sender
     *
     * Checks if the sender of $email contains $expected
     *
     * @param mixed $email a JSON encoded email
     * @param string $expected Text
     */
    public function seeInEmailSender($email, $expected)
    {
        $this->assertStringContainsString($expected, $this->getEmailSender($email));
    }

    /**
     * Dont See In Email Sender
     *
     * Checks if the sender of $email does not contain $expected
     *
     * @param mixed $email a JSON encoded email
     * @param string $expected Text
     */
    public function dontSeeInEmailSender($email, $expected)
    {
        $this->assertStringNotContainsString($expected, $this->getEmailSender($email));
    }

    /**
     * See In Opened Email Reply To
     *
     * Checks if the ReplyTo of the opened email contains $expected
     *
     * @param string $expected Text
     */
    public function seeInOpenedEmailReplyTo($expected)
    {
        $email = $this->getOpenedEmail();
        $this->seeInEmailReplyTo($email, $expected);
    }

    /**
     * Dont See In Opened Email Reply To
     *
     * Checks if the ReplyTo of the opened email does not contain $expected
     *
     * @param string $expected Text
     */
    public function dontSeeInOpenedEmailReplyTo($expected)
    {
        $email = $this->getOpenedEmail();
        $this->dontSeeInEmailReplyTo($email, $expected);
    }

    /**
     * See In Email Reply To
     *
     * Checks if the ReplyTo of $email contains $expected
     *
     * @param mixed $email a JSON encoded email
     * @param string $expected Text
     */
    public function seeInEmailReplyTo($email, $expected)
    {
        $this->assertStringContainsString($expected, $this->getEmailReplyTo($email));
    }

    /**
     * Dont See In Email Reply To
     *
     * Checks if the ReplyTo of $email does not contain $expected
     *
     * @param mixed $email a JSON encoded email
     * @param string $expected Text
     */
    public function dontSeeInEmailReplyTo($email, $expected)
    {
        $this->assertStringNotContainsString($expected, $this->getEmailReplyTo($email));
    }

    /**
     * See In Opened Email Recipients
     *
     * Checks that the recipients of the opened email contain $expected
     *
     * @param string $expected Text
     */
    public function seeInOpenedEmailRecipients($expected)
    {
        $email = $this->getOpenedEmail();
        $this->seeInEmailRecipients($email, $expected);
    }

    /**
     * Dont See In Opened Email Recipients
     *
     * Checks that the recipients of the opened email do not contain $expected
     *
     * @param string $expected Text
     */
    public function dontSeeInOpenedEmailRecipients($expected)
    {
        $email = $this->getOpenedEmail();
        $this->dontSeeInEmailRecipients($email, $expected);
    }

    /**
     * See In Email Recipients
     *
     * Checks that the recipients of $email contain $expected
     *
     * @param mixed $email a JSON encoded email
     * @param string $expected Text
     */
    public function seeInEmailRecipients($email, $expected)
    {
        $this->assertStringContainsString($expected, $this->getEmailRecipients($email));
    }

    /**
     * Dont See In Email Recipients
     *
     * Checks that the recipients of $email do not contain $expected
     *
     * @param mixed $email a JSON encoded email
     * @param string $expected Text
     */
    public function dontSeeInEmailRecipients($email, $expected)
    {
        $this->assertStringNotContainsString($expected, $this->getEmailRecipients($email));
    }

    /**
     * See In Opened Email To Field
     *
     * Checks that the To field of the opened email contains $expected
     *
     * @param string $expected Text
     */
    public function seeInOpenedEmailToField($expected)
    {
        $email = $this->getOpenedEmail();
        $this->seeInEmailToField($email, $expected);
    }

    /**
     * Dont See In Opened Email To Field
     *
     * Checks that the To field of the opened email does not contain $expected
     *
     * @param string $expected Text
     */
    public function dontSeeInOpenedEmailToField($expected)
    {
        $email = $this->getOpenedEmail();
        $this->dontSeeInEmailToField($email, $expected);
    }

    /**
     * See In Email To Field
     *
     * Checks that the To field of $email contains $expected
     *
     * @param mixed $email a JSON encoded email
     * @param string $expected Text
     */
    public function seeInEmailToField($email, $expected)
    {
        $this->assertStringContainsString($expected, $this->getEmailTo($email));
    }

    /**
     * Dont See In Email To Field
     *
     * Checks that the To field of $email does not contain $expected
     *
     * @param mixed $email a JSON encoded email
     * @param string $expected Text
     */
    public function dontSeeInEmailToField($email, $expected)
    {
        $this->assertStringNotContainsString($expected, $this->getEmailTo($email));
    }

    /**
     * See In Opened Email CC Field
     *
     * Checks that the CC field of the opened email contains $expected
     *
     * @param string $expected Text
     */
    public function seeInOpenedEmailCCField($expected)
    {
        $email = $this->getOpenedEmail();
        $this->seeInEmailCCField($email, $expected);
    }

    /**
     * Dont See In Opened Email CC Field
     *
     * Checks that the CC field of the opened email does not contain $expected
     *
     * @param string $expected Text
     */
    public function dontSeeInOpenedEmailCCField($expected)
    {
        $email = $this->getOpenedEmail();
        $this->dontSeeInEmailCCField($email, $expected);
    }

    /**
     * See In Email CC Field
     *
     * Checks that the CC field of $email contains $expected
     *
     * @param mixed $email a JSON encoded email
     * @param string $expected Text
     */
    public function seeInEmailCCField($email, $expected)
    {
        $this->assertStringContainsString($expected, $this->getEmailCC($email));
    }

    /**
     * Dont See In Email CC Field
     *
     * Checks that the CC field of $email does not contain $expected
     *
     * @param mixed $email a JSON encoded email
     * @param string $expected Text
     */
    public function dontSeeInEmailCCField($email, $expected)
    {
        $this->assertStringNotContainsString($expected, $this->getEmailCC($email));
    }

    /**
     * See In Opened Email BCC Field
     *
     * Checks that the BCC field of the opened email contains $expected
     *
     * Warning: it is possible for an email to have its BCC field empty, it doesn't mean that another instance of the same email doesn't exist.
     *
     * @param string $expected Text
     */
    public function seeInOpenedEmailBCCField($expected)
    {
        $email = $this->getOpenedEmail();
        $this->seeInEmailBCCField($email, $expected);
    }

    /**
     * Dont See In Opened Email BCC Field
     *
     * Checks that the BCC field of the opened email does not contain $expected
     *
     * Warning: it is possible for an email to have its BCC field empty, it doesn't mean that another instance of the same email doesn't exist.
     *
     * @param string $expected Text
     */
    public function dontSeeInOpenedEmailBCCField($expected)
    {
        $email = $this->getOpenedEmail();
        $this->dontSeeInEmailBCCField($email, $expected);
    }

    /**
     * See In Email BCC Field
     *
     * Checks that the BCC field of $email contains $expected
     *
     * Warning: it is possible for an email to have its BCC field empty, it doesn't mean that another instance of the same email doesn't exist.
     *
     * @param mixed $email a JSON encoded email
     * @param string $expected Text
     */
    public function seeInEmailBCCField($email, $expected)
    {
        $this->assertStringContainsString($expected, $this->getEmailBCC($email));
    }

    /**
     * Dont See In Email BCC Field
     *
     * Checks that the BCC field of $email does not contain $expected
     *
     * Warning: it is possible for an email to have its BCC field empty, it doesn't mean that another instance of the same email doesn't exist.
     *
     * @param mixed $email a JSON encoded email
     * @param string $expected Text
     */
    public function dontSeeInEmailBCCField($email, $expected)
    {
        $this->assertStringNotContainsString($expected, $this->getEmailBCC($email));
    }

    /**
     * Return plain text body from currently opened email
     */
    public function grabTextBodyFromEmail(): string
    {
        $email = $this->getOpenedEmail();
        return $email->Text ?? '';
    }

    /**
     * Return HTML body from currently opened email
     */
    public function grabHtmlBodyFromEmail(): string
    {
        $email = $this->getOpenedEmail();
        return $email->HTML ?? '';
    }

    /**
     * Return specific header from currently opened email
     */
    public function grabHeaderFromOpenedEmail(string $header): array
    {
        $email = $this->getOpenedEmail();
        return $this->getHeader($email, $header);
    }
}

;
