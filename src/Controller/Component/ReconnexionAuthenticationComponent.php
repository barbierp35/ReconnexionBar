<?php

namespace ReconnexionBar\Controller\Component;

use Cake\Controller\Component;

class ReconnexionAuthenticationComponent extends Component
{
    public function getUserData($data)
    {
        return $this->getController()->Authentication->getIdentity()->$data;
    }

    public function connectUser($user)
    {
        return $this->getController()->Authentication->setIdentity($user);
    }

    public function disconnectUser()
    {
        return $this->getController()->Authentication->logout();
    }
}