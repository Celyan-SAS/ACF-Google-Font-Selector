<?php

/**
 * Common Functions
 *
 * This file contains the common functions shared between the ACF 5 and ACF 4
 * versions.
 *
 * @author Daniel Pataki
 * @since 3.0.0
 *
 */


/**
  * Get Fonts To Enqueue
  *
  * Retrieves the fonts to enqueue on the current page
  *
  * @return array The array of fonts to enqueue
  * @author Daniel Pataki
  * @since 3.0.0
  *
  */
function acfgfs_get_fonts_to_enqueue() {
    if( is_singular() ) {
        global $post;
        $post_fields = get_field_objects( $post->ID );
    }
    $post_fields = ( empty( $post_fields ) ) ? array() : $post_fields;
    $option_fields = get_field_objects( 'options' );
    $option_fields = ( empty( $option_fields ) ) ? array() : $option_fields;
    $fields = array_merge( $post_fields, $option_fields );
    $font_fields = array();
    foreach( $fields as $field ) {
        if( !empty( $field['type'] ) && 'google_font_selector' == $field['type'] && !empty( $field['value'] ) ) {
            $font_fields[] = $field['value'];
        }
    }

    $font_fields = apply_filters( 'acfgfs/enqueued_fonts', $font_fields );

    if($_SERVER['REMOTE_ADDR'] == '109.220.119.249'){
        //var_dump($font_fields);
    }

    return $font_fields;
}

/**
  * Enqueue Fonts
  *
  * Retrieves the fonts to enqueue on the current page
  *
  * @uses acfgfs_get_fonts_to_enqueue()
  * @author Daniel Pataki
  * @since 3.0.0
  *
  */
function acfgfs_google_font_enqueue(){
    $fonts = acfgfs_get_fonts_to_enqueue();
    if( empty( $fonts ) ) {
        return;
    }
    $subsets = array();
    $font_element = array();
    foreach( $fonts as $font ) {

        $subsets = array_merge( $subsets, $font['subsets'] );
        $font_name = str_replace( ' ', '+', $font['font'] );

        if($_SERVER['REMOTE_ADDR'] == '109.220.119.249'){
            //var_dump($font);
        }

        if(!empty($font['variants'])){
            if( $font['variants'] == array( 'regular' ) ) {
                $font_element[] = $font_name;
            }
            else {
                $regular_variant = array_search( 'regular', $font['variants'] );
                if( $regular_variant !== false ) {
                    $font['variants'][$regular_variant] = '400';
                }
                $font_element[] = $font_name . ':' . implode( ',', $font['variants'] );
            }
        }
    }
    $subsets = ( empty( $subsets ) ) ? array('latin') : array_unique( $subsets );
    $subset_string = implode( ',', $subsets );
    $font_string = implode( '|', $font_element );
    $request = '//fonts.googleapis.com/css?family=' . $font_string . '&subset=' . $subset_string;
    wp_enqueue_style( 'acfgfs-enqueue-fonts', $request );
}



/**
 * Font Dropdown Array
 *
 * Retrieves a list of fonts as an array. The array uses the
 * font name as the key and value. Uses the acfgfs_get_web_safe_fonts()
 * function to add a list of we safe fonts if the user enabled it in the
 * options.
 *
 * @param array $field The field data
 * @uses acfgfs_get_web_safe_fonts()
 * @return array The array of fonts
 * @author Daniel Pataki
 * @since 3.0.0
 *
 */
function acfgfs_get_font_dropdown_array( $field = null ) {
    $fonts = acfgfs_get_fonts();

    $font_array = array();
    foreach( $fonts as $font => $data ) {
        $font_array[$font] = $font;
    }

    if( !empty( $field['include_web_safe_fonts'] ) ) {
        $web_safe = acfgfs_get_web_safe_fonts();
        foreach( $web_safe as $font ) {
            $font_array[$font['family']] = $font['family'];
        }
    }

    asort( $font_array );

    $font_array = apply_filters( 'acfgfs/font_dropdown_array', $font_array );

    return $font_array;
}

/**
 * Get Font
 *
 * Retrieves the details of a single font
 *
 * @param string $font The name of the font to retrieve
 * @uses acfgfs_get_fonts()
 * @return array The details of the font
 * @author Daniel Pataki
 * @since 3.0.0
 *
 */
