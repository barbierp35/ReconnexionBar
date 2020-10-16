<?php
namespace ReconnexionBar\Middleware;

use Cake\Core\Configure;
use Cake\Routing\Router;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

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
        $pos = strrpos($contents, 'id="reconnexionbar"');
        if ($pos != false) {
            return $response;
        }

        // Body tag found?
        $pos = strrpos($contents, '</body>');
        if ($pos === false) {
            return $response;
        }

        // Affichage de la barre seulement si on est connecté à un autre compte
        if (!$request->getSession()->read('parentAccount')) {
            return $response;
        }

        $body->rewind();

        $column_name = $request->getSession()->read('Auth.User.first_name');
        if (Configure::read('ReconnexionBar.column_name')) {
            if (is_array(Configure::read('ReconnexionBar.column_name'))) {
                $column_name = '';
                foreach (Configure::read('ReconnexionBar.column_name') as $column) {
                    $column_name .= $request->getSession()->read('Auth.User.' . $column) . ' ';
                }
            } else {
                $column_name = $request->getSession()->read('Auth.User.' . Configure::read('ReconnexionBar.column_name'));
            }
        }

        $linkActionReconnectParentAccount = Configure::read('ReconnexionBar.linkActionReconnectParentAccount') ?? ['prefix' => false, 'plugin' => false, 'controller' => 'Users', 'action' => 'reconnectParentAccount'];

        $reconnexion_bar =
            '<div id="reconnexionbar" style="display: flex; justify-content: space-between; width:100%;padding:2px 10px;background-color: #e63757;position: absolute;bottom: 0;left: 0;font-size: 14px;color:white;z-index:9999;">' .
                '<div>' .
                    'Vous êtes connecté à la place de ' . $column_name .
                '</div>' .
                '<div style="text-align:right;">' .
                    '<a href="' . Router::url($linkActionReconnectParentAccount) . '" style="color:white;">' .
                        '<u>Se reconnecter <span class="hidden-xs"> à mon compte</span></u>' .
                    '</a>' .
                '</div>' .
            '</div>';
        
        // Inject ReconnexionBar in body content before body end tag
        $contents = substr($contents, 0, $pos) . $reconnexion_bar . substr($contents, $pos);
        
        $body->rewind();
        $body->write($contents);
        return $response->withBody($body);
    }
}