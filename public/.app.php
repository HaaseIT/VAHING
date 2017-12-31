<?php

require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'autoload.php';

$VAHI = new \HaaseIT\VAHI\VAHI(__DIR__);
$VAHI->init();

$serviceManager = $VAHI->getServiceManager();

$pageData = $VAHI->gatherPageData();

$response = new \Zend\Diactoros\Response();

$response->getBody()->write($serviceManager->get('twig')->render('base.twig', $pageData));

$emitter = new \Zend\Diactoros\Response\SapiEmitter();
$emitter->emit($response);
