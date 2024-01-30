<?php
/**
 *  buddytask Groups
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( class_exists( 'BP_Group_Extension' ) ) :

/**
 * The  buddytask group class
 *
 * @package  buddytask
 * @since 1.0.0
 */
class  BuddyTask_Group extends BP_Group_Extension {
    function __construct() {
        global $bp;

        $enabled = '0';
        if ( isset( $bp->groups->current_group->id ) ) {
            $enabled = groups_get_groupmeta( $bp->groups->current_group->id, 'buddytask_enabled', true );
        }

        $args = array(
            'name' => buddytask_get_name(),
            'slug' =>  buddytask_get_slug(),
            'nav_item_position' => 12,
            'enable_nav_item' =>  $enabled === '1'
        );
        parent::init( $args );
    }

    function create_screen( $group_id = null) {
        global $bp;

        if ( ! $group_id ) {
            $group_id = $bp->groups->current_group->id;
        }

        if ( !bp_is_group_creation_step( $this->slug ) )
            return false;

        wp_nonce_field( 'groups_create_save_' . $this->slug );

        $this->render_settings($group_id, true);
    }

    function create_screen_save( $group_id = null) {
        global $bp;

        if ( ! $group_id ) {
            $group_id = $bp->groups->current_group->id;
        }

        check_admin_referer( 'groups_create_save_' . $this->slug );

        $this->persist_settings($group_id);
    }

    function edit_screen( $group_id = null ) {
        global $bp;

        if ( !groups_is_user_admin( $bp->loggedin_user->id, $bp->groups->current_group->id ) && ! current_user_can( 'bp_moderate' ) ) {
            return false;
        }

        if ( !bp_is_group_admin_screen( $this->slug ) )
            return false;

        if (!$group_id){
            $group_id = $bp->groups->current_group->id;
        }

        wp_nonce_field( 'groups_edit_save_' . $this->slug );

        $this->render_settings($group_id, false);
        ?>

        <input type="submit" name="save" value="Save" />
        <?php
    }

    function edit_screen_save( $group_id = null ) {
        global $bp;

        $save = sanitize_text_field($_POST['save']);
        if ($save == null)
            return false;

        if ( !$group_id ) {
            $group_id = $bp->groups->current_group->id;
        }

        check_admin_referer( 'groups_edit_save_' . $this->slug );

        $this->persist_settings($group_id);

        bp_core_add_message( esc_html__( 'Settings saved successfully', 'buddytask' ) );

        bp_core_redirect( bp_get_group_permalink( $bp->groups->current_group ) . 'admin/' . $this->slug );
    }

