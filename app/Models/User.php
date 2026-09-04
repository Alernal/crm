<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Concerns\ParticipatesInCommunications;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements HasCommunicationOwner
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, ParticipatesInCommunications;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'nit',
        'identification_type',
        'identification_number',
        'phone',
        'city',
        'address',
        'professional_card_number',
        'logo_path',
        'bank_name',
        'account_type',
        'account_number',
        'account_holder_name',
        'account_holder_id',
        'payment_link',
        'is_admin',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

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

    public function clients(): HasMany
    {
        return $this->hasMany(Client::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    public function serviceCategories(): HasMany
    {
        return $this->hasMany(ServiceCategory::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function taxEvents(): HasMany
    {
        return $this->hasMany(TaxEvent::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function budgets(): HasMany
    {
        return $this->hasMany(Budget::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function payrollPeriods(): HasMany
    {
        return $this->hasMany(PayrollPeriod::class);
    }

    public function primaSettlements(): HasMany
    {
        return $this->hasMany(PrimaSettlement::class);
    }

    public function cesantiaSettlements(): HasMany
    {
        return $this->hasMany(CesantiaSettlement::class);
    }

    public function contractSettlements(): HasMany
    {
        return $this->hasMany(ContractSettlement::class);
    }

    public function documentTemplates(): HasMany
    {
        return $this->hasMany(DocumentTemplate::class);
    }

    public function generatedDocuments(): HasMany
    {
        return $this->hasMany(GeneratedDocument::class);
    }

    public function documentTypeCounters(): HasMany
    {
        return $this->hasMany(DocumentTypeCounter::class);
    }

    public function teamMembers(): HasMany
    {
        return $this->hasMany(TeamMember::class, 'owner_id');
    }

    public function channels(): HasMany
    {
        return $this->hasMany(Channel::class, 'owner_id');
    }

    public function communicationOwnerId(): int
    {
        return $this->id;
    }
}
