<?php
declare(strict_types=1);

require_once __DIR__ . '/../repositories/UserRepository.php';
require_once __DIR__ . '/RepositoryTestCase.php';

final class UserRepositoryTest extends RepositoryTestCase
{
    private UserRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new UserRepository();
    }

    public function testCreateUserWithMissingNames(): void
    {
        $email = 'missing@example.com';
        $this->repo->createUser([
            'email' => $email,
            'password' => 'secret',
            'username' => 'user_missing',
        ]);

        $user = $this->repo->findByEmail($email);
        $this->assertSame('', $user['lpa_user_firstname']);
        $this->assertSame('', $user['lpa_user_lastname']);
        $this->assertEquals(2, $user['lpa_fk_user_group_ID']);
        $this->assertTrue($this->repo->emailExists($email));
    }

    public function testCreateUserWithCustomGroup(): void
    {
        $email = 'admin@example.com';
        $this->repo->createUser([
            'email' => $email,
            'password' => 'secret',
            'username' => 'admin_user',
            'firstname' => 'Admin',
            'lastname' => 'User',
            'group_id' => 1,
        ]);

        $user = $this->repo->findByEmail($email);
        $this->assertEquals(1, $user['lpa_fk_user_group_ID']);
        $this->assertTrue($this->repo->emailExists($email));
    }

    public function testDuplicateEmailThrowsException(): void
    {
        $email = 'dup@example.com';
        $this->repo->createUser([
            'email' => $email,
            'password' => 'secret',
            'username' => 'dup_user1',
        ]);

        $this->assertNotFalse($this->repo->findByEmail($email));
        $this->assertTrue($this->repo->emailExists($email));

        $this->expectException(PDOException::class);
        $this->repo->createUser([
            'email' => $email,
            'password' => 'secret',
            'username' => 'dup_user2',
        ]);
    }
}
