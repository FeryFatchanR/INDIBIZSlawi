<?php

namespace Database\Factories;

use App\Enums\LeadSource;
use App\Enums\LeadType;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Lead> */
class LeadFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_name' => 'PT '.fake()->unique()->company(),
            'contact_name' => fake()->name(),
            'phone' => '0812'.fake()->numerify('########'),
            'email' => fake()->safeEmail(),
            'type' => LeadType::Contact,
            'source' => LeadSource::WebsiteForm,
        ];
    }

    public function assignedTo(User $user): static
    {
        return $this->afterMaking(fn (Lead $lead) => $lead->assigned_to = $user->id);
    }
}
