<?php
namespace CmThizer\Plugins;

use function Composer\Autoload\includeFile;

class LoadPlugins
{
  private $cmThizerInstance;
  private $plugins = array();
  
  public function __construct(string $pluginsPath, \CmThizer $cmThizerInstance) {
    
    $this->cmThizerInstance = $cmThizerInstance;
    
    $pluginsDir = scandir_recursive($pluginsPath, true);
    
    foreach ($pluginsDir as $value) {
      if (is_array($value)) {
        foreach ($value as $subValue) {
          if (is_file($subValue) && is_readable($subValue) && (pathinfo($subValue, PATHINFO_EXTENSION) == 'php')) {
            
            $this->append($subValue);
          }
        }
      } else if (is_file($value) && is_readable($value) && (pathinfo($value, PATHINFO_EXTENSION) == 'php')) {
        
        $this->append($value);
      }
      
    } // Endforeach
  }
  
  private function append($filename): LoadPlugins {
    
    require_once $filename;
    $className = basename($filename, '.php');
    
    $classInstance = new $className();
    if ($classInstance instanceof AbstractPlugin) {
      $this->plugins[] = $classInstance;
    }
    return $this;
  }
  
  public function dispatch(int $type): void {
    foreach($this->plugins as $plugin) {
      
      $plugin->setCmThizerInstance($this->cmThizerInstance);
      
      switch ($type) {
        case AbstractPlugin::PRE_URI:
          $plugin->preUri();
          break;
        case AbstractPlugin::POS_URI:
          $plugin->posUri();
          break;
        case AbstractPlugin::PRE_PARAMS:
          $plugin->preParams();
          break;
        case AbstractPlugin::POS_PARAMS:
          $plugin->posParams();
          break;
        case AbstractPlugin::PRE_POST:
          $plugin->prePost();
          break;
        case AbstractPlugin::POS_POST:
          $plugin->posPost();
          break;
        case AbstractPlugin::PRE_ROUTES:
          $plugin->preRoutes();
          break;
        case AbstractPlugin::POS_ROUTES:
          $plugin->posRoutes();
          break;
        case AbstractPlugin::PRE_RUN:
          $plugin->preRun();
          break;
        case AbstractPlugin::POS_RUN:
          $plugin->posRun();
          break;
        default:
          throw new \ErrorException("Unknown plugin dispatch type ($type)");
      }
      
    } // Endforeach 
  }
}
