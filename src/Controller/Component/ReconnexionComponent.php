<?php

namespace ReconnexionBar\Controller\Component;

use Cake\ORM\TableRegistry;
use Cake\Controller\Component;

class ReconnexionComponent extends Component
{
    /**
     * Connexion sur un autre compte
     */
    public function connectOtherAccount($user)
    {
        // On modifie le tableau en session pour ajouter l'id de le referer de l'user actuellement connecté
        $sessionParentAccount = [];
        if (!empty($this->getController()->getRequest()->getSession()->read('parentAccount'))) {
            $sessionParentAccount = $this->getController()->getRequest()->getSession()->read('parentAccount');
        }
        array_push($sessionParentAccount, ['id' => $this->getController()->Auth->user('id'), 'referer' => $this->getController()->referer()]);

        $this->getController()->getRequest()->getSession()->write('parentAccount', $sessionParentAccount);
        $this->getController()->Auth->setUser($user);

        $this->getController()->redirect('/');
    }

    /**
     * Reconnexion au compte parent
     */
    public function reconnectParentAccount()
    {
        // Si parentAccount n'existe pas en session, on déconnecte l'utilisateur
        if (empty($this->getController()->getRequest()->getSession()->read('parentAccount'))) {
            return $this->getController()->redirect($this->getController()->Auth->logout());
        }

        // Récupération des parentAccount en session
        $sessionParentAccount = $this->getController()->getRequest()->getSession()->read('parentAccount');

        // Suppression du dernier élément du tableau et récupération de celui ci dans la variable $parentAccount
        $parentAccount = array_pop($sessionParentAccount);

        // Modification de la variable de session pour enlever le dernier parentAccount
        $this->getController()->getRequest()->getSession()->write('parentAccount', $sessionParentAccount);

        // Modification de l'user connecté
        $userTable = TableRegistry::getTableLocator()->get('Users');
        $user = $userTable->get($parentAccount['id']);
        $this->getController()->Auth->setUser($user);

        return $this->getController()->redirect($parentAccount['referer']);
    }

    /**
     * Suppression de la variable de session parentAccount
     */
    public function deleteReconnectSession()
    {
        $this->getController()->getRequest()->getSession()->delete('parentAccount');
    }
}