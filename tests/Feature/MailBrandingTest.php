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

test('emails carry the logo inside the message', function () {
    $user = User::factory()->create(['name' => 'Dana Brooks']);

    $user->notify(new VerifyEmailCode('482913', 15));

    $email = lastSentEmail();
    $inline = array_filter($email->getAttachments(), fn ($part) => $part->getDisposition() === 'inline');

    expect($inline)->toHaveCount(1)
        ->and(array_values($inline)[0]->getContentType())->toBe('image/png')
        ->and($email->getHtmlBody())->toContain('Northgate Logistics')
        ->toContain('482 913')
        ->toContain('Hi Dana Brooks');
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
