<?php

if (!function_exists('URL_APP')) {
  function URL_APP()
  {
      global $appUrl;
  }
}
if (!function_exists('redirect')) {
  // Chuyển hướng đến một trang khác
  function redirect($location, array $data = [])
  {
    foreach ($data as $key => $value) {
      $_SESSION[$key] = $value;
    }

    header('Location: ' . $location, true, 302);
    exit();
  }
}

if (!function_exists('session_get_once')) {
  // Đọc và xóa một biến trong $_SESSION
  function session_get_once($name, $default = null)
  {
    $value = $default;
    if (isset($_SESSION[$name])) {
      $value = $_SESSION[$name];
      unset($_SESSION[$name]);
    }
    return $value;
  }
}

if (!function_exists('PDO')) {
  function PDO(): PDO
  {
    global $PDO;
    return $PDO;
  }
}

function url_with_params($param, $value)
{
    $url = parse_url($_SERVER['REQUEST_URI']);
    $query = [];
    parse_str($url['query'] ?? '', $query);
    $query[$param] = $value;
    return $url['path'] . '?' . http_build_query($query);
}

if (!function_exists('AUTHGUARD')) {
  
  function AUTHGUARD(): App\SessionGuard
  {
    global $AUTHGUARD;
    return $AUTHGUARD;
  }
}

if (!function_exists('dd')) {
  function dd($var)
  {
    var_dump($var);
    exit();
  }
}





