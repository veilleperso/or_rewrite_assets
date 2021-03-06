<?php

function or_hashed_url($new, $url) {
  ob_end_flush();
  if (stripos($new, "%d")>0) {
    $hash = crc32($url) % 4;
    return sprintf($new, $hash);
  }
  else return $new;
}

function or_ra_redirections() {
  $redirections = array();
  for ($i=1; $i<=OR_RA_REDIRECT_NUMBER; $i+=1) {
    $key = trim(get_option("ra_url${i}"));
    $key = preg_replace("|([/]+)$|", "", $key);
    if (!empty($key)) {
      $value = trim(get_option("ra_redirect${i}"));
      if (!empty($value)) {
        $redirections[$key] = $value;
        $local_url = preg_replace("|(https?://[^/]+)|i", "", $key);

        // we replace local url
        if (!empty($local_url) && preg_match("|^https?:|i", $key)) $redirections[$local_url] = $value;
        
        // and full url
        if (!empty($local_url) && $local_url[0] != '.') {
          $full_url = get_bloginfo("url"). "/".preg_replace("|^[/]+|", "", $local_url);
          if ($full_url != $key) $redirections[$full_url] = $value;
        }
      }
    }
  }
  return $redirections;
}

function or_ra_convert_internal_link($matches) {
  global $or_ra_redirections;
  foreach ($or_ra_redirections as $key => $value) {
    if (stripos($matches[2], $key) === 0) {
      return str_replace($key, or_hashed_url($value, $matches[2]), $matches[0]);
    }
  }
  return $matches[0];
}

function or_ra_url($url) {
  global $or_ra_redirections;
  if (!is_array($or_ra_redirections)) $or_ra_redirections = or_ra_redirections();
  if (count($or_ra_redirections)>0) {
    foreach ($or_ra_redirections as $key => $value) {
      if (stripos($url, $key) === 0) {
        return str_replace($key, or_hashed_url($value, $url), $url);
      }
    }
  }
  return $url;
}

function or_ra_content($text) {
  global $or_ra_redirections;
  if (!is_array($or_ra_redirections)) $or_ra_redirections = or_ra_redirections();
  if (count($or_ra_redirections)>0) {
    $pattern = '/(href|src)=[\"\']([^\"\']+)[\"\']/im';
    return preg_replace_callback($pattern, 'or_ra_convert_internal_link', $text);
  }
  else return $text;
}

add_filter('the_content', 'or_ra_content', 100);
add_filter('the_excerpt', 'or_ra_content', 100);
add_filter('or_ra_rewrite_url', 'or_ra_url');
?>