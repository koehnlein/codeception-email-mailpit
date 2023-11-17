# Mailpit Module for the Codeception Testing Framework

This codeception module can be used to run tests against your [Mailpit](https://github.com/axllent/mailpit) instance.

Mailpit was inspired by MailHog, which is not developed anymore. Because it is not a 1:1 replacement, the API changed
and existing MailHog codeception modules cannot be used anymore.

This codeception module is based on [oqq/codeception-email-mailhog](https://github.com/oqq/codeception-email-mailhog)
(wich is a fork of [ericmartel/codeception-email-mailhog](https://github.com/ericmartel/codeception-email-mailhog)) and
brings nearly the same functionality for Mailpit as the mentioned modules did for MailHog.

### Installation
Through composer, require the package:
```
composer req koehnlein/codeception-email-mailpit --dev
```
Then turn it on in your Codeception suite yaml file
```
class_name: FunctionalTester
modules:
    enabled:
        - Mailpit
    config:
        Mailpit:
            url: 'http://mailpit.dev'
            port: '8025'
```
Additional parameters can be fed directly to the Guzzle connection using the `guzzleRequestOptions` variable.

The variable `deleteEmailsAfterScenario` can be set to true to ensure that all emails are deleted at the end of each scenario, but it is turned off by default.
### Added Methods
This Module adds a few public methods for the user, such as:
```
deleteAllEmails()
```
Deletes all emails in Mailpit
```
fetchEmails()
```
Fetches all email headers from Mialpit, sorts them by timestamp and assigns them to the current and unread inboxes
```
accessInboxFor($address)
```
Filters emails to only keep those that are received by the provided address
```
openNextUnreadEmail()
```
Pops the most recent unread email and assigns it as the email to conduct tests on
```
openNextAttachmentInOpenedEmail()
```
Pops the next attachment and assigns it as the attachment to conduct tests on

### Example Test
Here is a simple scenario where we test the content of an email.  For a detailed list of all available test methods, please refer to the [Codeception Email Testing Framework][CodeceptionEmailTestingFramework].
```
<?php
$I = new FunctionalTester($scenario);
$I->am('a member');
$I->wantTo('request a reset password link');

// First, remove all existing emails in the Mailpit inbox
$I->deleteAllEmails();

// Implementation is up to the user, use this as an example
$I->requestAPasswordResetLink();

// Query Mailpit and fetch all available emails
$I->fetchEmails();

// This is optional, but will filter the emails in case you're sending multiple emails or use the BCC field
$I->accessInboxFor('testuser@example.com');

// A new email should be available and it should be unread
$I->haveEmails();
$I->haveUnreadEmails();

// Set the next unread email as the email to perform operations on
$I->openNextUnreadEmail();

// After opening the only available email, the unread inbox should be empty
$I->dontHaveUnreadEmails();

// Validate the content of the opened email, all of these operations are performed on the same email
$I->seeInOpenedEmailSubject('Your Password Reset Link');
$I->seeInOpenedEmailTextBody('Follow this link to reset your password');
$I->seeInOpenedEmailHtmlBody('<a href="https://www.example.org/">Follow this link to reset your password</a>');
$I->seeInOpenedEmailRecipients('testuser@example.com');

// Validate if email has attachments
$I->haveAttachmentsInOpenedEmail();

// Open next attachment
$I->openNextAttachmentInOpenedEmail();

// Validate metadata of the attachment
$I->seeInFilenameOfOpenedAttachment();
$I->grabFilenameFromOpenedAttachment();
$I->grabContentTypeFromOpenedAttachment();
$I->grabSizeFromOpenedAttachment();
```

