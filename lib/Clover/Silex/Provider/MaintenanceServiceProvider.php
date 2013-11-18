<?php
namespace Clover\Silex\Provider;

use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Usage:
 *
 * $app->register(Clover\Silex\Provider\MaintenanceServiceProvider([
 *     'lock' => __DIR__ . '/../maintenance',
 *     'html' => __DIR__ . '/../web/maintenance.html',
 * ]));
 *
 * or
 *
 * $app->register(Clover\Silex\Provider\MaintenanceServiceProvider([
 *     'lock' => __DIR__ . '/../maintenance',
 *     'twig_template' => 'maintenance.html',
 * ]));
 */
class MaintenanceServiceProvider implements ServiceProviderInterface
{
    protected $isMaintenanceMode = false;
    protected $htmlFile;
    protected $twigTemplate;

    public function __construct(array $options)
    {
        if (isset($options['lock'])) {
            $this->isMaintenanceMode = is_file($options['lock']);
        }
        if (isset($options['html'])) {
            if (is_file($options['html']) && is_readable($options['html'])) {
                $this->htmlFile = $options['html'];
            }
        }
        if (isset($options['twig_template'])) {
            $this->twigTemplate = $options['twig_template'];
        }
    }

    public function register(Application $app)
    {
        $app['maintenance.enabled'] = false;
        if ($this->isMaintenanceMode) {
            $app['maintenance.enabled'] = true;
            if ($this->htmlFile) {
                $app['maintenance.html'] = file_get_contents($this->htmlFile);
                $app->match('/{path}', function() use ($app) {
                    return new Response($app['maintenance.html'], 503);
                })->assert('path', '.*');
            } elseif (isset($app['twig']) && $this->twigTemplate) {
                $app['maintenance.twig_template'] = $this->twigTemplate;
                $app->match('/{path}', function() use ($app) {
                    $response = new Response();
                    $response->setStatusCode(503);
                    $response->setContent($app['twig']->render($app['maintenance.twig_template']));
                    return $response;
                })->assert('path', '.*');
            } else {
                $app->match('/{path}', function() use ($app) {
                    return $app->abort(503);
                })->assert('path', '.*');
            }
        }
    }

    public function boot(Application $app)
    {
    }
}
