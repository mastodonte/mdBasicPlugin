<?php 

$initial_app = null;
$initial_config = null;
$context = null;

function switchApp($appname, $env = null, $debug = null){
  GLOBAL $context;
  GLOBAL $initial_app;
  GLOBAL $initial_config;

  $initial_app = sfContext::getInstance()->getConfiguration()->getApplication();
  $initial_web_controler = basename(sfContext::getInstance()->getRequest()->getScriptName());
  $initial_config = sfConfig::getAll();
  
  // get the environment
  if (is_null($env))
  {
    $env = sfContext::getInstance()->getConfiguration()->getEnvironment();
  }

  // context creation
  if (!sfContext::hasInstance($appname))
  {
      $context = sfContext::createInstance(ProjectConfiguration::getApplicationConfiguration($appname, $env, $debug), $appname);
  }
  else
  {
    $context = sfContext::getInstance($appname);
  }

  return true;
}

function switchBack(){
  GLOBAL $context;
  GLOBAL $initial_app;
  GLOBAL $initial_config;

  if($initial_app === null || $initial_config === null || $context === null)
    throw new Exception("Use SwitchBack function without using switchApp before", 1);
    
  sfContext::switchTo($initial_app);
  sfConfig::add($initial_config);

  $context = null;
  $initial_app = null;
  $initial_config = null;

}