<?php

namespace ReconnexionBar\Controller\Component;

use Cake\Controller\Component;

class ReconnexionAuthComponent extends Component
{
    public function getUserData($data)
    {
        return $this->getController()->Auth->user($data);
    }

    public function connectUser($user)
    {
        return $this->getController()->Auth->setUser($user);
    }

    public function disconnectUser()
    {
        return $this->getController()->Auth->logout();
    }
}