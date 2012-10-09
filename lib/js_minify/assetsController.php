<?php

/**
 * Description of test
 *
 * @author chugas
 */
class assetsController {

  public static function usingSecureMode() {
    if (isset($_SERVER['HTTPS']))
      return ($_SERVER['HTTPS'] == 1 || strtolower($_SERVER['HTTPS']) == 'on');
    // $_SERVER['SSL'] exists only in some specific configuration
    if (isset($_SERVER['SSL']))
      return ($_SERVER['SSL'] == 1 || strtolower($_SERVER['SSL']) == 'on');

    return false;
  }

  /**
   * Get the current url prefix protocol (https/http)
   *
   * @return string protocol
   */
  public static function getCurrentUrlProtocolPrefix() {
    if (self::usingSecureMode())
      return 'https://';
    else
      return 'http://';
  }
  
  public static function str_replace_once($needle, $replace, $haystack) {
    $pos = strpos($haystack, $needle);
    if ($pos === false)
      return $haystack;
    return substr_replace($haystack, $replace, $pos, strlen($needle));
  }  

  /**
   * Combine Compress and Cache CSS (ccc) calls
   *
   */
  public static function cccCss($css_files, $media = 'all') {
    $sf_cache_dir   = sfConfig::get('sf_cache_dir');
    $sf_web_dir     = sfConfig::get('sf_web_dir');
    
    //inits
    $css_files_by_media = array();
    $compressed_css_files = array();
    $compressed_css_files_not_found = array();
    $compressed_css_files_infos = array();
    $protocolLink = self::getCurrentUrlProtocolPrefix();

    // group css files by media
    foreach ($css_files as $filename => $media) {
      if (!array_key_exists($media, $css_files_by_media))
        $css_files_by_media[$media] = array();

      $infos = array();
      $infos['uri'] = $filename;
      $url_data = parse_url($filename);
      
      //$infos['path'] = _PS_ROOT_DIR_ . self::str_replace_once(__PS_BASE_URI__, '/', $url_data['path']);
      $infos['path'] = $sf_web_dir . '/' . $url_data['path'];
      
      $css_files_by_media[$media]['files'][] = $infos;
      if (!array_key_exists('date', $css_files_by_media[$media]))
        $css_files_by_media[$media]['date'] = 0;
      $css_files_by_media[$media]['date'] = max(
        file_exists($infos['path']) ? filemtime($infos['path']) : 0, $css_files_by_media[$media]['date']
      );

      if (!array_key_exists($media, $compressed_css_files_infos))
        $compressed_css_files_infos[$media] = array('key' => '');
      $compressed_css_files_infos[$media]['key'] .= $filename;
    }

    // get compressed css file infos
    foreach ($compressed_css_files_infos as $media => &$info) {
      $key = md5($info['key'] . $protocolLink);
      
      $filename = $sf_web_dir . '/cache/css/' . $key . '_' . $media . '.css';
      
      $info = array(
        'key' => $key,
        'date' => file_exists($filename) ? filemtime($filename) : 0
      );
    }

    // aggregate and compress css files content, write new caches files
    foreach ($css_files_by_media as $media => $media_infos) {
      $cache_filename = $sf_web_dir . '/cache/css/' . $compressed_css_files_infos[$media]['key'] . '_' . $media . '.css';
      if ($media_infos['date'] > $compressed_css_files_infos[$media]['date']) {
        $compressed_css_files[$media] = '';
        foreach ($media_infos['files'] as $file_infos) {
          if (file_exists($file_infos['path']))
            $compressed_css_files[$media] .= self::minifyCSS(file_get_contents($file_infos['path']), $file_infos['uri']);
          else
            $compressed_css_files_not_found[] = $file_infos['path'];
        }
        if (!empty($compressed_css_files_not_found))
          $content = '/* WARNING ! file(s) not found : "' .
            implode(',', $compressed_css_files_not_found) .
            '" */' . "\n" . $compressed_css_files[$media];
        else
          $content = $compressed_css_files[$media];
        file_put_contents($cache_filename, $content);
        chmod($cache_filename, 0777);
      }
      $compressed_css_files[$media] = $cache_filename;
    }

    return self::str_replace_once($sf_web_dir, '', $compressed_css_files[$media]);
  }

