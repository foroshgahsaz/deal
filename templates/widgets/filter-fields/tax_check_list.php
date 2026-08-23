<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


$r = [
    'orderby'         => 'name',
    'order'           => 'ASC',
    'show_count'      => 0,
    'hide_empty'      => 0,
    'parent'          => '',
    'child_of'        => 0,
    'exclude'         => '',
    'selected'        => $selected,
    'hierarchical'    => 1,
    'name'            => $name,
    'depth'           => 0,
    'taxonomy'        => $field['taxonomy'],
    'value'           => 'slug',
    'multiple'        => true,
    'input_type'      => 'checkbox',
    'rand_id'         => rand(10000, 99999)
];

$r['lang'] = apply_filters( 'wp-cardealer-current-lang', null );

$categories_hash = 'wpcd_cats_' . md5( wp_json_encode( $r ) . WP_CarDealer_Cache_Helper::get_transient_version('wpcd_get_' . $r['taxonomy']) );
$categories      = get_transient( $categories_hash );

if ( empty( $categories ) ) {
    $cat_args = [
        'taxonomy'     => $r['taxonomy'],
        'orderby'      => $r['orderby'],
        'order'        => $r['order'],
        'hide_empty'   => $r['hide_empty'],
        'parent'       => $r['parent'],
        'child_of'     => $r['child_of'],
        'exclude'      => $r['exclude'],
        'hierarchical' => $r['hierarchical'],
    ];

    $categories = get_terms( $cat_args );

    set_transient( $categories_hash, $categories, DAY_IN_SECONDS * 7 );
}

$output = '';
if ( ! empty( $categories ) ) {
    include_once WP_CARDEALER_PLUGIN_DIR . '/includes/walkers/class-category-radio-check-walker.php';

    $walker = new WP_CarDealer_Category_Radio_Check_Walker();

    if ( $r['hierarchical'] ) {
        $depth = $r['depth'];  // Walk the full depth.
    } else {
        $depth = -1; // Flat.
    }

    $output .= $walker->walk( $categories, $depth, $r );
}

if ( !empty($output) ) {
?>
    <div class="form-group form-group-<?php echo esc_attr($key); ?> <?php echo esc_attr(!empty($field['toggle']) ? 'toggle-field' : ''); ?> <?php echo esc_attr(!empty($field['hide_field_content']) ? 'hide-content' : ''); ?> tax-checklist-field">
        <?php if ( !isset($field['show_title']) || $field['show_title'] ) { ?>
            <label class="heading-label">
                <?php echo wp_kses_post($field['name']); ?>
                <?php if ( !empty($field['toggle']) ) { ?>
                    <i class="fas fa-angle-down"></i>
                <?php } ?>
            </label>
        <?php } ?>
        <div class="form-group-inner">
            <div class="terms-list-wrapper">
                <ul class="terms-list circle-check level-0">
                    <?php echo $output; ?>
                </ul>
            </div>
        </div>
    </div><!-- /.form-group -->
<?php }