function acfgfs_get_font( $font ) {

    $fonts = acfgfs_get_fonts();	


    //echo "<pre>", print_r("FONT SAVE 02 --- ", 1), "</pre>";
    //echo "<pre>", print_r($font, 1), "</pre>";
    ////echo "<pre>", print_r("fonts", 1), "</pre>";
    ////echo "<pre>", print_r($fonts, 1), "</pre>";
    //
    //echo "<pre>", print_r("what?", 1), "</pre>";
    //echo "<pre>", print_r($fonts[$font], 1), "</pre>";

    //die();

    if ( empty( $font ) || !is_scalar( $font ) || empty($fonts) || !is_array($fonts) || empty($fonts[$font]) ) { return array(); }

    return $fonts[$font];
}

/**
 * Get Fonts
 *
 * Gets all fonts. It first checks a transient. If the transient doesn't
 * exist it gets all fonts from Google. If this fails for some reason we
 * fall back on a file which has a font list.
 *
 * We then create a special format that works for us and finally
 * add the web safe fonts if they are needed.
 *
 * @uses acfgfs_retrieve_fonts()
 * @uses acfgfs_get_web_safe_fonts()
 * @return array The final font list
 * @author Daniel Pataki
 * @since 3.0.0
 *
 */
function acfgfs_get_fonts() {
    $fonts = get_transient( 'acfgfs_fonts' );
    if( empty( $fonts ) ) {
        $fonts = acfgfs_retrieve_fonts();
    }
    if( empty( $fonts ) ) {
        $fonts = include( 'font-list.php' );
    }

    $acfgfs_fonts = array();

    if( !empty( $field['include_web_safe_fonts'] ) ) {
        $web_safe = acfgfs_get_web_safe_fonts();
        $fonts = array_merge($fonts, $web_safe);
    }
    
    if( isset( $fonts['items']  ) && is_array( $fonts['items']  ) ) {
        foreach( $fonts['items'] as $font ) {
            if( !isset($font['variants']) || !isset($font['subsets']) || !isset( $font['family'] ) ) { continue; }

            $acfgfs_fonts[$font['family']] = array(
                'variants' => $font['variants'],
                'subsets' => $font['subsets']
            );
        }
    }

    /*
    if( !empty( $field['include_web_safe_fonts'] ) ) {
        $web_safe = acfgfs_get_web_safe_fonts();
        foreach( $web_safe as $font ) {
            $acfgfs_fonts[$font['family']] = array(
                'variants' => array( 'regular', '700' ),
                'subsets' => array( 'latin' ),
                'websafe' => $font['websafe'],
            );
        }
    }
    */

    return $acfgfs_fonts;
}
acfgfs_get_fonts();

/**
 * Retrieve Google Fonts
 *
 * Gets all Google fonts from the Google API. It uses the API key which is
 * either saved or defined as a constant.
 *
 * @return array The Google font list
 * @author Daniel Pataki
 * @since 3.0.0
 *
 */
function acfgfs_retrieve_fonts() {

    $api_key = get_option('acfgfs_api_key');
    if( defined( 'ACFGFS_API_KEY' ) ) {
        $api_key = ACFGFS_API_KEY;
    }

    $request = wp_remote_get( 'https://www.googleapis.com/webfonts/v1/webfonts?key=' . $api_key );
    
    if( !isset( $request ) || !is_array( $request ) || !isset( $request['body'] ) ) {
    	/** Request Failed **/
    	return array();
    }
    
    $response = json_decode( $request['body'], true );
    if( !empty( $response['items'] ) ) {
        $timeout = ( defined( 'ACFGFS_REFRESH' ) ) ? ACFGFS_REFRESH : WEEK_IN_SECONDS;
        set_transient( 'acfgfs_fonts', $response, $timeout );
    }

    return $request;

}

/**
 * Web Safe Fonts
 *
 * A simple array of web safe fonts
 *
 * @return array Array of web safe fonts
 * @author Daniel Pataki
 * @since 3.0.0
 *
 */
