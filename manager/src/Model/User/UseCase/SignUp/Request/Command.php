<?php

declare(strict_types=1);

namespace App\Model\User\UseCase\SignUp\Request;

class Command
{
    /**
     * @var string
     * @Assert\NotBlank()
     * @Assert\Email()
     */
    public $email;
    /**
     * @var string
     * @Assert\NotBlank()
     * @Assert\Length(min=6)
     */
    public $password;
}