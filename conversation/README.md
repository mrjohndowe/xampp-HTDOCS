# Text Message Simulator

A small PHP/JavaScript application for creating realistic scripted text-message conversations.

## Features

- Realistic phone-style interface
- Incoming and outgoing message bubbles
- Participant names
- Contact avatars
- User-selectable contact avatar
- Incoming typing indicator
- Scripted delays
- Automatic conversation scrolling
- Simulated typing into the message composer
- Configurable typing speed
- Configurable pauses while composing
- Configurable delay before sending
- Manual message input
- PHP message persistence
- Avatar uploads

## Requirements

- PHP 8.1+
- Fileinfo PHP extension
- Modern browser

## Run locally

From the project directory:

```bash
php -S localhost:8080 router.php
```
Then open:
http://localhost:8080

## Conversation scripting
Edit:
`app/data/conversation.php`

#### A basic incoming message:

```php
[
    'sender' => 'other',
    'text' => 'hey...',
    'delay' => 2500,
    'typing' => 1500,
],
```
#### An outgoing message typed into the composer before being sent:
```php
[
    'sender' => 'me',
    'text' => 'NVM on the flirting & roleplaying with me 😌',
    'delay' => 3000,
    'compose' => true,
    'typingSpeed' => 60,
    'sendDelay' => 1000,
],
```
#### Pausing while composing
Use `pauseAt`.

The key represents the number of characters already typed.

```php
[
    'sender' => 'me',
    'text' => 'Actually... never mind.',
    'delay' => 2500,
    'compose' => true,
    'typingSpeed' => 70,

    'pauseAt' => [
        11 => 2000,
    ],

    'sendDelay' => 900,
],
```
This types:

```
Actually...
```
waits two seconds, then finishes the message.
#### Avatars
Click the contact image at the top of the phone to upload another avatar.

Uploaded files are stored in: `public/uploads/avatars/`
#### Stored messages
Manually sent messages are written to:
`storage/conversations/conversation.json`
#### Security

The avatar endpoint:
- checks upload errors
- checks maximum size
- uses MIME detection
- restricts image formats
- generates random filenames

For public deployment, also add authentication, CSRF protection, rate limiting, and stricter storage permissions.

One correction to the earlier `phone.php`: because the API directory is **outside** `public`, requests such as `/api/send-message.php` rely on `router.php`. That works with:

```bash
php -S localhost:8080 router.php
```
