<?php
/**
 * Gravity Forms Bootstrap 4 Styles
 *
 * Applies Bootstrap 4 classes to various common field types.
 * Requires Bootstrap 4 to be in use by the theme.
 *
 * Using this function allows use of Gravity Forms default CSS
 * in conjuction with Bootstrap (benefit for fields types such as Address).
 *
 * @see  gform_field_content
 * @link http://www.gravityhelp.com/documentation/page/Gform_field_content
 *
 * @return string Modified field content
 */
add_filter("gform_field_content", "bootstrap4_styles_for_gravityforms_fields", 0, 5);
function bootstrap4_styles_for_gravityforms_fields($content, $field, $value, $lead_id, $form_id){

	if($field["type"] != 'hidden' && $field["type"] != 'list' && $field["type"] != 'checkbox' && $field["type"] != 'fileupload' && $field["type"] != 'date' && $field["type"] != 'html' && $field["type"] != 'address') {
		$content = str_replace('class=\'medium', 'class=\'form-control medium', $content);
	}

	if($field["type"] == 'name' || $field["type"] == 'address' || $field["type"] == 'list' || $field["type"] == 'time' || $field["type"] == 'date') {
			$content = str_replace('<input ', '<input class=\'form-control\' ', $content);
		
			if($field["type"] == 'address' || $field["type"] == 'time') {
				$content = str_replace('<select ', '<select style="height: 2.25rem;" class=\'form-control\' ', $content);
			}
	}
	
	if($field["type"] == 'number') {
			$content = str_replace('<input ', '<input class=\'form-control small\' ', $content);
	}

	if($field["type"] == 'textarea') {
			$content = str_replace('class=\'textarea', 'class=\'form-control textarea', $content);
	}
	
	if($field["type"] == 'checkbox' || $field["type"] == 'radio') {
		$content = str_replace('li class=\'', 'li style="padding-left: 1.25rem !important;" class=\'form-check ', $content);
		$content = str_replace('<input ', '<input style="position: absolute; margin-top: .3rem; margin-left: -1.2rem;" class=\'form-check-input\' ', $content);
		$content = str_replace('<label ', '<label class=\'form-check-label\' ', $content);
	}

	return $content;
} // End bootstrap_styles_for_gravityforms_fields()

// Update submit button class
function form_submit_btn($button, $form){
	$dom = new DOMDocument();
    $dom->loadHTML( $button );
    $input = $dom->getElementsByTagName( 'input' )->item(0);
    $new_button = $dom->createElement( 'button' );
    $new_button->appendChild( $dom->createTextNode( $input->getAttribute( 'value' ) ) );
    $input->removeAttribute( 'value' );
	$input->removeAttribute( 'class' );
    foreach( $input->attributes as $attribute ) {
        $new_button->setAttribute( $attribute->name, $attribute->value );
    }
	$new_button->setAttribute( 'class', 'btn btn-default');
    $input->parentNode->replaceChild( $new_button, $input );

    return $dom->saveHtml( $new_button );
}
add_filter('gform_submit_button','form_submit_btn',10,2);

?>