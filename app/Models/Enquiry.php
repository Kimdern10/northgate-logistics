<?php

namespace App\Models;

use App\Enums\EnquiryStatus;
use Database\Factories\EnquiryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * A quote request or message sent from the website.
 *
 * @property int $id
 * @property string $type
 * @property string $name
 * @property string|null $company
 * @property string $email
 * @property string|null $phone
 * @property string $message
 * @property array<string, string>|null $details
 * @property EnquiryStatus $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['type', 'name', 'company', 'email', 'phone', 'message', 'details', 'status'])]
class Enquiry extends Model
{
    /** @use HasFactory<EnquiryFactory> */
    use HasFactory;

    /**
     * What the visitor is asking about.
     *
     * @var array<string, string>
     */
    public const array TYPES = [
        'quote' => 'Shipping quote',
        'warehousing' => 'Warehousing',
        'fulfillment' => 'E-commerce fulfillment',
        'business' => 'Business account',
        'general' => 'General question',
    ];

    protected $attributes = [
        'status' => 'new',
    ];

    protected function casts(): array
    {
        return [
            'details' => 'array',
            'status' => EnquiryStatus::class,
        ];
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? ucfirst($this->type);
    }
}
