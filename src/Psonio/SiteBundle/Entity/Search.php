<?php
namespace Psonio\SiteBundle\Entity;

use Symfony\Component\Validator\Constraints as Assert;

class Search {
    /**
     * @Assert\NotBlank()
     */
    protected $product;

    protected $area;

    public function getProduct() {
        return $this->product;
    }

    public function setProduct($product) {
        $this->product = $product;
    }

    public function getArea() {
        return $this->area;
    }

    public function setArea($area) {
        $this->area = $area;
    }
}
?>