<?php
namespace Psonio\UserBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\OrderBy;
use FOS\UserBundle\Entity\User as BaseUser;
use Symfony\Component\Validator\Constraints as Assert;
use Oh\GoogleMapFormTypeBundle\Validator\Constraints as OhAssert;

/**
 * @ORM\Entity
 * @ORM\Table(name="Users")
 * @ORM\Entity(repositoryClass="Psonio\UserBundle\Entity\Repositories\UserRepository")
 */
class User extends BaseUser
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;
    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $name;
    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $surname;
    /**
     * @ORM\Column(type="integer")
     */
    protected $capacity = 0;

    const CAPACITY_PRESIDENT = 0;
    const CAPACITY_SOCIALWORKER = 1;
    const CAPACITY_IT = 2;

    public function getId() {
        return $this->id;
    }

    public function setId($id) {
        $this->id = $id;
    }

    public function getName() {
        return $this->name;
    }

    public function setName($name) {
        $this->name = $name;
    }

    public function getSurname() {
        return $this->surname;
    }

    public function setSurname($surname) {
        $this->surname = $surname;
    }

    public function getCapacity() {
        return $this->capacity;
    }

    public function getCapacityAsString() {
        $capacities = self::getCapacities();
        return $capacities[$this->capacity];
    }

    public function setCapacity($capacity) {
        $this->capacity = $capacity;
    }

    public static function getCapacities() {
        return array(
            self::CAPACITY_PRESIDENT => 'president',
            self::CAPACITY_SOCIALWORKER => 'social worker',
            self::CAPACITY_IT => 'it support',
        );
    }
}