    function display( $group_id = null ) {
      $enabled = groups_get_groupmeta( $group_id, 'buddytask_enabled', true );
      if ( $enabled == 1 ) {
          $this->get_groups_template_part( 'tasks/home' );
      }

      //   global $bp;

      //   if (!$group_id) {
      //       $group_id = $bp->groups->current_group->id;
      //   }

      //   if ( groups_is_user_member( $bp->loggedin_user->id, $group_id )
      //       || groups_is_user_mod( $bp->loggedin_user->id, $group_id )
      //       || groups_is_user_admin( $bp->loggedin_user->id, $group_id )
      //       || is_super_admin() ) {

      //       $enabled = groups_get_groupmeta( $group_id, 'buddytask_enabled', true );
      //       if ( $enabled == 1 ) {
      //           $this->get_groups_template_part( 'tasks/home' );
      //       }
      //   } else {
      //       echo '<div id="message" class="error"><p>'.esc_html__('This content is only available to group members.', 'buddytask').'</p></div>';
      //   }
    }
    function render_permission($group_id, $name, $defaultVal) {
         $val = groups_get_groupmeta( $group_id, $name, true );
         $val = empty($val) ? $defaultVal : $val;
         echo "<select name='$name' id='$name'>";
         echo "  <option value='ALL' ". ($val=='ALL' ? "selected":"").">ALL</option>";
         echo "  <option value='OWN' ". ($val=='OWN' ? "selected":"").">OWN</option>";
         echo "  <option value='NONE' ".($val=='NONE' ? "selected":"").">NONE</option>";
         echo "</select>";
    }
    function render_settings($group_id, $is_create){
        $defaults =  buddytask_default_settings();
        $enabled = $is_create ? $defaults['enabled'] : buddytask_is_enabled($group_id);

        ?>
        <div class="wrap">
            <h4><?php _e(  buddytask_get_name() . ' Settings', 'buddytask' ) ?></h4>

            <fieldset>
                <div class="field-group">
                    <div class="checkbox">
                        <label><input type="checkbox" name=" buddytask_enabled" value="1" <?php checked( (bool) $enabled )?>> <?php _e( 'Activate', 'buddytask' ); ?></label>
                    </div>
                </div>
            </fieldset>
            <fieldset>
               <legend>Permissions</legend>

               <table class="buddytask-permissions">
                  <tr>
                     <th class="role"></th>
                     <th class="col" data-bp-tooltip-pos="right" data-bp-tooltip="view service requests on the board">VIEW</th>
                     <th class="col" data-bp-tooltip-pos="right" data-bp-tooltip="edit requests in the 'Requested' column">EDIT Requested</th>
                     <th class="col" data-bp-tooltip-pos="right" data-bp-tooltip="edit any request">EDIT</th>
                     <th class="col" data-bp-tooltip-pos="right" data-bp-tooltip="delete requests in the 'Requested' column">DELETE Requested</th>
                     <th class="col" data-bp-tooltip-pos="right" data-bp-tooltip="delete any request">DELETE</th>
                  </tr>
                  <tr>
                     <td><?php echo esc_html_e( get_group_role_label( $group_id, 'organizer_plural_label_name' ), 'buddyboss' ); ?></td>
                     <td><?php $this->render_permission($group_id, "organizer_view","ALL"); ?></td>
                     <td><?php $this->render_permission($group_id, "organizer_edit_requested","ALL"); ?></td>
                     <td><?php $this->render_permission($group_id, "organizer_edit","ALL"); ?></td>
                     <td><?php $this->render_permission($group_id, "organizer_delete_requested","ALL"); ?></td>
                     <td><?php $this->render_permission($group_id, "organizer_delete","ALL"); ?></td>
                  </tr>
                  <tr>
                     <td><?php echo esc_html_e( get_group_role_label( $group_id, 'moderator_plural_label_name' ), 'buddyboss' ); ?></td>
                     <td><?php $this->render_permission($group_id, "moderator_view","ALL"); ?></td>
                     <td><?php $this->render_permission($group_id, "moderator_edit_requested","ALL"); ?></td>
                     <td><?php $this->render_permission($group_id, "moderator_edit","ALL"); ?></td>
                     <td><?php $this->render_permission($group_id, "moderator_delete_requested","ALL"); ?></td>
                     <td><?php $this->render_permission($group_id, "moderator_delete","ALL"); ?></td>
                  </tr>
                     <td><?php echo esc_html_e( get_group_role_label( $group_id, 'member_plural_label_name' ), 'buddyboss' ); ?></td>
                     <td><?php $this->render_permission($group_id, "member_view","ALL"); ?></td>
                     <td><?php $this->render_permission($group_id, "member_edit_requested","OWN"); ?></td>
                     <td><?php $this->render_permission($group_id, "member_edit","NONE"); ?></td>
                     <td><?php $this->render_permission($group_id, "member_delete_requested","OWN"); ?></td>
                     <td><?php $this->render_permission($group_id, "member_delete","NONE"); ?></td>
                  </tr>
                  </tr>
                     <td data-bp-tooltip-pos="right" data-bp-tooltip="non-group members but LG, ABWE team members or promoted external users">Authorized Non-Members</td>
                     <td><?php $this->render_permission($group_id, "visitor_view","ALL"); ?></td>
                     <td><?php $this->render_permission($group_id, "visitor_edit_requested","OWN"); ?></td>
                     <td><?php $this->render_permission($group_id, "visitor_edit","NONE"); ?></td>
                     <td><?php $this->render_permission($group_id, "visitor_delete_requested","OWN"); ?></td>
                     <td><?php $this->render_permission($group_id, "visitor_delete","NONE"); ?></td>
                  </tr>
                  </tr>
                     <td data-bp-tooltip-pos="right" data-bp-tooltip="Vistors to the group with minimum permissions (i.e. Interested Visitor user role)">Limited Vistors</td>
                     <td><?php $this->render_permission($group_id, "limited_visitor_view","ALL"); ?></td>
                     <td><?php $this->render_permission($group_id, "limited_visitor_edit_requested","NONE"); ?></td>
                     <td><?php $this->render_permission($group_id, "limited_visitor_edit","NONE"); ?></td>
                     <td><?php $this->render_permission($group_id, "limited_visitor_delete_requested","NONE"); ?></td>
                     <td><?php $this->render_permission($group_id, "limited_visitor_delete","NONE"); ?></td>
                  </tr>
               </table>
               <p><i>NOTES: (1) adding to the board should typically only be done through the Request Service form but 
                  <?php echo esc_html_e( get_group_role_label( $group_id, 'organizer_plural_label_name' ), 'buddyboss' ); ?> and 
                  <?php echo esc_html_e( get_group_role_label( $group_id, 'moderator_plural_label_name' ), 'buddyboss' ); ?>
               can insert projects directly  (2) only group admin level users are allowed to change the name of the columns</i></p>
            </fieldset>
        </div>
        <?php
    }

