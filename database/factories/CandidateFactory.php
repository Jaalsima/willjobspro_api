<?php

namespace Database\Factories;

use App\Models\Candidate;
use App\Models\EducationLevel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CandidateFactory extends Factory
{
    protected $model = Candidate::class;

    public function definition(): array
    {
        $user = User::whereHas('roles', function ($query) {
            $query->where('name', 'candidate');
        })->inRandomOrder()->first();

        if (!$user) {
            $user = User::factory()->create();
            $user->assignRole('candidate');
        }

        $educationLevels = EducationLevel::all();

        return [
            'user_id' => $user->id,
            'gender' => $this->faker->randomElement(['male', 'female']),
            'date_of_birth' => $this->faker->date(),
            'phone_number' => $this->faker->phoneNumber,
            'expected_salary' => $this->faker->randomFloat(2, 1000, 10000),
            'status' => $this->faker->randomElement(['Active', 'Blocked']),
            'cv_path' => 'candidate_uploads/cvs/' . $this->faker->file('public/storage/candidate_uploads/cvs', storage_path('app/public/candidate_uploads/cvs'), false),
            'photo_path' => 'candidate_uploads/profile_photos/' . $this->faker->image('public/storage/candidate_uploads/profile_photos', 100, 100, null, false),
            'banner_path' => 'candidate_uploads/banners/' . $this->faker->image('public/storage/candidate_uploads/banners', 800, 400, null, false),

        ];
    }
}

