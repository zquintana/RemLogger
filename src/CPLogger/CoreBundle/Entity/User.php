<?php

namespace CPLogger\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\ExecutionContextInterface;

/**
 * User
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class User
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=255)
     */
    private $email;

    /**
     * @var string
     *
     * @ORM\Column(name="password_hash", type="string", length=255)
     */
    private $passwordHash;

    /**
     * @var string
     *
     * @ORM\Column(name="password_salt", type="string", length=255)
     */
    private $passwordSalt;

    /**
     * @var string
     *
     * @ORM\Column(name="api_id", type="string", length=255)
     */
    private $apiId = null;

    /**
     * @var string
     *
     * @ORM\Column(name="api_key_hash", type="string", length=255)
     */
    private $apiKeyHash = null;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="api_created_at", type="datetime")
     */
    private $apiCreatedAt = null;

    /**
     * @var \DateTime
     * 
     * @ORM\Column(name="last_login_at", type="datetime")
     */
    private $lastLoginAt;

    /**
     * @var string
     */
    private $password;

    /**
     * @var string
     */
    private $password_confirm;
    


    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set email
     *
     * @param string $email
     * @return User
     */
    public function setEmail($email)
    {
        $this->email = $email;
    
        return $this;
    }

    /**
     * Get email
     *
     * @return string 
     */
    public function getEmail()
    {
        return $this->email;
    }

    public function getPassword() {
        return $this->password;
    }

    public function getPasswordConfirm() {
        return $this->password_confirm;
    }

    public function setPassword($password) {
        $this->password = $password;

        $this->setPasswordSalt(substr(time(), 2, 10));
        $this->setPasswordHash(
            $this->crypt($this->password, $this->getPasswordSalt())
        );

        return $this;
    }

    public function setPasswordConfirm($passwordConfirm) {
        $this->password_confirm = $passwordConfirm;
        return $this;
    }

    public function crypt($password) {
        return crypt($password, '$1$' . $this->getPasswordSalt() . '$');
    }

    public function passwordMatch($raw) {
        return $this->crypt($raw) == $this->getPasswordHash();
    }

    /**
     * Set passwordHash
     *
     * @param string $passwordHash
     * @return User
     */
    public function setPasswordHash($passwordHash)
    {
        $this->passwordHash = $passwordHash;
    
        return $this;
    }

    /**
     * Get passwordHash
     *
     * @return string 
     */
    public function getPasswordHash()
    {
        return $this->passwordHash;
    }

    /**
     * Set passwordSalt
     *
     * @param string $passwordSalt
     * @return User
     */
    public function setPasswordSalt($passwordSalt)
    {
        $this->passwordSalt = $passwordSalt;
    
        return $this;
    }

    /**
     * Get passwordSalt
     *
     * @return string 
     */
    public function getPasswordSalt()
    {
        return $this->passwordSalt;
    }

    /**
     * Set apiId
     *
     * @param string $apiId
     * @return User
     */
    public function setApiId($apiId)
    {
        $this->apiId = $apiId;
    
        return $this;
    }

    /**
     * Get apiId
     *
     * @return string 
     */
    public function getApiId()
    {
        return $this->apiId;
    }

    /**
     * Set apiKeyHash
     *
     * @param string $apiKeyHash
     * @return User
     */
    public function setApiKeyHash($apiKeyHash)
    {
        $this->apiKeyHash = $apiKeyHash;
    
        return $this;
    }

    /**
     * Get apiKeyHash
     *
     * @return string 
     */
    public function getApiKeyHash()
    {
        return $this->apiKeyHash;
    }

    /**
     * Set apiCreatedAt
     *
     * @param \DateTime $apiCreatedAt
     * @return User
     */
    public function setApiCreatedAt($apiCreatedAt)
    {
        $this->apiCreatedAt = $apiCreatedAt;
    
        return $this;
    }

    /**
     * Get apiCreatedAt
     *
     * @return \DateTime 
     */
    public function getApiCreatedAt()
    {
        return $this->apiCreatedAt;
    }

    public function setLastLoginAt($lastLoginAt) {
        $this->lastLoginAt = $lastLoginAt;
        return $this;
    }

    public function getLastLoginAt() {
        return $this->lastLoginAt;
    }

    public function getHash() {
        return md5($this->getEmail() . "|" . $this->getPasswordHash() . "|" . $this->getLastLoginAt()->getTimestamp());
    }

    public function verifyHash($hash) {
        return $hash === $this->getHash();
    }

    public function generateApi() {
        $this->setApiCreatedAt(new \DateTime());
        $time = $this->getApiCreatedAt()->getTimestamp();

        $this->setApiId(substr(md5($time), 2, 8));
        $this->setApiKeyHash($this->cryptApi($this->getApiKey()));

        return $this;
    }

    public function getApiKey() {
        return md5(sha1($this->getApiId() . "|" . $this->getApiCreatedAt()->getTimestamp()));
    }

    public function cryptApi($key) {
        $salt = substr(md5(md5($this->getApiCreatedAt()->getTimestamp())), 0, 10);
        return crypt($key, '$2a$08$' . $salt . '$');
    }

    public function validateApi($key) {
        return $this->cryptApi($key) === $this->getApiKeyHash();
    }

    /**
     * Validator methods
     * 
     * @return null
     */
    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraint('password', new Assert\Length(array(
            'min' => 6,
            'minMessage' => 'Password must be at least 6 characters'
        )));
        $metadata->addConstraint(new Assert\Callback(array(
            'methods' => array('isConfirmedPassword'),
        )));
    }

    public function isConfirmedPassword(ExecutionContextInterface $context) 
    {
        if ($this->getPassword() !== $this->getPasswordConfirm()) {
            $context->addViolationAt('password_confirm', 'Passwords do not match', [], null);
        }
    }
}
