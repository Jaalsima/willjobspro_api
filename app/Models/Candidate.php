<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Candidate extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'subscription_id',
        'full_name',
        'gender',
        'date_of_birth',
        'address',
        'phone_number',
        'work_experience',
        'education',
        'certifications',
        'languages',
        'references',
        'expected_salary',
        'cv_path',
        'photo_path',
        'banner_path',
        'candidate_social_networks',
        'status',
    ];

    protected $casts = [
        'candidate_social_networks' => 'json',
    ];

    // Relación con el usuario
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function skills()
    {
        return $this->belongsToMany(Skill::class);
    }

    public function subscription()
    {
        return $this->hasOne(Subscription::class);
    }

    // Relación con aplicaciones o postulaciones
    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }
}
