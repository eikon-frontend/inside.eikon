<?php
/*
 * Display confirmation message and form after successful submission.
 *
 * @link  https://wpforms.com/developers/how-to-display-the-confirmation-and-the-form-again-after-submission/
 *
 */
function wpf_dev_frontend_output_success(  $form_data, $fields, $entry_id ) {



  // Optional, you can limit to specific forms. Below, we restrict output to form #235.
  if ( absint( $form_data['id'] ) !== absint(get_field("contact_form_id", "option")) ) {
      return;
  }
  // Reset the fields to blank
  unset(
      $_GET['wpforms_return'],
      $_POST['wpforms']['id']
  );

  // If you want to preserve the user entered values in form fields - remove the line below.
  unset( $_POST['wpforms']['fields'] );

  // Actually render the form.
  wpforms()->frontend->output( $form_data['id'] );
}

add_action( 'wpforms_frontend_output_success', 'wpf_dev_frontend_output_success', 10, 3 );
