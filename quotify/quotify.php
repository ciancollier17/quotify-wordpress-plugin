<?php
/*
  Plugin Name: Quotify
  Author: Cian Collier
  Description: Displays random quotes on a topic of your choice at the top of each post.
  Version: 1.0
*/

// Include Simple HTML DOM Library (http://simplehtmldom.sourceforge.io) for parsing of BrainyQuote HTML
include_once("includes/simple_html_dom.php");

class QuotifyOptions {
  function __construct () {
    add_action("admin_menu", array($this, "add_quotify_menu"));
    add_action("admin_init", array($this, "register_quotify_settings"));
  }

  function create_quotify_menu () {
    if(!current_user_can('manage_options')) {
      wp_die(__("You Do Not Have Sufficient Permissions To Access This Page!"));
    }

    echo "<div class='wrap'>";
    echo "<h1>Quotify Options</h1>";
    echo "<form method='post' action='options.php'>";
    settings_fields("quotify_settings_menu");
    do_settings_sections("quotify_settings_menu");
    submit_button();
    echo "</form>";
    echo "</div>";
  }

  function add_quotify_menu () {
    add_options_page("Quotify Options", "Quotify", "manage_options", "quotify_settings_menu", array($this, "create_quotify_menu"));
  }

  function register_quotify_settings () {
    register_setting("quotify_settings_menu", "quotify_settings_menu", array($this, "sanitise"));
    add_settings_section("quotify-main-settings", "Main Settings", array($this, "main_settings_html"), 'quotify_settings_menu');
    add_settings_field("quotify-cat-or-search", "Use Category Or Search Query?", array($this, "cat_or_search_setting"), "quotify_settings_menu", "quotify-main-settings");
    add_settings_field("quotify-category", "Quote Category", array($this, "category_setting"), "quotify_settings_menu", "quotify-main-settings");
    add_settings_field("quotify-search", "Quote Search Query", array($this, "search_setting"), "quotify_settings_menu", "quotify-main-settings");
  }

  function cat_or_search_setting () {
    $selected = "";

    if (get_option('quotify_settings_menu')['cat_or_search']) {
      $selected = get_option('quotify_settings_menu')['cat_or_search'];
    }

    echo "<input type='radio' name='quotify_settings_menu[cat_or_search]' value='category'";
    if ($selected == "category" || $selected == "") {
      echo " checked";
    }
    echo "> Category";
    echo "<input type='radio' name='quotify_settings_menu[cat_or_search]' value='search' style='margin-left: 20px;'";
    if ($selected == "search") {
      echo " checked";
    }
    echo "> Search";
  }

  function category_setting () {
    $current_value = "";
    $categories = ['age', 'alone', 'amazing', 'anger', 'anniversary', 'architecture', 'finance', 'food', 'travel'];

    if (get_option('quotify_settings_menu')['category']) {
      $current_value = get_option('quotify_settings_menu')['category'];
    }

    echo "<select name='quotify_settings_menu[category]'>";

    $categories_file = fopen(plugins_url("quotify/categories.dat"), "r") or die("ERROR: Could Not Open Quotify Categories.dat File!");

    while (!feof($categories_file)) {
      $category = fgets($categories_file);

      // Remove Whitespace
      $category = preg_replace('/\s+/', '', $category);

      echo "<option value='" . $category . "'";

      if ($current_value == $category) {
        echo " selected='selected'";
      }

      echo ">" . ucwords($category) . "</option>";
    }

    fclose($categories_file);

    echo "</select>";
  }

  function search_setting () {
    $current_value = "";

    if (get_option('quotify_settings_menu')['search']) {
      $current_value = get_option('quotify_settings_menu')['search'];
    }

    echo "<input id='quotify-test' name='quotify_settings_menu[search]' type='text' value='{$current_value}' />";
  }

  function main_settings_html () {
    echo "This is the main settings section.";
  }

  function sanitise ($input) {
    return $input;
  }
}

$quotify_options_instance = new QuotifyOptions;


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
