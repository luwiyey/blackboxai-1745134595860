<?php
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use User;
use Database;
use Logger;

class UserTest extends TestCase
{
    private $user;
    private $db;
    private $validUserData;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Initialize test database connection
        $this->db = $this->createMock(Database::class);
        $this->user = User::getInstance();
        
        // Set up valid test data
        $this->validUserData = [
            'name' => 'John Doe',
            'email' => 'john.doe@panpacific.edu.ph',
            'password' => 'Test123!@#',
            'student_id' => '2023-12345',
            'role' => 'student'
        ];
    }

    public function testUserCreationWithValidData()
    {
        // Arrange
        $this->db->expects($this->once())
            ->method('insert')
            ->willReturn(1);

        // Act
        $userId = $this->user->create($this->validUserData);

        // Assert
        $this->assertIsInt($userId);
        $this->assertEquals(1, $userId);
    }

    public function testUserCreationWithInvalidEmail()
    {
        // Arrange
        $invalidData = $this->validUserData;
        $invalidData['email'] = 'invalid-email';

        // Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The email must be a valid email address');

        // Act
        $this->user->create($invalidData);
    }

    public function testUserCreationWithInvalidPassword()
    {
        // Arrange
        $invalidData = $this->validUserData;
        $invalidData['password'] = '123'; // Too short

        // Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The password must be at least 8 characters');

        // Act
        $this->user->create($invalidData);
    }

    public function testUserCreationWithInvalidStudentId()
    {
        // Arrange
        $invalidData = $this->validUserData;
        $invalidData['student_id'] = '12345'; // Invalid format

        // Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The student id must be in the format YYYY-XXXXX');

        // Act
        $this->user->create($invalidData);
    }

    public function testUserCreationWithDuplicateEmail()
    {
        // Arrange
        $this->db->expects($this->once())
            ->method('exists')
            ->willReturn(true);

        // Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Email already registered');

        // Act
        $this->user->create($this->validUserData);
    }

    public function testUserUpdateWithValidData()
    {
        // Arrange
        $userId = 1;
        $updateData = [
            'name' => 'John Updated',
            'email' => 'john.updated@panpacific.edu.ph'
        ];

        $this->db->expects($this->once())
            ->method('update')
            ->willReturn(true);

        // Act
        $result = $this->user->update($userId, $updateData);

        // Assert
        $this->assertTrue($result);
    }

    public function testUserUpdateWithInvalidEmail()
    {
        // Arrange
        $userId = 1;
        $invalidData = [
            'email' => 'invalid-email'
        ];

        // Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The email must be a valid email address');

        // Act
        $this->user->update($userId, $invalidData);
    }

    public function testPasswordUpdateWithValidData()
    {
        // Arrange
        $userId = 1;
        $currentPassword = 'Current123!@#';
        $newPassword = 'New123!@#';

        $this->db->expects($this->once())
            ->method('fetchOne')
            ->willReturn([
                'id' => 1,
                'password' => password_hash($currentPassword, PASSWORD_BCRYPT)
            ]);

        $this->db->expects($this->once())
            ->method('update')
            ->willReturn(true);

        // Act
        $result = $this->user->updatePassword($userId, $currentPassword, $newPassword);

        // Assert
        $this->assertTrue($result);
    }

    public function testPasswordUpdateWithIncorrectCurrentPassword()
    {
        // Arrange
        $userId = 1;
        $currentPassword = 'WrongPassword123!@#';
        $newPassword = 'New123!@#';

        $this->db->expects($this->once())
            ->method('fetchOne')
            ->willReturn([
                'id' => 1,
                'password' => password_hash('ActualPassword123!@#', PASSWORD_BCRYPT)
            ]);

        // Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Current password is incorrect');

        // Act
        $this->user->updatePassword($userId, $currentPassword, $newPassword);
    }

    public function testEmailVerificationWithValidToken()
    {
        // Arrange
        $token = 'valid_token';
        $userData = [
            'id' => 1,
            'email_verified' => 0,
            'verification_token' => $token
        ];

        $this->db->expects($this->once())
            ->method('fetchOne')
            ->willReturn($userData);

        $this->db->expects($this->once())
            ->method('update')
            ->willReturn(true);

        // Act
        $result = $this->user->verifyEmail($token);

        // Assert
        $this->assertTrue($result);
    }

    public function testEmailVerificationWithInvalidToken()
    {
        // Arrange
        $token = 'invalid_token';

        $this->db->expects($this->once())
            ->method('fetchOne')
            ->willReturn(null);

        // Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid verification token');

        // Act
        $this->user->verifyEmail($token);
    }

    public function testPasswordResetRequestWithValidEmail()
    {
        // Arrange
        $email = 'john.doe@panpacific.edu.ph';
        $userData = [
            'id' => 1,
            'name' => 'John Doe',
            'email' => $email
        ];

        $this->db->expects($this->once())
            ->method('fetchOne')
            ->willReturn($userData);

        $this->db->expects($this->once())
            ->method('update')
            ->willReturn(true);

        // Act
        $result = $this->user->requestPasswordReset($email);

        // Assert
        $this->assertTrue($result);
    }

    public function testPasswordResetRequestWithInvalidEmail()
    {
        // Arrange
        $email = 'nonexistent@panpacific.edu.ph';

        $this->db->expects($this->once())
            ->method('fetchOne')
            ->willReturn(null);

        // Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Email not found');

        // Act
        $this->user->requestPasswordReset($email);
    }

    public function testPasswordResetWithValidToken()
    {
        // Arrange
        $token = 'valid_token';
        $newPassword = 'NewPassword123!@#';
        $userData = [
            'id' => 1,
            'reset_token' => $token,
            'reset_token_expiry' => date('Y-m-d H:i:s', strtotime('+1 hour'))
        ];

        $this->db->expects($this->once())
            ->method('fetchOne')
            ->willReturn($userData);

        $this->db->expects($this->once())
            ->method('update')
            ->willReturn(true);

        // Act
        $result = $this->user->resetPassword($token, $newPassword);

        // Assert
        $this->assertTrue($result);
    }

    public function testPasswordResetWithExpiredToken()
    {
        // Arrange
        $token = 'expired_token';
        $newPassword = 'NewPassword123!@#';
        $userData = [
            'id' => 1,
            'reset_token' => $token,
            'reset_token_expiry' => date('Y-m-d H:i:s', strtotime('-1 hour'))
        ];

        $this->db->expects($this->once())
            ->method('fetchOne')
            ->willReturn($userData);

        // Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid or expired reset token');

        // Act
        $this->user->resetPassword($token, $newPassword);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->user = null;
        $this->db = null;
    }
}