function acfgfs_get_web_safe_fonts() {
    $web_safe = array( 
        array(
            'family' => 'Georgia', 
            'websafe' => 'true'
        ),
        array(
            'family' => 'Palatino Linotype', 
            'websafe' => 'true'
        ),
        array(
            'family' => 'Book Antiqua', 
            'websafe' => 'true'
        ),
        array(
            'family' => 'Palatino', 
            'websafe' => 'true'
        ),
        array(
            'family' => 'Times New Roman', 
            'websafe' => 'true'
        ),
        array(
            'family' => 'Times', 
            'websafe' => 'true'
        ),
        array(
            'family' => 'Arial, sans-serif', 
            'websafe' => 'true'
        ),
        array(
            'family' => 'Helvetica,sans-serif', 
            'websafe' => 'true'
        ),
        array(
            'family' => 'Gadget', 
            'websafe' => 'true'
        ),
        array(
            'family' => 'Impact', 
            'websafe' => 'true'
        ),
        array(
            'family' => 'Charcoal', 
            'websafe' => 'true'
        ),
        array(
            'family' => 'Lucida Sans Unicode', 
            'websafe' => 'true'
        ),
        array(
            'family' => 'Lucida Grande', 
            'websafe' => 'true'
        ),
        array(
            'family' => 'Tahoma', 
            'websafe' => 'true'
        ),
        array(
            'family' => 'Geneva', 
            'websafe' => 'true'
        ),
        array(
            'family' => 'Trebuchet MS', 
            'websafe' => 'true'
        ),
        array(
            'family' => 'Helvetica', 
            'websafe' => 'true'
        ),
        array(
            'family' => 'Verdana', 
            'websafe' => 'true'
        ),
        array(
            'family' => 'Geneva', 
            'websafe' => 'true'
        ),
        array(
            'family' => 'Courier New', 
            'websafe' => 'true'
        ),
        array(
            'family' => 'Courier', 
            'websafe' => 'true'
        ),
        array(
            'family' => 'Lucida Console', 
            'websafe' => 'true'
        ),
        array(
            'family' => 'Monaco' ,
            'websafe' => 'true'
        ),
    );
    return $web_safe;
}


/**
 * Font Variant Array
 *
 * Gets an array of font variants for a given font
 *
 * @param string $font The font to retrieve variants for
 * @uses acfgfs_get_font()
 * @return array The variant list for this font
 * @author Daniel Pataki
 * @since 3.0.0
 *
 */
function acfgfs_get_font_variant_array( $font ) {
    $font = acfgfs_get_font( $font );
    return $font['variants'];
}


/**
 * Font Subset Array
 *
 * Gets an array of font subsets for a given font
 *
 * @param string $font The font to retrieve variants for
 * @uses acfgfs_get_font()
 * @return array The subset list for this font
 * @author Daniel Pataki
 * @since 3.0.0
 *
 */
function acfgfs_get_font_subset_array( $font ) {
    $font = acfgfs_get_font( $font );
    return $font['subsets'];
}


/**
 * Font Websafe Answer
 *
 * Gets websafe answer for a given font
 *
 * @param string $font The font to retrieve variants for
 * @uses acfgfs_get_font()
 * @return array The subset list for this font
 * @author Daniel Pataki
 * @since 3.0.0
 *
 */
function acfgfs_get_font_websafe( $font ) {
    $font = acfgfs_get_font( $font );
    return $font['websafe'];
}

/**
 * Display Variant List
 *
 * Displays a checkbox list of font variants. If only a field is given it
 * looks up the current font (or uses the default). The second parameter
 * is used when a new font is selected and we grab a variant list via AJAX.
 *
 * At this stage the new font is not saved but we still need to show the
 * variant list for that font. When the $new_font parameter is given the
 * value of $field is not used.
 *
 * @param string $field The field to retrieve variants for
 * @param string $new_font The font to retrieve variants for
 * @uses acfgfs_get_font()
 * @author Daniel Pataki
 * @since 3.0.0
 *
 */
