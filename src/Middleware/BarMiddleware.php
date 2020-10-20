<?php
namespace ReconnexionBar\Middleware;

use Cake\Core\Configure;
use Cake\Routing\Router;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Cake\Core\Plugin as CorePlugin;

/**
 * Bar middleware
 */
class BarMiddleware
{
    /**
     * Invoke method.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request.
     * @param \Psr\Http\Message\ResponseInterface $response The response.
     * @param callable $next Callback to invoke the next middleware.
     * @return \Psr\Http\Message\ResponseInterface A response
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, $next)
    {
        $response = $next($request, $response);

        // Skip debugkit requests and requestAction()
        $path = $request->getUri()->getPath();
        if (
            strpos($path, 'debug_kit') !== false ||
            strpos($path, 'debug-kit') !== false ||
            $request->is('requested')
        ) {
            return $response;
        }

        $body = $response->getBody();
        if (!$body->isSeekable() || !$body->isWritable()) {
            return $response;
        }
        
        $body->rewind();
        $contents = $body->getContents();

        // ReconnexionBar already injected?
        $posReconnexionBar = strrpos($contents, 'id="reconnexionbar"');
        if ($posReconnexionBar != false) {
            return $response;
        }

        // ReconnexionCircle already injected?
        $posReconnexionCircle = strrpos($contents, 'id="btn-reconnexion"');
        if ($posReconnexionCircle != false) {
            return $response;
        }

        // Affichage de la barre seulement si on est connecté à un autre compte
        if (!$request->getSession()->read('parentAccount')) {
            return $response;
        }

        // Head tag found?
        $posEndHead = strrpos($contents, '</head>');
        if ($posEndHead === false) {
            return $response;
        }

        // Inject Style CSS of the ReconnexionBar in head content before head end tag
        $contents = substr($contents, 0, $posEndHead) . '<link rel="stylesheet" href="' . Router::url($this->getCssUrl()) . '">' . substr($contents, $posEndHead);

        // Body tag found?
        $posEndBody = strrpos($contents, '</body>');
        if ($posEndBody === false) {
            return $response;
        }
        
        $body->rewind();
        // Inject ReconnexionBar in body content before body end tag
        $body->write($this->getContent($request, $contents, $posEndBody, $posEndHead));
        return $response->withBody($body);
    }

    /**
     * Retourne le content
     */
    public function getContent($request, $contents, $posEndBody, $posEndHead)
    {
        // Nom de la colonne pour récupérer les infos de l'utilisateur connecté
        $columnName = $this->getSessionAttribute($request, 'first_name');
        if (Configure::read('ReconnexionBar.column_name')) {
            $columnName = $this->getSessionAttribute($request, Configure::read('ReconnexionBar.column_name'));
        }

        // Lien vers la reconnexion du compte parent
        $linkActionReconnectParentAccount = Configure::read('ReconnexionBar.linkActionReconnectParentAccount') ?? ['prefix' => false, 'plugin' => false, 'controller' => 'Users', 'action' => 'reconnectParentAccount'];

        $color = Configure::read('ReconnexionBar.style.color') ?? '#e63757';

        // Si c'est en type circle
        if (Configure::read('ReconnexionBar.style.type') == 'circle') {
            switch (Configure::read('ReconnexionBar.style.position')) {
                case 'top-left':
                    $circlePosition = 'left: 20px;top: 20px;';
                    $modalPosition = 'left: 0;top: 0;';
                    break;
                case 'top-right':
                    $circlePosition = 'right: 20px;top: 20px;';
                    $modalPosition = 'right: 0;top: 0;';
                    break;
                case 'bottom-right':
                    $circlePosition = 'right: 20px;bottom: 20px;';
                    $modalPosition = 'right: 0;bottom: 20px;';
                    break;
                default:
                    $circlePosition = 'left: 20px;bottom: 20px;';
                    $modalPosition = 'left: 0;bottom: 0;';
                    break;
            }


            $reconnexion_bar =
            '<div id="btn-reconnexion" style="' . $circlePosition . '">' .
                '<div id="btn-reconnexion-image" style="background-color: ' . $color . ';">' .
                    '<img src="' . $this->getImageUrl() . '" onclick="openModalReconnexion()" />' .
                '</div>' .
                '<div id="modal-reconnexion" style="border: 1px solid ' . $color . ';' . $modalPosition . '">' .
                    '<div id="modal-reconnexion-text">' .
                        '<p id="modal-reconnexion-title">Reconnexion</p>' .
                        '<p>Vous êtes connecté à la place de <strong>' . $columnName . '</strong></p>' .
                    '</div>' .
                    '<div id="modal-reconnexion-button">' .
                        '<button onclick="closeModalReconnexion()" style="color: ' . $color . ';">Fermer</button>' .
                        '<a href="' . Router::url($linkActionReconnectParentAccount) . '" style="background: ' . $color . ';">Se reconnecter</a>' .
                    '</div>' .
                '</div>' .
            '</div>';
        } else {
            // Position de la barre
            $barPosition = Configure::read('ReconnexionBar.style.position') ?? 'bottom';
                
            // Si c'est en top, on descend le body de 25px
            if ($barPosition == 'top') {
                $contents = substr($contents, 0, $posEndHead) . '<style>body{padding-top: 25px;}</style>' . substr($contents, $posEndHead);

                // On recalcule le posEndBody car il a changé
                $posEndBody = strrpos($contents, '</body>');
            }
            
            $reconnexion_bar =
            '<div id="reconnexionbar" style="background-color: ' . $color . ';' . ($barPosition == 'top' ? 'top: 0;' : 'bottom: 0;') . '">' .
                '<div>' .
                    '<span class="hidden-xs">' . __('Vous êtes') . '</span> ' . __('connecté à la place de') . ' <strong>' . $columnName . '</strong>' .
                '</div>' .
                '<div style="text-align:right;">' .
                    '<a href="' . Router::url($linkActionReconnectParentAccount) . '" style="color:white;">' .
                        '<u>' . __('Se reconnecter') . '<span class="hidden-xs"> ' . __('à mon compte') . '</span></u>' .
                    '</a>' .
                '</div>' .
            '</div>';
        }

        $contents = substr($contents, 0, $posEndBody) . $reconnexion_bar . '<script src="' . Router::url($this->getScriptUrl()) . '"></script>' . substr($contents, $posEndBody);

        return $contents;
    }

