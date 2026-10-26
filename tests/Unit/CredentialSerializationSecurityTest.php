<?php

namespace Tests\Unit;

use Liberu\Foundation\Identity\Socialstream\Models\ConnectedAccount;
use Tests\TestCase;

class CredentialSerializationSecurityTest extends TestCase
{
    public function test_serialization_hides_credentials_without_disabling_server_side_access(): void
    {
        $cases = [
            [ConnectedAccount::class, ['token', 'secret', 'refresh_token']],
        ];

        foreach ($cases as [$class, $fields]) {
            $model = new $class();
            $model->forceFill(['name' => 'Example connection']);
            foreach ($fields as $field) {
                $model->setAttribute($field, 'private-'.$field);
            }

            $serialized = $model->toArray();
            $this->assertSame('Example connection', $serialized['name']);
            foreach ($fields as $field) {
                $this->assertArrayNotHasKey($field, $serialized);
                $this->assertStringNotContainsString('private-'.$field, $model->toJson());
                $this->assertSame('private-'.$field, $model->getAttribute($field));
            }
        }
    }
}
