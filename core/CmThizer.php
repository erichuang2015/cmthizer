<?php

use CmThizer\Plugins\AbstractPlugin;
use CmThizer\Plugins\LoadPlugins;
use CmThizer\Uri;

class CmThizer {
  
  private $template = 'template.phtml';
  
  private $landingPage = 'landing-page.phtml';
  
  private $sitePath = './site/';
  
  private $pluginsPath = './plugins/';
  
  private $plugins;
  
  private $uri;
  
  private $params = array();
  
  private $post = array();
  
  private $routes = array();
  
  public function __construct() {
    try {
      $this->plugins = new LoadPlugins($this->pluginsPath);
      
      $this->plugins->dispatch(AbstractPlugin::PRE_URI);
      $this->uri = new Uri();
      $this->plugins->dispatch(AbstractPlugin::POS_URI);
      
      // Resolver configuracoes
      $this->resolveParams();
      $this->resolvePost();
      $this->resolveRoutes();
      
    } catch (Exception $ex) {
      dump($ex);
    } catch (Error $err) {
      dump($err);
    }
  }
  
  public function run(): CmThizer {
    try {
      // Check if route exists
      if (!isset($this->routes[$this->uri->getRoute()])) {
        throw new Exception("404 - Page not found", 404);
      }
      
      // Variables to be appended to the view
      $route = $this->routes[$this->uri->getRoute()];
      $configs = $route['configs'];
      
      // Caminho base
      // (essa concatenacao sem sentido existe apenas para desmarcar var nao utilizada)
      $basePath = '';
      $basePath .= $this->uri->getBasePath();
      
      // Load content
      $content = "";
      if ($route['content'] && file_exists($route['content'])) {
        
        // Allowed to read file?
        if (is_readable($route['content'])) {
          $parseDown = new Parsedown();
          $content = $parseDown->parse(file_get_contents($route['content']));
          
        } else {
          throw new Exception("Markdown content file ({$route['content']}) is not readable");
        }
      }
      
      // Including here, all these variables defined above
      // are accessible on the view
      include $this->sitePath.$configs['template'];
      
    } catch (Exception $ex) {
      dump($ex);
    }
    return $this;
  }
  
  public function getUrl(string $link = ''): string {
    return getenv('REQUEST_SCHEME').'://'.getenv('HTTP_HOST').$this->uri->getBasePath().'/'. trim($link, '/');
  }
  
  public function getBaseUrl(string $link = ''): string {
    return $this->getUrl($link);
  }
  
  public function url(string $link = ''): string {
    return $this->getUrl($link);
  }
  
  public function setTemplate(string $name): CmThizer {
    $this->template = $name.'.phtml';
    return $this;
  }
  
  public function getTemplate(): string {
    return $this->template;
  }
  
  public function setLandingPage(string $name): CmThizer {
    $this->landingPage = $name.'.phtml';
    return $this;
  }
  
  public function getLandingPage(): string {
    return $this->landingPage;
  }
  
  public function setSitePath(string $foldername): CmThizer {
    $this->sitePath = $foldername;
    return $this;
  }
  
  public function getUri(): Uri {
    return $this->uri;
  }
  
  private function resolveParams(): CmThizer {
    $this->params = (array) filter_input_array(INPUT_GET);
    return $this;
  }
  
  private function resolvePost(): CmThizer {
    $this->post = (array) filter_input_array(INPUT_POST);
    return $this;
  }
  
  private function resolveRoutes(): CmThizer {
    
    $dirItems = $this->scandirRecursive($this->sitePath);
    
    /**
     * Outra recursiva, agora para organizar os dados da pagina
     * 
     * ## RECURSIVA ##
     */
    function resolve(array $items): array {
      $routes = array();
      
      $defaultValues = array(
        'title' => 'My website',
        'uri' => '/',
        'template' => 'template.phtml'
      );
      
      foreach ($items as $folder => $content) {
        if (is_dir($folder) && in_array('config.json', $content) && in_array('content.md', $content)) {
          
          // Get configs from config.json file
          $config['configs'] = array_merge(
            $defaultValues,
            json_decode(file_get_contents($folder.'/config.json'), true)
          );
          
          $config['content'] = $folder.'/content.md';
          
          $uri = '/'.ltrim($config['configs']['uri'], '/');
          
          $routes[$uri] = $config;
        } else if(is_array($content)) {
          $routes += resolve($content);
        }
      }
      return $routes;
    }
    $this->routes = resolve($dirItems);
    
    // If was not created a home landing page, we do it
    if (!isset($this->routes['/'])) {
      $this->routes['/'] = array(
          'configs' => array(
            'title' => 'My website',
            'uri' => '/',
            'template' => 'landing-page.phtml'
          ),
          'content' => ''
      );
    }
    
    return $this;
  }
  
  /**
   * Nos da a lista de conteudo da pasta site
   * 
   * ## RECURSIVA ##
   */
  private function scandirRecursive(string $dirname): array {
    $items = array();
    if (is_dir($dirname)) {
      foreach (scandir($dirname) as $item) {
        if (!in_array($item, array('.', '..'))) {
          if (is_dir($dirname.DIRECTORY_SEPARATOR.$item)) {
            $items[$dirname.DIRECTORY_SEPARATOR.$item] = $this->scandirRecursive($dirname.DIRECTORY_SEPARATOR.$item);
          } else {
            $items[] = $item;
          }
        }
      }
    }
    return $items;
  }
}

