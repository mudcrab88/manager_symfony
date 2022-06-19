<?php

declare(strict_types=1);

namespace App\Tests\Unit\Model\User\Entity\User\SignUp;
use App\Model\User\Entity\User\Id;
use App\Model\User\Entity\User\User;
use App\Tests\Builder\User\UserBuilder;
use PHPUnit\Framework\TestCase;
use App\Model\User\Entity\User\Email;

class ConfirmTest extends TestCase
{
    public function testSuccess(): void
    {
        $user = (new UserBuilder())->viaEmail()->build();

        $user->confirmSignup();

        self::assertFalse($user->isWait());
        self::assertTrue($user->isActive());
        self::assertNull($user->getConfirmToken());
    }

    public function testAlready(): void
    {
        $user = (new UserBuilder())->viaEmail()->build();
        $user->confirmSignUp();

        $this->expectExceptionMessage('User is already confirmed.');
        $user->confirmSignup();
    }
}
