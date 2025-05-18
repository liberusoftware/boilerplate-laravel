<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Jetstream\Events\TeamCreated;
use Laravel\Jetstream\Events\TeamDeleted;
use Laravel\Jetstream\Events\TeamUpdated;
use Laravel\Jetstream\Team as JetstreamTeam;

class Team extends JetstreamTeam
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'personal_team',
    ];

    /**
     * The event map for the model.
     *
     * @var array<string, class-string>
     */
    protected $dispatchesEvents = [
        'created' => TeamCreated::class,
        'updated' => TeamUpdated::class,
        'deleted' => TeamDeleted::class,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'personal_team' => 'boolean',
        ];
    }

    public function addrs(): HasMany
    {
        return $this->hasMany(Addr::class);
    }

    public function authors(): HasMany
    {
        return $this->hasMany(Author::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function chans(): HasMany
    {
        return $this->hasMany(Chan::class);
    }

    public function citations(): HasMany
    {
        return $this->hasMany(Citation::class);
    }

    public function companies(): HasMany
    {
        return $this->hasMany(Company::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    public function dnas(): HasMany
    {
        return $this->hasMany(Dna::class);
    }

    public function dna_matchings(): HasMany
    {
        return $this->hasMany(DnaMatching::class);
    }

    public function families(): HasMany
    {
        return $this->hasMany(Family::class);
    }

    public function family_events(): HasMany
    {
        return $this->hasMany(FamilyEvent::class);
    }

    public function family_slgs(): HasMany
    {
        return $this->hasMany(FamilySlgs::class);
    }

    public function gedcoms(): HasMany
    {
        return $this->hasMany(Gedcom::class);
    }

    public function geneanums(): HasMany
    {
        return $this->hasMany(Geneanum::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(Note::class);
    }

    public function persons(): HasMany
    {
        return $this->hasMany(Person::class);
    }

    public function people(): HasMany
    {
        return $this->hasMany(Person::class);
    }

    public function person_alias(): HasMany
    {
        return $this->hasMany(PersonAlia::class);
    }

    public function person_ancis(): HasMany
    {
        return $this->hasMany(PersonAnci::class);
    }

    public function person_assos(): HasMany
    {
        return $this->hasMany(PersonAsso::class);
    }

    public function person_events(): HasMany
    {
        return $this->hasMany(PersonEvent::class);
    }

    public function person_lds(): HasMany
    {
        return $this->hasMany(PersonLds::class);
    }

    public function person_names(): HasMany
    {
        return $this->hasMany(PersonName::class);
    }

    public function person_name_fones(): HasMany
    {
        return $this->hasMany(PersonNameFone::class);
    }

    public function person_subms(): HasMany
    {
        return $this->hasMany(PersonSubm::class);
    }

    public function places(): HasMany
    {
        return $this->hasMany(Place::class);
    }

    public function publications(): HasMany
    {
        return $this->hasMany(Publication::class);
    }

    public function refns(): HasMany
    {
        return $this->hasMany(Refn::class);
    }

    public function repositories(): HasMany
    {
        return $this->hasMany(Repository::class);
    }

    public function sources(): HasMany
    {
        return $this->hasMany(Source::class);
    }

    public function source_data(): HasMany
    {
        return $this->hasMany(SourceData::class);
    }

    public function subms(): HasMany
    {
        return $this->hasMany(Subm::class);
    }

    public function subns(): HasMany
    {
        return $this->hasMany(Subn::class);
    }

    public function trees(): HasMany
    {
        return $this->hasMany(Tree::class);
    }
}
