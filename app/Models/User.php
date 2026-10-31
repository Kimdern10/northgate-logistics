<?php

namespace App\Models;

use App\Support\EmailVerificationCode;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property bool $is_admin
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail, PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }

    /**
     * Shipments booked while logged in.
     *
     * @return HasMany<Shipment, $this>
     */
    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }

    /**
     * @return HasMany<Address, $this>
     */
    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class)->orderByDesc('is_default')->orderBy('label')->orderBy('name');
    }

    /**
     * Shipments added to the account by tracking number.
     *
     * @return BelongsToMany<Shipment, $this>
     */
    public function watchedShipments(): BelongsToMany
    {
        return $this->belongsToMany(Shipment::class, 'shipment_watches')->withTimestamps();
    }

    /**
     * Every shipment this customer may see in their account: ones they booked, ones sent from or
     * to their email address (only once that address is verified), and ones they are watching.
     *
     * @param  'all'|'sending'|'receiving'|'watching'  $view
     * @return Builder<Shipment>
     */
    public function visibleShipments(string $view = 'all'): Builder
    {
        $verifiedEmail = $this->hasVerifiedEmail() ? strtolower($this->email) : null;

        return Shipment::query()->where(function (Builder $query) use ($view, $verifiedEmail): void {
            if (in_array($view, ['all', 'sending'], true)) {
                $query->orWhere('user_id', $this->id);

                if ($verifiedEmail) {
                    $query->orWhere('sender_email', $verifiedEmail);
                }
            }

            if (in_array($view, ['all', 'receiving'], true) && $verifiedEmail) {
                $query->orWhere('receiver_email', $verifiedEmail);
            }

            if (in_array($view, ['all', 'watching'], true)) {
                $query->orWhereIn('id', $this->watchedShipments()->select('shipments.id'));
            }
        });
    }

    public function canSeeShipment(Shipment $shipment): bool
    {
        return $this->visibleShipments()->whereKey($shipment->id)->exists();
    }

    /**
     * The sender's own account: may download the waybill with full addresses.
     */
    public function sentShipment(Shipment $shipment): bool
    {
        return $shipment->user_id === $this->id
            || ($this->hasVerifiedEmail() && $shipment->sender_email !== null && strtolower($shipment->sender_email) === strtolower($this->email));
    }

    /**
     * Email a six-digit code instead of Laravel's default verification link.
     */
    public function sendEmailVerificationNotification(): void
    {
        EmailVerificationCode::send($this);
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        $initials = Str::initials($this->name, true);

        return Str::length($initials) > 1
            ? Str::substr($initials, 0, 1).Str::substr($initials, -1)
            : $initials;
    }
}
