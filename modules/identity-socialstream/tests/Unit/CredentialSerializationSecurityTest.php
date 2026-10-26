<?php

namespace Liberu\Foundation\Identity\Socialstream\Tests\Security;

use Liberu\Foundation\Identity\Socialstream\Models\ConnectedAccount;
use PHPUnit\Framework\TestCase;

final class CredentialSerializationSecurityTest extends TestCase
{
    public function test_credentials_are_hidden_from_array_and_json_output(): void
    {
        $account = new ConnectedAccount();
        $account->forceFill(['name' => 'Example', 'token' => 'private-token', 'secret' => 'private-secret', 'refresh_token' => 'private-refresh-token']);

        self::assertSame(['name' => 'Example'], $account->toArray());
        self::assertSame('{"name":"Example"}', $account->toJson());
        self::assertSame('private-secret', $account->secret);
        self::assertSame('private-refresh-token', $account->refresh_token);
    }
}
