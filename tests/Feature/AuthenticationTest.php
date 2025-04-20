<?php
namespace Tests\Feature;

use PHPUnit\Framework\TestCase;
use Auth;
use User;
use Database;
use Logger;
use Session;

class AuthenticationTest extends TestCase
{
    private $auth;
    private $db;
    private $validCredentials;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Initialize test database connection
        $this->db = $this->createMock(Database::class);
        $this->auth = Auth::getInstance();
        
        // Set up valid test credentials
        $this->validCredentials = [
            'email' => 'john.doe@panpacific.edu.ph',
            'password' => 'Test123!@#'
        ];

        // Start session for testing
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function testSuccessfulLogin()
    {
        // Arrange
        $hashedPassword = password_hash($this->validCredentials['password'], PASSWORD_BCRYPT);
        $userData = [
            'id' => 1,
            'name' => 'John Doe',
            'email' => $this->validCredentials['email'],
            'password' => $hashedPassword,
            'role' => 'student',
            'status' => 'active',
            'student_id' => '2023-12345'
        ];

        $this->db->expects($this->once())
            ->method('fetchOne')
            ->willReturn($userData);

        $this->db->expects($this->once())
            ->method('update')
            ->willReturn(true);

        // Act
        $user = $this->auth->login(
            $this->validCredentials['email'],
            $this->validCredentials['password']
        );

        // Assert
        $this->assertIsArray($user);
        $this->assertEquals($userData['id'], $user['id']);
        $this->assertEquals($userData['email'], $user['email']);
        $this->assertTrue(isset($_SESSION['user']));
        $this->assertEquals($userData['id'], $_SESSION['user']['id']);
    }

    public function testLoginWithInvalidEmail()
    {
        // Arrange
        $this->db->expects($this->once())
            ->method('fetchOne')
            ->willReturn(null);

        // Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid email or password');

        // Act
        $this->auth->login('invalid@email.com', 'password123');
    }

    public function testLoginWithIncorrectPassword()
    {
        // Arrange
        $hashedPassword = password_hash('correct_password', PASSWORD_BCRYPT);
        $userData = [
            'id' => 1,
            'email' => $this->validCredentials['email'],
            'password' => $hashedPassword,
            'status' => 'active'
        ];

        $this->db->expects($this->once())
            ->method('fetchOne')
            ->willReturn($userData);

        // Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid email or password');

        // Act
        $this->auth->login($this->validCredentials['email'], 'wrong_password');
    }

    public function testLoginWithUnverifiedAccount()
    {
        // Arrange
        $hashedPassword = password_hash($this->validCredentials['password'], PASSWORD_BCRYPT);
        $userData = [
            'id' => 1,
            'email' => $this->validCredentials['email'],
            'password' => $hashedPassword,
            'status' => 'pending'
        ];

        $this->db->expects($this->once())
            ->method('fetchOne')
            ->willReturn($userData);

        // Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Please verify your email address');

        // Act
        $this->auth->login(
            $this->validCredentials['email'],
            $this->validCredentials['password']
        );
    }

    public function testLoginWithSuspendedAccount()
    {
        // Arrange
        $hashedPassword = password_hash($this->validCredentials['password'], PASSWORD_BCRYPT);
        $userData = [
            'id' => 1,
            'email' => $this->validCredentials['email'],
            'password' => $hashedPassword,
            'status' => 'suspended'
        ];

        $this->db->expects($this->once())
            ->method('fetchOne')
            ->willReturn($userData);

        // Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Your account has been suspended');

        // Act
        $this->auth->login(
            $this->validCredentials['email'],
            $this->validCredentials['password']
        );
    }

    public function testSuccessfulLogout()
    {
        // Arrange
        $_SESSION['user'] = [
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john.doe@panpacific.edu.ph'
        ];

        // Act
        $result = $this->auth->logout();

        // Assert
        $this->assertTrue($result);
        $this->assertFalse(isset($_SESSION['user']));
    }

    public function testIsLoggedInWithActiveSession()
    {
        // Arrange
        $_SESSION['user'] = [
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john.doe@panpacific.edu.ph'
        ];

        // Act
        $isLoggedIn = $this->auth->isLoggedIn();

        // Assert
        $this->assertTrue($isLoggedIn);
    }

    public function testIsLoggedInWithoutSession()
    {
        // Arrange
        unset($_SESSION['user']);

        // Act
        $isLoggedIn = $this->auth->isLoggedIn();

        // Assert
        $this->assertFalse($isLoggedIn);
    }

    public function testGetCurrentUserWithActiveSession()
    {
        // Arrange
        $userData = [
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john.doe@panpacific.edu.ph'
        ];
        $_SESSION['user'] = $userData;

        // Act
        $currentUser = $this->auth->getCurrentUser();

        // Assert
        $this->assertEquals($userData, $currentUser);
    }

    public function testGetCurrentUserWithoutSession()
    {
        // Arrange
        unset($_SESSION['user']);

        // Act
        $currentUser = $this->auth->getCurrentUser();

        // Assert
        $this->assertNull($currentUser);
    }

    public function testRequireLoginWithActiveSession()
    {
        // Arrange
        $_SESSION['user'] = [
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john.doe@panpacific.edu.ph'
        ];

        // Act & Assert
        try {
            $this->auth->requireLogin();
            $this->assertTrue(true); // Should reach this point
        } catch (\Exception $e) {
            $this->fail('Exception should not be thrown when user is logged in');
        }
    }

    public function testRequireLoginWithoutSession()
    {
        // Arrange
        unset($_SESSION['user']);

        // Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Authentication required');

        // Act
        $this->auth->requireLogin();
    }

    public function testRequireRoleWithCorrectRole()
    {
        // Arrange
        $_SESSION['user'] = [
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john.doe@panpacific.edu.ph',
            'role' => 'admin'
        ];

        // Act & Assert
        try {
            $this->auth->requireRole('admin');
            $this->assertTrue(true); // Should reach this point
        } catch (\Exception $e) {
            $this->fail('Exception should not be thrown for correct role');
        }
    }

    public function testRequireRoleWithIncorrectRole()
    {
        // Arrange
        $_SESSION['user'] = [
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john.doe@panpacific.edu.ph',
            'role' => 'student'
        ];

        // Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Unauthorized access');

        // Act
        $this->auth->requireRole('admin');
    }

    public function testCheckPermissionWithValidPermission()
    {
        // Arrange
        $_SESSION['user'] = [
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john.doe@panpacific.edu.ph',
            'role' => 'admin'
        ];

        // Act
        $hasPermission = $this->auth->checkPermission('manage_users');

        // Assert
        $this->assertTrue($hasPermission);
    }

    public function testCheckPermissionWithInvalidPermission()
    {
        // Arrange
        $_SESSION['user'] = [
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john.doe@panpacific.edu.ph',
            'role' => 'student'
        ];

        // Act
        $hasPermission = $this->auth->checkPermission('manage_users');

        // Assert
        $this->assertFalse($hasPermission);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->auth = null;
        $this->db = null;
        
        // Clear session
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }
}
