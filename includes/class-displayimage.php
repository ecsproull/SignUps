<?php
/*
 * Summary
 * Database class.
 *
 * @package     SignUps
 * @author      Edward Sproull
 * @copyright   You have the right to copy
 * license     GPL-2.0+
 */

/**
 * Display's a members image
 */
class DisplayImage extends SignUpsBase {

    public function render_image_page() {
        global $wpdb;
        $badge = '4038';
        $row = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT *
				FROM %1s
				where photo_badge = %s',
				self::PHOTO_TABLE,
				$badge
			),
			OBJECT
		);
        $data = $row->photo_image;
        $mime = ! empty( $row->photo_mime ) ? $row->photo_mime : 'image/jpeg';

        // base64 encode and produce data URL
        $b64 = base64_encode( $data );
        $src = 'data:' . esc_attr( $mime ) . ';base64,' . $b64;

        ?>
       <img src=<?php echo $src; ?> alt='Ed' width='180' height='200'>
       <?php 
    }
}


 