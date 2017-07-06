# Email fetcher
Checks inbox for unread emails, sends them to webhook and marks read.

# Example usage
```php
require __DIR__ . '/vendor/autoload.php';

$fetcher = new Fetcher('http://helpdesk.f1lab.ru/email/post', [
    'host' => 'some.imap.server',
    'port' => '993',
    'user' => 'some@imap.user',
    'password' => 'some password',
    'ssl' => true,
], 10); // 10 is limit to process at once

$fetcher->fetch();
```
