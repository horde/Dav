<?php

declare(strict_types=1);

namespace Horde\Dav\Test\Unit;

use Horde_Auth_Base;
use Horde_Dav_Principals;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Sabre\DAV\PropPatch;

#[CoversClass(Horde_Dav_Principals::class)]
class PrincipalsTest extends TestCase
{
    private Horde_Auth_Base $auth;
    private object $identities;
    private Horde_Dav_Principals $principals;

    protected function setUp(): void
    {
        $this->auth = $this->createMock(Horde_Auth_Base::class);
        $this->identities = new class () {
            public function create(string $user): object
            {
                return new class ($user) {
                    public function __construct(private readonly string $user)
                    {
                    }

                    public function getName(): string
                    {
                        return 'Display: ' . $this->user;
                    }

                    public function getDefaultFromAddress(): string
                    {
                        return $this->user . '@example.com';
                    }
                };
            }
        };
        $this->principals = new Horde_Dav_Principals(
            $this->auth,
            $this->identities
        );
    }

    public function testGetGroupMembershipReturnsSystemPrincipal(): void
    {
        $membership = $this->principals->getGroupMembership('principals/testuser');

        $this->assertSame(['principals/-system-'], $membership);
    }

    public function testSearchPrincipalsReturnsEmptyArray(): void
    {
        $result = $this->principals->searchPrincipals('principals', ['name' => 'test']);

        $this->assertSame([], $result);
    }

    public function testGetGroupMemberSetReturnsEmptyArray(): void
    {
        $result = $this->principals->getGroupMemberSet('principals/testuser');

        $this->assertSame([], $result);
    }

    public function testUpdatePrincipalIsNoOp(): void
    {
        $propPatch = new PropPatch([]);

        // Should not throw
        $this->principals->updatePrincipal('principals/testuser', $propPatch);

        $this->assertTrue(true);
    }

    public function testSetGroupMemberSetIsNoOp(): void
    {
        // Should not throw
        $this->principals->setGroupMemberSet('principals/testuser', ['principals/other']);

        $this->assertTrue(true);
    }

    public function testGetPrincipalByPathReturnsSystemPrincipal(): void
    {
        $this->auth->method('hasCapability')->willReturn(false);

        $info = $this->principals->getPrincipalByPath('principals/-system-');

        $this->assertSame('principals/-system-', $info['uri']);
        $this->assertArrayHasKey('{DAV:}displayname', $info);
    }

    public function testGetPrincipalByPathReturnsUserInfo(): void
    {
        $this->auth->method('hasCapability')->willReturn(false);

        $info = $this->principals->getPrincipalByPath('principals/jan');

        $this->assertSame('principals/jan', $info['uri']);
        $this->assertSame('Display: jan', $info['{DAV:}displayname']);
        $this->assertSame('jan@example.com', $info['{http://sabredav.org/ns}email-address']);
    }

    public function testGetPrincipalByPathThrowsForInvalidPrefix(): void
    {
        $this->expectException(\Sabre\DAV\Exception\NotFound::class);

        $this->principals->getPrincipalByPath('invalid/user');
    }

    public function testGetPrincipalsByPrefixThrowsForInvalidPrefix(): void
    {
        $this->expectException(\Sabre\DAV\Exception\NotFound::class);

        $this->principals->getPrincipalsByPrefix('invalid');
    }

    public function testGetPrincipalsByPrefixIncludesSystemUser(): void
    {
        $this->auth->method('hasCapability')
            ->with('list')
            ->willReturn(false);

        $principals = $this->principals->getPrincipalsByPrefix('principals');

        $this->assertCount(1, $principals);
        $this->assertSame('principals/-system-', $principals[0]['uri']);
    }

    public function testGetPrincipalsByPrefixListsUsersWhenCapable(): void
    {
        $this->auth->method('hasCapability')
            ->with('list')
            ->willReturn(true);
        $this->auth->method('listUsers')
            ->willReturn(['alice', 'bob']);

        $principals = $this->principals->getPrincipalsByPrefix('principals');

        $this->assertCount(3, $principals);
        $this->assertSame('principals/-system-', $principals[0]['uri']);
        $this->assertSame('principals/alice', $principals[1]['uri']);
        $this->assertSame('principals/bob', $principals[2]['uri']);
    }
}
