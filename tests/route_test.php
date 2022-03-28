<?php

require __DIR__ . "/../vendor/autoload.php";

use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;
$root = new RouteCollection();
$route = new Route('/foo', array('controller' => 'MyController'));
$routes = new RouteCollection();


$routes->add('route_name', $route);



$route = new Route(
    '/archive/{month}', // path
    array('controller' => 'showArchive', 'asd' => 'feswf'), // default values
    array('month' => '[0-9]{4}-[0-9]{2}', 'subdomain' => 'www|m'), // requirements
    array(), // options
    '', // host
    array(), // schemes
    array() // methods
);

// ...

$routes->add('date', new Route(
    '/archive/{month}', // path
    array('controller' => 'showArchive', 'asd' => 'feswf'), // default values
    array('month' => '[0-9]{4}-[0-9]{2}', 'subdomain' => 'www|m'), // requirements
    array(), // options
    '', // host
    array(), // schemes
    array() // methods
));



$route = new Route('/archive/test');

$routes->add('qwerty', $route);

$route = new Route('/{aas}/{test}', ['_class' => stdClass::class, '_method' => 'foo'],[],["class" => stdClass::class]);

$routes->add('root', $route);

$context = new RequestContext();

//$root->addCollection($routes);
$matcher = new UrlMatcher($root, $context);
$root->addCollection($routes);

dump($root->all());
//$parameters = $matcher->match('/test/foo');var_dump($parameters);
// array(
//     'controller' => 'showArchive',
//     'month' => '2012-01',
//     'subdomain' => 'www',
//     '_route' => ...
//  )
try {
    $parameters = $matcher->match('/fooss/%20');
    var_dump($parameters);
} catch (ResourceNotFoundException $e) {
    echo $e->getMessage().PHP_EOL;
} catch (MethodNotAllowedException $e) {
}


$sub = new RouteCollection();
