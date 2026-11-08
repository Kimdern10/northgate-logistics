<?php

use App\Models\User;
use App\Notifications\VerifyEmailCode;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mime\Email;

function lastSentEmail(): Email
{
    /** @var Email $email */
    $email = Mail::mailer()->getSymfonyTransport()->messages()->last()->getOriginalMessage();

    return $email;
}

test('emails show the logo without adding an attachment', function () {
    $user = User::factory()->create(['name' => 'Dana Brooks']);

    $user->notify(new VerifyEmailCode('482913', 15));

    $email = lastSentEmail();

    expect($email->getAttachments())->toBeEmpty()
        ->and($email->getHtmlBody())->toContain('logo-mark-m')
        ->toContain('Northgate Logistics')
        ->toContain('482 913')
        ->toContain('Hi Dana Brooks');
});

test('once the site is online the email loads the logo picture from it', function () {
    config(['app.url' => 'https://northgatelogistics.com']);
    $user = User::factory()->create();

    $user->notify(new VerifyEmailCode('482913', 15));

    expect(lastSentEmail()->getHtmlBody())->toContain('src="https://northgatelogistics.com/images/mail-logo.png"');
});

test('the password reset email uses the branded layout', function () {
    $user = User::factory()->create(['name' => 'Dana Brooks']);

    $user->notify(new ResetPassword('reset-token'));

    $email = lastSentEmail();

    expect($email->getSubject())->toBe('Reset your Northgate Logistics password')
        ->and($email->getHtmlBody())->toContain('Reset my password')
        ->toContain(route('password.reset', ['token' => 'reset-token', 'email' => $user->email]))
        ->not->toContain('Laravel');
});
