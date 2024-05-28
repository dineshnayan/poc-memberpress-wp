<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class PluginDatabase {

    private $table_name;

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'ongraph_mpress';
        $this->table_name_user = $wpdb->prefix . 'mpress_active_services';
    }

    // Display table content
    public function display_table_content() {
        global $wpdb;
        $results = $wpdb->get_results( "SELECT * FROM $this->table_name" );

        if ( $results ) {
            foreach ( $results as $row ) {
                echo "<tr>";
                echo "<td>{$row->id}</td>";
                echo "<td>{$row->name}</td>";
                echo "<td>{$row->value}</td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='3'>No data found</td></tr>";
        }
    }
}
?>
