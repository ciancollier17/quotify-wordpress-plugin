<?php
/*
  Plugin Name: Quotify
  Author: Cian Collier
  Description: Displays random quotes on a topic of your choice at the top of each post.
  Version: 1.0
*/

// Include Simple HTML DOM Library (http://simplehtmldom.sourceforge.io) for parsing of BrainyQuote HTML
include_once("includes/simple_html_dom.php");
include_once("options.php");

class Quotify {
  function __construct () {
    add_action('wp_enqueue_scripts', array($this, 'register_plugin_styles'));
    add_filter('the_content', array($this, 'add_quote'));
  }

  function register_plugin_styles () {
    wp_register_style('quotify', plugins_url('quotify/styles/main.css'));
    wp_enqueue_style('quotify');
  }

  function get_quotes_search($query) {
    $split_query = explode(' ', $query);
    $url = "https://www.brainyquote.com/search_results?q=" . join('-', $split_query);

    $html = file_get_html($url);
    return $this->parse_quotes($html);
  }

  function get_quotes_category($category) {
    $url = "https://www.brainyquote.com/topics/" . $category . "-quotes";

    $html = file_get_html($url);
    return $this->parse_quotes($html);
  }

  function parse_quotes($html) {
    $quotes_array = array();
    $quote_number = 0;

    foreach ($html->find("a") as $link) {
      if ($link->title == "view quote") {
        array_push($quotes_array, array($link->innertext));
      } else if ($link->title == "view author") {
        array_push($quotes_array[$quote_number], $link->innertext);
        $quote_number++;
      }
    }

    return $quotes_array;
  }

  function generate_quote() {
    $query = "default";
    $option = get_option('quotify_settings_menu');
    $quotes = [];
    $cat_or_search = "category";
    $category = "love";

    if ($option['cat_or_search']) {
      $cat_or_search = $option['cat_or_search'];
    }

    if ($cat_or_search == "search") {
      if ($option['search']) {
        $query = $option['search'];
      }

      $quotes = $this->get_quotes_search($query);
    } else {
      if ($option['category']) {
        $category = $option['category'];
      }

      $quotes = $this->get_quotes_category($category);
    }

    $random = Rand(0, count($quotes) - 1);
    return $quotes[$random];
  }

  function add_quote ($content) {
    if (in_the_loop() && is_main_query()) {
      $selected_quote = $this->generate_quote();
      return "<p class='quotify-quote'><strong>" . $selected_quote[0] . "</strong><br /><i>" . $selected_quote[1] . "</i></p>" . $content;
    }

    return $content;
  }
}

$quotify_instance = new Quotify;
?>
