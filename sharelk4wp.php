<?php
/*
 * Plugin Name: Sharelock 4 WP
 * Plugin URI: http://www.sharelock.io/wordpress
 * Description: The plugin provides a widget to be installed in Worpress, 
 * showcasing the image feed for the site or a specific user. 
 * Version: 1.0
 * Author: Péter Nagy <peter@sharelock.io>
 * License: GPL2
*/
class Sharelk4Wp extends WP_Widget{
    private $_widgetId = 'sharelk4wp';
    private $_widgetName = 'Sharelock4Wp';
    private $_pluginBase = '';
    /**
     * Register widget with WordPress.
     */
    public function __construct() {
        $this->_pluginBase = basename(dirname(plugin_basename(__FILE__))).DIRECTORY_SEPARATOR;
        parent::__construct(
            $this->_widgetId, // Base ID
            $this->_widgetName, // Name
            array( 
                'description' => __( 'Sharelock for Wordpress', $this->_widgetId ),
                'classname' => $this->_widgetId, ) // Args
        );
        $style_url = WP_PLUGIN_URL . '/sharelock_wp/css/display.css';
        $style_file = WP_PLUGIN_DIR . '/sharelock_wp/css/display.css';
        if(file_exists($style_file)) {
            wp_register_style('sharelock-display-styles', $style_url);
        }
    }
    
    private function _loadJson($path)
    {
        $json = @file_get_contents('http://www.sharelock.io/'.$path.'.json',0,null,null);
        if ( $json == false )
            return null;
        return json_decode($json);
    }
    
    /**
     * Back-end widget form.
     *
     * @see WP_Widget::form()
     *
     * @param array $instance Previously saved values from database.
     */
    public function form( $instance ) {
        $instance = wp_parse_args(
            (array)$instance,
            array(
                'entryname' => null,
                'number' => 9
            )
        );
        $entryname = strip_tags( stripslashes( $instance[ 'entryname' ] ) );
        $number = strip_tags( stripslashes( $instance[ 'number' ] ) );
        ?>
        <p>
        <label for="<?php echo $this->get_field_id( 'entryname' ); ?>"><?php _e( 'Entry name:', $this->_widgetId ); ?></label> 
        <select class="widefat" id="<?php echo $this->get_field_id( 'entryname' ); ?>" name="<?php echo $this->get_field_name( 'entryname' ); ?>"  value="<?php echo esc_attr( $entryname ); ?>">
            <option value="all"><?php _e( 'All' , $this->_widgetId)?></option>
            <optgroup label="<?php _e('Users', $this->_widgetId) ?>">
            <?php
            if ($users = $this->_loadJson('users')) {
                foreach ( $users as $user ) {
                    echo "<option value='$user->username'";
                    if ( $entryname == $user->username ) echo ' selected="selected"';
                    $num = count($user->entries);
                    echo ">$user->username ($num)</option>";
                }
            } 
            ?></optgroup>
            <optgroup label="<?php _e('Channels', $this->_widgetId) ?>">
            <?php
            if ($channels = $this->_loadJson('channels')) {
                foreach ( $channels as $ch ) {
                    echo "<option value='channels/$ch->id'";
                    if ( $entryname == 'channels/'+$ch->id ) echo ' selected="selected"';
                    if (null != $ch->description) echo " title='$grp->description'";
                    $num = count($ch->entries);
                    echo ">$ch->name ($num)</option>";
                }
            } 
            ?></optgroup>
            <optgroup label="<?php _e('Groups', $this->_widgetId) ?>">
            <?php
            if ($groups = $this->_loadJson('groups')) {
                foreach ( $groups as $grp ) {
                    echo "<option value='groups/$grp->name'";
                    if ( $entryname == 'groups/'+$grp->name ) echo ' selected="selected"';
                    if (null != $grp->description) echo " title='$grp->description'";
                    $num = count($grp->entries);
                    echo ">$grp->name ($num)</option>";
                }
            } 
            ?></optgroup>
        </select>
        </p>
        <p><label for="<?php echo $this->get_field_id( 'number' ); ?>"><?php _e( 'Number of Posts to show(max. 24):', $this->_widgetId ); ?></label> 
        <input class="widefat" id="<?php echo $this->get_field_id( 'number' ); ?>" name="<?php echo $this->get_field_name( 'number' ); ?>" type="text" value="<?php echo esc_attr( $number ); ?>" />
        </p>
        <?php 
    }
    
    /**
     * Sanitize widget form values as they are saved.
     *
     * @see WP_Widget::update()
     *
     * @param array $new_instance Values just sent to be saved.
     * @param array $old_instance Previously saved values from database.
     *
     * @return array Updated safe values to be saved.
     */
    public function update( $new_instance, $old_instance ) {
        $instance = $old_instance;
        if ($instance['username']) {
            $instance['entryname'] = $instance['username'];
            
        }
        $instance['entryname'] = strip_tags( stripslashes( $new_instance['entryname'] ) );
        $instance['number'] = strip_tags( stripslashes( $new_instance['number'] ) );
        return $instance;
    }
    
    /**
     * Front-end display of widget.
     *
     * @see WP_Widget::widget()
     *
     * @param array $args     Widget arguments.
     * @param array $instance Saved values from database.
     */
    public function widget( $args, $instance ) {
        wp_enqueue_style('sharelock-display-styles');
        extract( $args );
        echo $before_widget;
        echo $before_title;
        ?>
        <div class="sharelk-title">
            <a href="http://www.sharelock.io" target="_blank">
                <img class="sharelk-logo" src="<?php echo $this->get_plugin_url() ?>images/sharelock.png"/>
            </a>
        </div>
        <?php 
        echo $after_title;
        if ( 'all' == $instance['entryname'] ) {
            $entries =  $this->_loadJson( 'feed' );
        } else {
            $respons = $this->_loadJson( $instance['entryname'] );
            $entries = $respons->entries;
        }
        if ( $entries !== null ) {
            ?><div class="sharelk-recent"><?php
            if ( 'all' != $instance['entryname'] ) {
                switch (strstr($instance['entryname'],'/',true)) {
                    case 'groups':
                        echo sprintf(__( '%s group\'s recent photos', $this->_widgetId ), $respons->name);
                        break;
                    case 'channels':
                        echo sprintf(__( '%s channel\'s recent photos', $this->_widgetId ), $respons->name);
                        break;
                    default:
                        echo sprintf(__( '%s\'s recent photos', $this->_widgetId ), $instance['entryname']);
                }
            } else {
                echo __( 'Recent photos', $this->_widgetId );
            }
            ?></div><?php
            array_splice( $entries, $instance['number'] );
            foreach ( $entries as $entry ) {
                ?><div class="sharelk-thumbnail">
                <a href="http://www.sharelock.io<?php echo $entry->post_link ?>" target="_blank">
                    <img src="<?php echo $entry->thumb_url ?>" title="<?php echo $entry->title ?>" />
                </a>
                </div><?php
            }
        } else {
            ?><div class="sharelk-recent"><?php
            echo sprintf(__( 'No recent photos yet', $this->_widgetId ), $instance['entryname']);
            ?></div><?php
        }
        echo $after_widget;
    }
    
    private function get_plugin_url() {
    // WP < 2.6
        if ( !function_exists('plugins_url') ) {
            return get_option('siteurl') . '/wp-content/plugins/' . $this->_pluginBase;
        } else { 
            return plugins_url($this->_pluginBase);
        }
    }
}

add_action('widgets_init', create_function('', 'register_widget("Sharelk4Wp");'));