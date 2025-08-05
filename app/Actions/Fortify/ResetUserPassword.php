<?php

namespace App\Actions\Fortify;

use Illuminate\Validation\ValidationException;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\ResetsUserPasswords;
use Exception;

class ResetUserPassword implements ResetsUserPasswords
{
    use PasswordValidationRules;

    /**
     * Validate and reset the user's forgotten password.
     *
     * @param  array<string, string>  $input
     * @throws ValidationException
     * @throws Exception
     */
    public function reset(User $user, array $input): void
    {
        try {
            Validator::make($input, [
                'password' => $this->passwordRules(),
            ])->validate();

            $user->forceFill([
                'password' => Hash::make($input['password']),
            ])->save();

            Log::info('User password reset successfully', ['user_id' => $user->id]);
        } catch (ValidationException $e) {
            Log::error('Password reset validation failed', [
                'user_id' => $user->id,
                'errors' => $e->errors(),
            ]);
            throw $e;
        } catch (Exception $e) {
            Log::error('Password reset failed', [
                'user_id' => $user->id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw new Exception('Failed to reset password. Please try again later.');
        }
    }
}