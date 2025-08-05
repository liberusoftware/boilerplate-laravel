<?php

namespace App\Actions\Fortify;

use Illuminate\Validation\ValidationException;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\UpdatesUserPasswords;
use Exception;

class UpdateUserPassword implements UpdatesUserPasswords
{
    use PasswordValidationRules;

    /**
     * Validate and update the user's password.
     *
     * @param  array<string, string>  $input
     * @throws ValidationException
     * @throws Exception
     */
    public function update(User $user, array $input): void
    {
        try {
            Validator::make($input, [
                'current_password' => ['required', 'string', 'current_password:web'],
                'password' => $this->passwordRules(),
            ], [
                'current_password.current_password' => __('The provided password does not match your current password.'),
            ])->validateWithBag('updatePassword');

            $user->forceFill([
                'password' => Hash::make($input['password']),
            ])->save();

            Log::info('User password updated successfully', ['user_id' => $user->id]);
        } catch (ValidationException $e) {
            Log::error('Password update validation failed', [
                'user_id' => $user->id,
                'errors' => $e->errors(),
            ]);
            throw $e;
        } catch (Exception $e) {
            Log::error('Password update failed', [
                'user_id' => $user->id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw new Exception('Failed to update password. Please try again later.');
        }
    }
}