    /**
     * Retourne l'url de l'image
     */
    private function getImageUrl()
    {
        $url = 'img/reconnect.png';
        $filePaths = [
            str_replace('/', DIRECTORY_SEPARATOR, WWW_ROOT . 'reconnexionbar/' . $url),
            str_replace('/', DIRECTORY_SEPARATOR, CorePlugin::path('DebugKit') . 'webroot/' . $url),
        ];
        $url = '/ReconnexionBar/' . $url;
        foreach ($filePaths as $filePath) {
            if (file_exists($filePath)) {
                return $url . '?' . filemtime($filePath);
            }
        }

        return $url;
    }

    /**
     * Retourne l'url du script JS
     */
    private function getScriptUrl()
    {
        $url = 'js/reconnexionbar.js';
        $filePaths = [
            str_replace('/', DIRECTORY_SEPARATOR, WWW_ROOT . 'reconnexionbar/' . $url),
            str_replace('/', DIRECTORY_SEPARATOR, CorePlugin::path('DebugKit') . 'webroot/' . $url),
        ];
        $url = '/ReconnexionBar/' . $url;
        foreach ($filePaths as $filePath) {
            if (file_exists($filePath)) {
                return $url . '?' . filemtime($filePath);
            }
        }

        return $url;
    }

    /**
     * Retourne l'url du style CSS
     */
    private function getCssUrl()
    {
        $url = 'css/reconnexionbar.css';
        $filePaths = [
            str_replace('/', DIRECTORY_SEPARATOR, WWW_ROOT . 'reconnexionbar/' . $url),
            str_replace('/', DIRECTORY_SEPARATOR, CorePlugin::path('DebugKit') . 'webroot/' . $url),
        ];
        $url = '/ReconnexionBar/' . $url;
        foreach ($filePaths as $filePath) {
            if (file_exists($filePath)) {
                return $url . '?' . filemtime($filePath);
            }
        }

        return $url;
    }

    private function getSessionAttribute($request, $columnName)
    {
        // Récupération de la donnée en fonction du component Auth utilisé
        if (!empty($request->getSession()->read('Auth.User'))) {
            return $request->getSession()->read('Auth.User.' . $columnName);
        }

        return $request->getSession()->read('Auth.' . $columnName);
    }
}