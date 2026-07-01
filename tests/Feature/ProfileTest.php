<?php

use App\Models\Profile;
use App\Enums\ProfileStatus;

it('marca un perfil como libre por defecto', function () {
    $profile = Profile::factory()->create();
    expect($profile->isFree())->toBeTrue()
        ->and($profile->status)->toBe(ProfileStatus::Free);
});

it('el scope free solo devuelve perfiles libres', function () {
    Profile::factory()->create(['status' => ProfileStatus::Free->value]);
    Profile::factory()->create(['status' => ProfileStatus::Assigned->value]);

    expect(Profile::free()->count())->toBe(1);
});