function acfgfs_display_variant_list( $field, $new_font = null ) {
    $font = $new_font;
    if( empty( $new_font ) ) {
        $font = ( empty( $field['value']['font'] ) ) ? $field['default_font'] :     $field['value']['font'];
    }

    $font = acfgfs_get_font( $font );
    $font['variants'] = (empty( $font['variants'] )) ? array() : $font['variants'];
    $i = 1;
    foreach( $font['variants'] as $variant ) :
    if( empty( $new_font ) ) {
        $checked = ( empty( $field['value'] ) || ( !empty( $field['value'] ) && in_array( $variant, $field['value']['variants'] ) ) ) ? 'checked="checked"' : '';
    }
    else {
        $checked = ( $variant == 'regular' ) ? 'checked="checked"' : '';
    }

?>

<input <?php echo $checked ?> type="checkbox" id="<?php echo $field['key'] ?>_variants_<?php echo $i ?>" name="<?php echo $field['key'] ?>_variants[]" value="<?php echo $variant ?>"><label for="<?php echo $field['key'] ?>_variants_<?php echo $i ?>"><?php echo $variant ?></label> <br>

<?php $i++; endforeach;

}

/*
function acfgfs_display_websafe( $field, $new_font = null ) {
    $font = $new_font;
    if( empty( $new_font ) ) {
        $font = ( empty( $field['value']['font'] ) ) ? $field['default_font'] :     $field['value']['font'];
    }

    $font = acfgfs_get_font( $font );
    $font['websafe'] = (empty( $font['websafe'] )) ? array() : $font['websafe'];

?>

<p>Websafe : <?php if($_SERVER['REMOTE_ADDR'] == '109.220.119.249'){ var_dump($font); } //echo $font['websafe']; ?></p>

<?php

}
*/


/**
 * Display Subset List
 *
 * Displays a checkbox list of font subsets. If only a field is given it
 * looks up the current font (or uses the default). The second parameter
 * is used when a new font is selected and we grab a subset list via AJAX.
 *
 * At this stage the new font is not saved but we still need to show the
 * subset list for that font. When the $new_font parameter is given the
 * value of $field is not used.
 *
 * @param string $field The field to retrieve subsets for
 * @param string $new_font The font to retrieve subsets for
 * @uses acfgfs_get_font()
 * @author Daniel Pataki
 * @since 3.0.0
 *
 */
function acfgfs_display_subset_list( $field, $new_font = null ) {

    $font = $new_font;
    if( empty( $new_font ) ) {
        $font = ( empty( $field['value']['font'] ) ) ? $field['default_font'] :     $field['value']['font'];
    }

    $font = acfgfs_get_font( $font );
    $font['subsets'] = (empty( $font['subsets'] )) ? array() : $font['subsets'];
    $i = 1;
    foreach( $font['subsets'] as $subset ) :
    if( empty( $new_font ) ) {
        $checked = ( empty( $field['value'] ) || ( !empty( $field['value'] ) && in_array( $subset, $field['value']['subsets'] ) ) ) ? 'checked="checked"' : '';
    }
    else {
        $checked = ( $subset == 'latin' ) ? 'checked="checked"' : '';
    }
?>
<input <?php echo $checked ?> type="checkbox" id="<?php echo $field['key'] ?>_subsets_<?php echo $i ?>" name="<?php echo $field['key'] ?>_subsets[]" value="<?php echo $subset ?>"><label for="<?php echo $field['key'] ?>_subsets_<?php echo $i ?>"><?php echo $subset ?></label> <br>

<?php $i++; endforeach;

}

/**
 * Get Font Details
 *
 * Used in AJAX requests to output the HTML needed to display the UI for
 * the newly chosen font.
 *
 * @uses acfgfs_display_subset_list()
 * @uses acfgfs_display_variant_list()
 * @author Daniel Pataki
 * @since 3.0.0
 *
 */
function acfgfs_action_get_font_details() {
    $details = array();
    $field = json_decode( stripslashes( $_POST['data'] ), true );
    unset( $field['value'] );

    ob_start();
    acfgfs_display_subset_list( $field, $_POST['font_family'] );
    $details['subsets'] = ob_get_clean();

    ob_start();
    acfgfs_display_variant_list( $field, $_POST['font_family'] );
    $details['variants'] = ob_get_clean();

    /*
    ob_start();
    acfgfs_display_websafe( $field, $_POST['font_family'] );
    $details['websafe'] = ob_get_clean();*/

    echo json_encode( $details );

    die();
}
