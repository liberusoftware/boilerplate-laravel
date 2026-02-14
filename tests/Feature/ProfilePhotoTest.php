<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
});

it('can upload a profile photo', function () {
    $user = User::factory()->create();
    
    $photo = UploadedFile::fake()->image('avatar.jpg');
    
    $this->actingAs($user);
    
    $response = $this->post('/user/profile-information', [
        'name' => $user->name,
        'email' => $user->email,
        'photo' => $photo,
    ]);
    
    $user->refresh();
    
    expect($user->profile_photo_path)->not->toBeNull();
    Storage::disk('public')->assertExists($user->profile_photo_path);
});

it('can delete a profile photo', function () {
    $user = User::factory()->create();
    
    $photo = UploadedFile::fake()->image('avatar.jpg');
    
    $this->actingAs($user);
    
    $this->post('/user/profile-information', [
        'name' => $user->name,
        'email' => $user->email,
        'photo' => $photo,
    ]);
    
    $user->refresh();
    $photoPath = $user->profile_photo_path;
    
    expect($photoPath)->not->toBeNull();
    
    $this->delete('/user/profile-photo');
    
    $user->refresh();
    
    expect($user->profile_photo_path)->toBeNull();
    Storage::disk('public')->assertMissing($photoPath);
});

it('validates profile photo file type', function () {
    $user = User::factory()->create();
    
    $invalidFile = UploadedFile::fake()->create('document.pdf', 100);
    
    $this->actingAs($user);
    
    $response = $this->post('/user/profile-information', [
        'name' => $user->name,
        'email' => $user->email,
        'photo' => $invalidFile,
    ]);
    
    $response->assertSessionHasErrors(['photo']);
});

it('validates profile photo file size', function () {
    $user = User::factory()->create();
    
    $largeFile = UploadedFile::fake()->image('avatar.jpg')->size(2048); // 2MB (exceeds 1MB limit)
    
    $this->actingAs($user);
    
    $response = $this->post('/user/profile-information', [
        'name' => $user->name,
        'email' => $user->email,
        'photo' => $largeFile,
    ]);
    
    $response->assertSessionHasErrors(['photo']);
});

it('displays profile photo url correctly', function () {
    $user = User::factory()->create();
    
    $photo = UploadedFile::fake()->image('avatar.jpg');
    
    $this->actingAs($user);
    
    $this->post('/user/profile-information', [
        'name' => $user->name,
        'email' => $user->email,
        'photo' => $photo,
    ]);
    
    $user->refresh();
    
    expect($user->profile_photo_url)->toContain('storage');
    expect($user->profile_photo_url)->not->toBeNull();
});
