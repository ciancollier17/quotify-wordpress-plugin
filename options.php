<?php
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

      $categories_file = fopen(plugins_url("test-wp-plugin/categories.dat"), "r") or die("ERROR: Could Not Open Quotify Categories.dat File!");

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
?>
