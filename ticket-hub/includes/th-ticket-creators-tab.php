<?php

function get_th_ticket_creators()
{
    $args = array(
        'role'    => 'th_ticket_creator',
        'orderby' => 'user_nicename',
        'order'   => 'ASC'
    );
    $user_query = new WP_User_Query($args);
    return $user_query->get_results();
}

function th_ticket_creator_form_page()
{
    // Check if the current user has the capability to create users
    if (!current_user_can('create_users')) {
        wp_die(esc_html__('You do not have permission to access this page.', 'tickethub'));
    }

    // Handle form submission for single user creation
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_user_nonce']) && wp_verify_nonce($_POST['create_user_nonce'], 'create_th_ticket_creator')) {
        $first_name = sanitize_text_field($_POST['first_name']);
        $last_name = sanitize_text_field($_POST['last_name']);
        $email = sanitize_email($_POST['email']);
        $username = sanitize_user(strtolower($first_name . '-' . $last_name));

        // Ensure username is unique by appending numbers if needed
        $original_username = $username;
        $i = 1;
        while (username_exists($username)) {
            $username = sanitize_user($original_username . $i);
            $i++;
        }

        if (email_exists($email)) {
            echo '<div class="error"><p>' . esc_html__('Email already exists.', 'tickethub') . '</p></div>';
        } else {
            $user_id = wp_create_user($username, wp_generate_password(), $email);
            if (!is_wp_error($user_id)) {
                // Set the role to 'th_ticket_creator'
                $user = new WP_User($user_id);
                $user->set_role('th_ticket_creator');
                // Add first and last name to user meta
                update_user_meta($user_id, 'first_name', $first_name);
                update_user_meta($user_id, 'last_name', $last_name);

                wp_send_new_user_notifications($user_id, 'user');

                echo '<div class="updated"><p>' . esc_html__('New ticket creator assigned.', 'tickethub') . '</p></div>';
            } else {
                echo '<div class="error"><p>' . esc_html__('Error assigning ticket creator: ', 'tickethub') . esc_html($user_id->get_error_message()) . '</p></div>';
            }
        }
    }

    // Handle CSV upload if the Plus plugin is active
    if (is_plugin_active('ticket-hub-plus/ticket-hub-plus.php')) {
        th_plus_handle_csv_upload();
    }

    // Display the form
?>
    <div class="wrap">
        <form method="post">
            <?php wp_nonce_field('create_th_ticket_creator', 'create_user_nonce'); ?>
            <h2><?php esc_html_e('Add Ticket Creator', 'tickethub'); ?></h2>
            <table class="form-table">
                <tr>
                    <th><label for="first_name"><?php esc_html_e('First Name', 'tickethub'); ?></label></th>
                    <td><input type="text" name="first_name" id="first_name" required></td>
                </tr>
                <tr>
                    <th><label for="last_name"><?php esc_html_e('Last Name', 'tickethub'); ?></label></th>
                    <td><input type="text" name="last_name" id="last_name" required></td>
                </tr>
                <tr>
                    <th><label for="email"><?php esc_html_e('Email', 'tickethub'); ?></label></th>
                    <td><input type="email" name="email" id="email" required></td>
                </tr>
            </table>
            <input type="submit" class="button button-primary" value="<?php esc_attr_e('Create User', 'tickethub'); ?>">
        </form>

        <?php
        // Display bulk upload form if the Plus plugin is active
        if (is_plugin_active('ticketHubPlus/ticketHubPlus.php')) {
            th_plus_handle_csv_upload();
            th_plus_bulk_upload_form();
        }
        ?>

        <h2 style="margin-top: 50px;"><?php esc_html_e('List of Ticket Creators', 'tickethub'); ?></h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php esc_html_e('Username', 'tickethub'); ?></th>
                    <th><?php esc_html_e('First Name', 'tickethub'); ?></th>
                    <th><?php esc_html_e('Last Name', 'tickethub'); ?></th>
                    <th><?php esc_html_e('Email', 'tickethub'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                $th_ticket_creators = get_th_ticket_creators();
                if (!empty($th_ticket_creators)) {
                    foreach ($th_ticket_creators as $user) {
                        $first_name = get_user_meta($user->ID, 'first_name', true);
                        $last_name = get_user_meta($user->ID, 'last_name', true);
                        echo '<tr>';
                        echo '<td>' . esc_html($user->user_login) . '</td>';
                        echo '<td>' . esc_html($first_name) . '</td>';
                        echo '<td>' . esc_html($last_name) . '</td>';
                        echo '<td>' . esc_html($user->user_email) . '</td>';
                        echo '</tr>';
                    }
                } else {
                    echo '<tr><td colspan="4">' . esc_html__('No Users found.', 'tickethub') . '</td></tr>';
                }
                ?>
            </tbody>
        </table>
    </div>
<?php
}
?>