    function persist_settings($group_id){
        buddytask_groups_update_groupmeta($group_id, 'buddytask_enabled', "0");

        buddytask_groups_update_groupmeta($group_id, "organizer_view","ALL");
        buddytask_groups_update_groupmeta($group_id, "organizer_edit_requested","ALL");
        buddytask_groups_update_groupmeta($group_id, "organizer_edit","ALL");
        buddytask_groups_update_groupmeta($group_id, "organizer_delete_requested","ALL");
        buddytask_groups_update_groupmeta($group_id, "organizer_delete","ALL");
        buddytask_groups_update_groupmeta($group_id, "moderator_view","ALL");
        buddytask_groups_update_groupmeta($group_id, "moderator_edit_requested","ALL");
        buddytask_groups_update_groupmeta($group_id, "moderator_edit","ALL");
        buddytask_groups_update_groupmeta($group_id, "moderator_delete_requested","ALL");
        buddytask_groups_update_groupmeta($group_id, "moderator_delete","ALL");
        buddytask_groups_update_groupmeta($group_id, "member_view","ALL");
        buddytask_groups_update_groupmeta($group_id, "member_edit_requested","OWN");
        buddytask_groups_update_groupmeta($group_id, "member_edit","NONE");
        buddytask_groups_update_groupmeta($group_id, "member_delete_requested","OWN");
        buddytask_groups_update_groupmeta($group_id, "member_delete","NONE");
        buddytask_groups_update_groupmeta($group_id, "visitor_view","ALL");
        buddytask_groups_update_groupmeta($group_id, "visitor_edit_requested","OWN");
        buddytask_groups_update_groupmeta($group_id, "visitor_edit","NONE");
        buddytask_groups_update_groupmeta($group_id, "visitor_delete_requested","OWN");
        buddytask_groups_update_groupmeta($group_id, "visitor_delete","NONE");
        buddytask_groups_update_groupmeta($group_id, "limited_visitor_view","ALL");
        buddytask_groups_update_groupmeta($group_id, "limited_visitor_edit_requested","NONE");
        buddytask_groups_update_groupmeta($group_id, "limited_visitor_edit","NONE");
        buddytask_groups_update_groupmeta($group_id, "limited_visitor_delete_requested","NONE");
        buddytask_groups_update_groupmeta($group_id, "limited_visitor_delete","NONE");
    }

    function get_groups_template_part( $slug ) {
        add_filter( 'bp_locate_template_and_load', '__return_true'                        );
        add_filter( 'bp_get_template_stack', array($this, 'set_template_stack'), 10, 1 );

        bp_get_template_part( 'groups/single/' . $slug );

        remove_filter( 'bp_locate_template_and_load', '__return_true' );
        remove_filter( 'bp_get_template_stack', array($this, 'set_template_stack'), 10);
    }

    function set_template_stack( $stack = array() ) {
        if ( empty( $stack ) ) {
            $stack = array(  buddytask_get_plugin_dir() . 'templates' );
        } else {
            $stack[] =  buddytask_get_plugin_dir() . 'templates';
        }

        return $stack;
    }
}

/**
 * Waits for bp_init hook before loading the group extension
 *
 * Let's make sure the group id is defined before loading our stuff
 *
 * @since 1.0.0
 *
 * @uses bp_register_group_extension() to register the group extension
 */
function  buddytask_register_group_extension() {
    bp_register_group_extension( 'buddytask_Group' );
}

add_action( 'bp_init', 'buddytask_register_group_extension' );

endif;
