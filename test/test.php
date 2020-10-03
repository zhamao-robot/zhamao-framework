<?php

require __DIR__ . "/../vendor/autoload.php";

use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;

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

$route = new Route('/');

$routes->add('root', $route);

$context = new RequestContext();

$matcher = new UrlMatcher($routes, $context);

//$parameters = $matcher->match('/test/foo');var_dump($parameters);

$parameters = $matcher->match('/archive/2012-01');
var_dump($parameters);
// array(
//     'controller' => 'showArchive',
//     'month' => '2012-01',
//     'subdomain' => 'www',
//     '_route' => ...
//  )

$parameters = $matcher->match('/');
var_dump($parameters);

$sub = new RouteCollection();
