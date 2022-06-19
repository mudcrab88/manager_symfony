<?php

 namespace App\Model\User\Entity\User;

 use App\Model\User\Entity\User\Email;
 use App\Model\User\Entity\User\Id;
 use Doctrine\Common\Collections\ArrayCollection;
 use Doctrine\ORM\Mapping as ORM;

 /**
  * @ORM\Entity
  * @ORM\HasLifecycleCallbacks
  * @ORM\Table(name="user_users", uniqueConstraints={
  *     @ORM\UniqueConstraint(columns={"email"}),
  *     @ORM\UniqueConstraint(columns={"reset_token_token"})
  * })
  */
 class User
 {
     public const STATUS_WAIT = 'wait';
     public const STATUS_ACTIVE = 'active';
     public const STATUS_NEW = 'new';

     /**
      * @var Id
      * @ORM\Column(type="user_user_id")
      * @ORM\Id
      */
     private $id;

     /**
      * @var \DateTimeImmutable
      * @ORM\Column(type="datetime_immutable")
      */
     private $date;


     /**
      * @var Email|null
      * @ORM\Column(type="user_user_email", nullable=true)
      */
     private $email;

     /**
      * @var string
      * @ORM\Column(type="string", name="password_hash", nullable=true)
      */
     private $passwordHash;

     /**
      * @var string
      * @ORM\Column(type="string", name="confirm_token", nullable=true)
      */
     private $confirmToken;

     /**
      * @var string
      * @ORM\Column(type="string", length=16)
      */
     private $status;

     /**
      * @var Network[]|ArrayCollection
      * @ORM\OneToMany(targetEntity="Network", mappedBy="user", orphanRemoval=true, cascade={"persist"})
      */
     private $networks;

     /**
      * @var ResetToken|null
      * @ORM\Embedded(class="ResetToken", columnPrefix="reset_token_")
      */
     private $resetToken;

     /**
      * @var Role
      * @ORM\Column(type="user_user_role", length=16)
      */
     private $role;


     public function __construct(Id $id, \DateTimeImmutable $date)
     {
         $this->id = $id;
         $this->date = $date;
         $this->networks = new ArrayCollection();
         $this->status = self::STATUS_NEW;
         $this->role = Role::user();
     }

     /**
      * @return Id
      */
     public function getId(): Id
     {
         return $this->id;
     }

     /**
      * @return \DateTimeImmutable
      */
     public function getDate(): \DateTimeImmutable
     {
         return $this->date;
     }

     /**
      * @return Email
      */
     public function getEmail(): Email
     {
         return $this->email;
     }

     /**
      * @return string
      */
     public function getPasswordHash(): string
     {
         return $this->passwordHash;
     }

     /**
      * @return string
      */
     public function getConfirmToken(): ?string
     {
         return $this->confirmToken;
     }

     /**
      * @return Network[]
      */
     public function getNetworks(): array
     {
         return $this->networks->toArray();
     }

     /**
      * @return ResetToken|null
      */
     public function getResetToken(): ?ResetToken
     {
         return $this->resetToken;
     }

     /**
      * @return bool
      */
     public function isWait(): bool
     {
         return $this->status === self::STATUS_WAIT;
     }

     /**
      * @return bool
      */
     public function isActive(): bool
     {
         return $this->status === self::STATUS_ACTIVE;
     }

     /**
      * @return bool
      */
     public function isNew(): bool
     {
         return $this->status === self::STATUS_NEW;
     }

     /**
      * @return string
      */
     public function getStatus(): string
     {
         return $this->status;
     }

     /**
      * @return Role
      */
     public function getRole(): Role
     {
         return $this->role;
     }

     public static function signUpByEmail(Id $id, \DateTimeImmutable $date, Email $email, string $hash, string $token): self
     {
         $user = new self($id, $date);
         $user->email = $email;
         $user->passwordHash = $hash;
         $user->confirmToken = $token;
         $user->status = self::STATUS_WAIT;
         return $user;
     }

     public function confirmSignUp(): void
     {
         if (!$this->isWait()) {
             throw new \DomainException('User is already confirmed.');
         }

         $this->status = self::STATUS_ACTIVE;
         $this->confirmToken = null;
     }

     public static function signUpByNetwork(Id $id, \DateTimeImmutable $date, string $network, string $identity): self
     {
         $user = new self($id, $date);
         $user->attachNetwork($network, $identity);
         $user->status = self::STATUS_ACTIVE;
         return $user;
     }

     public function attachNetwork(string $network, string $identity): void
     {
         foreach ($this->networks as $existing) {
             if ($existing->isForNetwork($network)) {
                 throw new \DomainException('Network is already attached.');
             }
         }
         $this->networks->add(new Network($this, $network, $identity));
     }

     public function detachNetwork(string $network, string $identity): void
     {
         foreach ($this->networks as $existing) {
             if ($existing->isFor($network, $identity)) {
                 if (!$this->email && $this->networks->count() === 1) {
                     throw new \DomainException('Unable to detach the last identity.');
                 }
                 $this->networks->removeElement($existing);
                 return;
             }
         }
         throw new \DomainException('Network is not attached.');
     }

     public function requestPasswordReset(ResetToken $token, \DateTimeImmutable $date): void
     {
         if (!$this->isActive()) {
             throw new \DomainException('User is not active.');
         }
         if (!$this->email) {
             throw new \DomainException('Email is not specified.');
         }
         if ($this->resetToken && !$this->resetToken->isExpiredTo($date)) {
             throw new \DomainException('Resetting is already requested.');
         }
         $this->resetToken = $token;
     }

     public function passwordReset(\DateTimeImmutable $date, string $hash): void
     {
         if (!$this->resetToken) {
             throw new \DomainException('Resetting is not requested.');
         }
         if ($this->resetToken->isExpiredTo($date)) {
             throw new \DomainException('Reset token is expired.');
         }
         $this->passwordHash = $hash;
         $this->resetToken = null;
     }

     public function changeRole(Role $role): void
     {
         if ($this->role->isEqual($role)) {
             throw new \DomainException('Role is already same.');
         }
         $this->role = $role;
     }

     /**
      * @ORM\PostLoad()
      */
     public function checkEmbeds(): void
     {
         if ($this->resetToken->isEmpty()) {
             $this->resetToken = null;
         }
     }
 }