  /**
   * Combine Compress and Cache (ccc) JS calls
   */
  public static function cccJS($js_files, $add_use_js=false, $position = null) {

    if(sfConfig::get('app_assetsController_debug', false) and $add_use_js){
      foreach($js_files as $file)
        use_javascript('../' . $file, $position);
      return true;
    }

    $sf_cache_dir   = sfConfig::get('sf_cache_dir');
    $sf_web_dir     = sfConfig::get('sf_web_dir');
    
    //inits
    $compressed_js_files_not_found = array();
    $js_files_infos = array();
    $js_files_date = 0;
    $compressed_js_file_date = 0;
    $compressed_js_filename = '';
    $js_external_files = array();
    $protocolLink = self::getCurrentUrlProtocolPrefix();

    // get js files infos
    foreach ($js_files as $filename) {
      $expr = explode(':', $filename);

      if ($expr[0] == 'http')
        $js_external_files[] = $filename;
      else {
        $infos = array();
        $infos['uri'] = $filename;
        $url_data = parse_url($filename);
        
        //$infos['path'] = $sf_web_dir . self::str_replace_once(__PS_BASE_URI__, '/', $url_data['path']);
        $infos['path'] = $sf_web_dir . '/' . $url_data['path'];
        
        $js_files_infos[] = $infos;

        $js_files_date = max(
          file_exists($infos['path']) ? filemtime($infos['path']) : 0, $js_files_date
        );
        $compressed_js_filename .= $filename;
      }
    }

    // get compressed js file infos
    $compressed_js_filename = md5($compressed_js_filename);
    
    $returnfilename = '/cache/js/' . $compressed_js_filename . '.js';
    
    $compressed_js_path = $sf_web_dir . $returnfilename;
    $compressed_js_file_date = file_exists($compressed_js_path) ? filemtime($compressed_js_path) : 0;

    // aggregate and compress js files content, write new caches files
    if ($js_files_date > $compressed_js_file_date) {
      $content = '';
      foreach ($js_files_infos as $file_infos) {
        if (file_exists($file_infos['path']))
          $content .= file_get_contents($file_infos['path']) . ';';
        else
          $compressed_js_files_not_found[] = $file_infos['path'];
      }
      $content = self::minifyJS($content);

      if (!empty($compressed_js_files_not_found))
        $content = '/* WARNING ! file(s) not found : "' .
          implode(',', $compressed_js_files_not_found) .
          '" */' . "\n" . $content;

      file_put_contents($compressed_js_path, $content);
      chmod($compressed_js_path, 0777);
    }
    if($add_use_js === true){
      use_javascript($returnfilename, $position);
    }
    return $returnfilename;
  }

  public static function minifyJS($js_content) {
    if (strlen($js_content) > 0) {
      return JSMin::minify($js_content);
    }
    return false;
  }

  public static function minifyCSS($css_content, $uri = false) {
    if (strlen($css_content) > 0) {
      $css_content = preg_replace('#/\*.*?\*/#s', '', $css_content);
      //$css_content = preg_replace_callback('#url\((?:\'|")?([^\)\'"]*)(?:\'|")?\)#s', array('Tools', 'replaceByAbsoluteURL'), $css_content);

      $css_content = preg_replace('#\s+#', ' ', $css_content);
      $css_content = str_replace("\t", '', $css_content);
      $css_content = str_replace("\n", '', $css_content);
      //$css_content = str_replace('}', "}\n", $css_content);

      $css_content = str_replace('; ', ';', $css_content);
      $css_content = str_replace(': ', ':', $css_content);
      $css_content = str_replace(' {', '{', $css_content);
      $css_content = str_replace('{ ', '{', $css_content);
      $css_content = str_replace(', ', ',', $css_content);
      $css_content = str_replace('} ', '}', $css_content);
      $css_content = str_replace(' }', '}', $css_content);
      $css_content = str_replace(';}', '}', $css_content);
      $css_content = str_replace(':0px', ':0', $css_content);
      $css_content = str_replace(' 0px', ' 0', $css_content);
      $css_content = str_replace(':0em', ':0', $css_content);
      $css_content = str_replace(' 0em', ' 0', $css_content);
      $css_content = str_replace(':0pt', ':0', $css_content);
      $css_content = str_replace(' 0pt', ' 0', $css_content);
      $css_content = str_replace(':0%', ':0', $css_content);
      $css_content = str_replace(' 0%', ' 0', $css_content);

      return trim($css_content);
    }
    return false;
  }

  /*public static function replaceByAbsoluteURL($matches) {
    global $current_css_file;

    $protocol_link = self::getCurrentUrlProtocolPrefix();

    if (array_key_exists(1, $matches)) {
      $tmp = dirname($current_css_file) . '/' . $matches[1];
      return 'url(\'' . $protocol_link . self::getMediaServer($tmp) . $tmp . '\')';
    }
    return false;
  }

  public static function getMediaServer($filename) {
    if (self::$_cache_nb_media_servers === null) {
      if (_MEDIA_SERVER_1_ == '')
        self::$_cache_nb_media_servers = 0;
      elseif (_MEDIA_SERVER_2_ == '')
        self::$_cache_nb_media_servers = 1;
      elseif (_MEDIA_SERVER_3_ == '')
        self::$_cache_nb_media_servers = 2;
      else
        self::$_cache_nb_media_servers = 3;
    }

    if (self::$_cache_nb_media_servers AND ($id_media_server = (abs(crc32($filename)) % self::$_cache_nb_media_servers + 1)))
      return constant('_MEDIA_SERVER_' . $id_media_server . '_');
    return self::getHttpHost();
  }*/

}

?>
