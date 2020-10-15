# ReconnexionBar plugin for CakePHP

## Installation

This plugin help you to connect to another account and reconnect on your parent account.
When you are connected to another account, it displays a red bar at the bottom of all the pages of your application to reconnect on your parent account.

You can install this plugin into your CakePHP application using [composer](https://getcomposer.org).

The recommended way to install composer packages is:

```
composer require barbierp35/reconnexion-bar
```

Then you'll need to load the plugin in the bootstrap method of the `src/Application.php` file.

```php
$this->addPlugin('ReconnexionBar');
```

or you can use the console to do this for you.

```bash
bin/cake plugin load ReconnexionBar
```

## Configuration

Optional, you can change the configuration in your bootstrap.php file :
```
Configure::write('ReconnexionBar', [
    'column_name' => 'first_name', // Name of the column (string) or list of columns (array) you want to show in the bar the know which account is connected
    'linkActionReconnectParentAccount' => [ // Url of action to reconnect on parent account
        'prefix' => false,
        'plugin' => false,
        'controller' => 'Users',
        'action' => 'reconnectParentAccount'
    ]
]);
```

You must load the ReconnexionBarComponent and create 2 actions to use this plugin.
For exemple, in the UsersController.php file :
```
public function initialize()
{
    parent::initialize();
    $this->loadComponent('ReconnexionBar.Reconnexion');
}

/**
* Action to connect on another account
*/
public function connectOtherAccount($userId)
{
    $user = $this->Users->get($userId);
    $this->Reconnexion->connectOtherAccount($user);
}

/**
* Action to reconnect on the parent account
*/
public function reconnectParentAccount()
{
    $this->Reconnexion->reconnectParentAccount();
}
```