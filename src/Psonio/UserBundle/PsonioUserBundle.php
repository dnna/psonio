<?php

namespace Psonio\UserBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class PsonioUserBundle extends Bundle
{
    public function getParent()
    {
        return 'FOSUserBundle';
    }
}
