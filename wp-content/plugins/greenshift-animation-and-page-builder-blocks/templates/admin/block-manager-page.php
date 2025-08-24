<?php
// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

// Get all registered GreenShift blocks
function gspb_get_all_blocks() {
    $blocks = array();
    
    // Get all registered blocks
    $registered_blocks = WP_Block_Type_Registry::get_instance()->get_all_registered();
    
    foreach ($registered_blocks as $block_name => $block_type) {
        // Only include GreenShift blocks
        if (strpos($block_name, 'greenshift-blocks/') === 0) {
            $blocks[$block_name] = array(
                'name' => $block_name,
                'title' => $block_type->title,
                'category' => $block_type->category,
                'description' => $block_type->description
            );
        }
    }
    
    return $blocks;
}

// Get all Element block variations
function gspb_get_element_variations() {
    $variations = array();
    
    // These are the variations defined in the Element block
    $element_variations = array(
        'contentwrapper' => 'Section element',
        'contentcolumns' => 'Section Element with columns',
        'pinnedhorizontalscroll' => 'Pinned Horizontal Scroll',
        'divtext' => 'DIV element with text',
        'divrepeater' => 'Dynamic Repeater',
        'img' => 'Image element',
        'h1' => 'H1 heading element',
        'h2' => 'H2 heading element',
        'h3' => 'H3 heading element',
        'a' => 'Link element (A tag)',
        'p' => 'P element',
        'span' => 'SPAN element',
        'chart' => 'Chart element',
        'iconlist' => 'Icon list element',
        'ul' => 'UL list element',
        'li' => 'LI list element',
        'video' => 'Video element',
        'audio' => 'Audio element',
        'counter' => 'Counter element',
        'cover' => 'Cover element with image',
        'accordion' => 'Accordion elements',
        'tabs' => 'Tabs element',
        'countdown' => 'Countdown element',
        'draggable' => 'Draggable Scroll element',
        'splittext' => 'Split Text element',
        'textanimated' => 'Text Animated element',
        'marquee' => 'Marquee element',
        'section' => 'Section tag',
        'button' => 'Button element',
        'blockquote' => 'Blockquote element',
        'code' => 'Code element',
        'main' => 'Main element',
        'aside' => 'Aside element',
        'article' => 'Article element',
        'form' => 'FORM element',
        'input' => 'INPUT element',
        'textarea' => 'Textarea element',
        'select' => 'Select element',
        'label' => 'Label element',
        'tr' => 'TR element',
        'td' => 'TD element',
        'th' => 'TH element',
        'table' => 'Table element',
        'svg' => 'SVG element',
        'svgtextpath' => 'SVG Text Path element',
        'buttonoverlay' => 'Button Element with Overlay',
        'div' => 'DIV element'
    );
    
    foreach ($element_variations as $name => $title) {
        $variations['greenshift-blocks/element:' . $name] = array(
            'name' => 'greenshift-blocks/element:' . $name,
            'title' => $title,
            'category' => 'Element Variations',
            'description' => 'Element block variation: ' . $title
        );
    }
    
    return $variations;
}

// Get all WordPress user roles
function gspb_block_get_user_roles() {
    $roles = array();
    $wp_roles = wp_roles();
    
    foreach ($wp_roles->roles as $role_name => $role_info) {
        $roles[$role_name] = array(
            'name' => $role_name,
            'display_name' => $role_info['name'],
            'capabilities' => $role_info['capabilities']
        );
    }
    
    return $roles;
}

// Handle form submission
if (isset($_POST['gspb_save_block_manager']) && wp_verify_nonce($_POST['gspb_block_manager_nonce'], 'gspb_block_manager_action')) {
    $global_settings = get_option('gspb_global_settings', array());
    $block_manager_settings = array();
    
    $roles = gspb_block_get_user_roles();
    $blocks = gspb_get_all_blocks();
    $variations = gspb_get_element_variations();
    
    foreach ($roles as $role_name => $role_info) {
        $role_settings = array();
        
        // Check for simplified panels setting
        if (isset($_POST['simplified_panels'][$role_name])) {
            $role_settings['simplified_panels'] = true;
        } else {
            $role_settings['simplified_panels'] = false;
        }
        
        // Check for disabled blocks
        if (isset($_POST['disabled_blocks'][$role_name]) && is_array($_POST['disabled_blocks'][$role_name])) {
            $role_settings['disabled_blocks'] = $_POST['disabled_blocks'][$role_name];
        }
        
        // Check for disabled variations
        if (isset($_POST['disabled_variations'][$role_name]) && is_array($_POST['disabled_variations'][$role_name])) {
            $role_settings['disabled_variations'] = $_POST['disabled_variations'][$role_name];
        }
        
        // Save role settings if there are any settings (simplified panels, disabled blocks, or disabled variations)
        if (isset($role_settings['simplified_panels']) || !empty($role_settings['disabled_blocks']) || !empty($role_settings['disabled_variations'])) {
            $block_manager_settings[$role_name] = $role_settings;
        }
    }
    
    $global_settings['block_manager'] = $block_manager_settings;
    update_option('gspb_global_settings', $global_settings);
    
    echo '<div class="notice notice-success"><p>' . __('Block manager settings saved successfully!', 'greenshift-animation-and-page-builder-blocks') . '</p></div>';
}

// Get current settings
$global_settings = get_option('gspb_global_settings', array());
$block_manager_settings = isset($global_settings['block_manager']) ? $global_settings['block_manager'] : array();

$roles = gspb_block_get_user_roles();
$blocks = gspb_get_all_blocks();
$variations = gspb_get_element_variations();
?>

<style>
.block-manager-container {
    max-width: 1200px;
    margin: 0 auto;
}

.role-section {
    background: #fff;
    border: 1px solid #e6e8ec;
    border-radius: 8px;
    margin-bottom: 30px;
    overflow: hidden;
}

.role-header {
    background: #f8fafc;
    padding: 15px 20px;
    border-bottom: 1px solid #e6e8ec;
    margin: 0;
    font-size: 1.2em;
}

.role-content {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    padding: 20px;
}

.simplified-panels-section {
    grid-column: 1 / -1;
    margin-bottom: 20px;
}

.section-title {
    margin-top: 0;
    margin-bottom: 15px;
    color: #1e293b;
    font-size: 16px;
    font-weight: 700;
    padding-bottom: 8px;
    border-bottom: 2px solid #e2e8f0;
    position: relative;
}

.section-title::after {
    content: '';
    position: absolute;
    bottom: -2px;
    left: 0;
    width: 40px;
    height: 2px;
    background: #0073aa;
    border-radius: 1px;
}

.checkbox-container {
    max-height: 300px;
    overflow-y: auto;
    border: 1px solid #e6e8ec;
    background: #fff;
    border-radius: 8px;
    padding: 15px;
    box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.05);
}

.checkbox-container::-webkit-scrollbar {
    width: 8px;
}

.checkbox-container::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 4px;
}

.checkbox-container::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
}

.checkbox-container::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

.checkbox-item {
    display: flex;
    align-items: flex-start;
    margin-bottom: 12px;
    padding: 12px;
    border-radius: 8px;
    transition: all 0.3s ease;
    border: 1px solid #e6e8ec;
    background: #fff;
    position: relative;
}

.checkbox-item:hover {
    background-color: #f8fafc;
    border-color: #0073aa;
    box-shadow: 0 2px 8px rgba(0, 115, 170, 0.1);
    transform: translateY(-1px);
}

.checkbox-item input[type="checkbox"] {
    margin-right: 12px;
    margin-top: 2px;
    width: 18px;
    height: 18px;
    border: 2px solid #d1d5db;
    border-radius: 4px;
    background: #fff;
    cursor: pointer;
    transition: all 0.2s ease;
    position: relative;
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
}

.checkbox-item input[type="checkbox"]:checked {
    background: #0073aa;
    border-color: #0073aa;
    position: relative;
}

.checkbox-item input[type="checkbox"]:checked::after {
    content: '✓';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    color: #fff;
    font-size: 12px;
    font-weight: bold;
    line-height: 1;
}

.checkbox-item input[type="checkbox"]:focus {
    outline: none;
    box-shadow: 0 0 0 3px rgba(0, 115, 170, 0.2);
}

.checkbox-item input[type="checkbox"]:hover {
    border-color: #0073aa;
}

.checkbox-content {
    flex: 1;
    padding-left: 4px;
}

.checkbox-title {
    font-weight: 600;
    color: #23282d;
    margin-bottom: 4px;
    font-size: 14px;
    line-height: 1.3;
}

.checkbox-name {
    font-size: 11px;
    color: #6b7280;
    font-family: 'SF Mono', Monaco, 'Cascadia Code', 'Roboto Mono', Consolas, 'Courier New', monospace;
    background: #f3f4f6;
    padding: 2px 6px;
    border-radius: 3px;
    display: inline-block;
    line-height: 1.2;
}

.save-button {
    margin-top: 20px;
    padding: 12px 24px;
    font-size: 16px;
}

.description {
    background: #e7f3ff;
    border-left: 4px solid #0073aa;
    padding: 15px;
    margin-bottom: 30px;
    border-radius: 4px;
}

@media (max-width: 768px) {
    .role-content {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .checkbox-container {
        max-height: 250px;
    }
}
</style>


<div class="wp-block-greenshift-blocks-container alignfull gspb_container gspb_container-gsbp-ead11204-4841" id="gspb_container-id-gsbp-ead11204-4841">
    <div class="wp-block-greenshift-blocks-container gspb_container gspb_container-gsbp-cbc3fa8c-bb26" id="gspb_container-id-gsbp-cbc3fa8c-bb26"> 

        <?php $activetab = 'block_manager';?>
        <?php include(GREENSHIFT_DIR_PATH . 'templates/admin/navleft.php'); ?> 

        <div class="wp-block-greenshift-blocks-container gspb_container gspb_container-gsbp-89d45563-1559" id="gspb_container-id-gsbp-89d45563-1559">
            <div class="wp-block-greenshift-blocks-container gspb_container gspb_container-gsbp-efb64efe-d083" id="gspb_container-id-gsbp-efb64efe-d083">
                <h2 id="gspb_heading-id-gsbp-ca0b0ada-6561" class="gspb_heading gspb_heading-id-gsbp-ca0b0ada-6561 "><?php esc_html_e("Block Manager", 'greenshift-animation-and-page-builder-blocks'); ?></h2>
            </div>

            <div class="wp-block-greenshift-blocks-container gspb_container gspb_container-gsbp-7b4f8e8f-1a69" id="gspb_container-id-gsbp-7b4f8e8f-1a69">
                <div class="greenshift_form block-manager-container">
                    <div class="wp-block-greenshift-blocks-infobox gspb_infoBox gspb_infoBox-id-gsbp-158b5f3e-b35c" id="gspb_infoBox-id-gsbp-158b5f3e-b35c">
                        <div class="gs-box notice_type icon_type">
                            <div class="gs-box-icon"><svg class="" style="display:inline-block;vertical-align:middle" width="32" height="32" viewBox="0 0 704 1024" xmlns="http://www.w3.org/2000/svg">
                                    <path style="fill:#565D66" d="M352 160c-105.88 0-192 86.12-192 192 0 17.68 14.32 32 32 32s32-14.32 32-32c0-70.6 57.44-128 128-128 17.68 0 32-14.32 32-32s-14.32-32-32-32zM192.12 918.34c0 6.3 1.86 12.44 5.36 17.68l49.020 73.68c5.94 8.92 15.94 14.28 26.64 14.28h157.7c10.72 0 20.72-5.36 26.64-14.28l49.020-73.68c3.48-5.24 5.34-11.4 5.36-17.68l0.1-86.36h-319.92l0.080 86.36zM352 0c-204.56 0-352 165.94-352 352 0 88.74 32.9 169.7 87.12 231.56 33.28 37.98 85.48 117.6 104.84 184.32v0.12h96v-0.24c-0.020-9.54-1.44-19.020-4.3-28.14-11.18-35.62-45.64-129.54-124.34-219.34-41.080-46.86-63.040-106.3-63.22-168.28-0.4-147.28 119.34-256 255.9-256 141.16 0 256 114.84 256 256 0 61.94-22.48 121.7-63.3 168.28-78.22 89.22-112.84 182.94-124.2 218.92-2.805 8.545-4.428 18.381-4.44 28.594l-0 0.006v0.2h96v-0.1c19.36-66.74 71.56-146.36 104.84-184.32 54.2-61.88 87.1-142.84 87.1-231.58 0-194.4-157.6-352-352-352z"></path>
                                </svg></div>
                            <div class="gs-box-text">
                            <?php esc_html_e('Use this page to control which blocks and variations are available to different user roles in the block inserter. Simplified panel option allows you to hide the block manager panel for non editor user roles and lock position of blocks.', 'greenshift-animation-and-page-builder-blocks'); ?>
                            </div>
                        </div>
                    </div>
                    
                    <form method="POST" class="greenshift_form">
                        <?php wp_nonce_field('gspb_block_manager_action', 'gspb_block_manager_nonce'); ?>
                        
                        <?php foreach ($roles as $role_name => $role_info): ?>
                            <div class="role-section">
                                <h3 class="role-header"><?php echo esc_html($role_info['display_name']); ?></h3>
                                
                                <div class="role-content">
                                    <!-- Simplified Panels Section -->
                                    <div class="simplified-panels-section">
                                        <h4 class="section-title"><?php esc_html_e('Simplified Panels', 'greenshift-animation-and-page-builder-blocks'); ?></h4>
                                        <div style="padding: 10px;">
                                            <div class="checkbox-item">
                                                <input type="checkbox" 
                                                       id="simplified_panels_<?php echo esc_attr($role_name); ?>"
                                                       name="simplified_panels[<?php echo esc_attr($role_name); ?>]"
                                                       <?php checked(isset($block_manager_settings[$role_name]['simplified_panels']) && $block_manager_settings[$role_name]['simplified_panels']); ?>>
                                                <div class="checkbox-content">
                                                    <div class="checkbox-title"><?php esc_html_e('Enable simplified interface panels', 'greenshift-animation-and-page-builder-blocks'); ?></div>
                                                    <div class="checkbox-name"><?php esc_html_e('Enable simplified interface panels for this user role', 'greenshift-animation-and-page-builder-blocks'); ?></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Blocks Section -->
                                    <div class="blocks-section">
                                        <h4 class="section-title"><?php esc_html_e('Disable Blocks', 'greenshift-animation-and-page-builder-blocks'); ?></h4>
                                        <div style="margin-bottom: 10px;">
                                            <button type="button" class="button select-all-btn" data-target="blocks_<?php echo esc_attr($role_name); ?>">
                                                <?php esc_html_e('Select All', 'greenshift-animation-and-page-builder-blocks'); ?>
                                            </button>
                                            <button type="button" class="button deselect-all-btn" data-target="blocks_<?php echo esc_attr($role_name); ?>">
                                                <?php esc_html_e('Deselect All', 'greenshift-animation-and-page-builder-blocks'); ?>
                                            </button>
                                        </div>
                                        <div class="checkbox-container" id="blocks_<?php echo esc_attr($role_name); ?>">
                                            <?php foreach ($blocks as $block_name => $block_info): ?>
                                                <div class="checkbox-item">
                                                    <input type="checkbox" 
                                                           id="block_<?php echo esc_attr($role_name . '_' . sanitize_title($block_name)); ?>"
                                                           name="disabled_blocks[<?php echo esc_attr($role_name); ?>][]" 
                                                           value="<?php echo esc_attr($block_name); ?>"
                                                           <?php checked(isset($block_manager_settings[$role_name]['disabled_blocks']) && in_array($block_name, $block_manager_settings[$role_name]['disabled_blocks'])); ?>>
                                                    <div class="checkbox-content">
                                                        <div class="checkbox-title"><?php echo esc_html($block_info['title']); ?></div>
                                                        <div class="checkbox-name"><?php echo esc_html($block_name); ?></div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    
                                    <!-- Variations Section -->
                                    <div class="variations-section">
                                        <h4 class="section-title"><?php esc_html_e('Disable Element Variations', 'greenshift-animation-and-page-builder-blocks'); ?></h4>
                                        <div style="margin-bottom: 10px;">
                                            <button type="button" class="button select-all-btn" data-target="variations_<?php echo esc_attr($role_name); ?>">
                                                <?php esc_html_e('Select All', 'greenshift-animation-and-page-builder-blocks'); ?>
                                            </button>
                                            <button type="button" class="button deselect-all-btn" data-target="variations_<?php echo esc_attr($role_name); ?>">
                                                <?php esc_html_e('Deselect All', 'greenshift-animation-and-page-builder-blocks'); ?>
                                            </button>
                                        </div>
                                        <div class="checkbox-container" id="variations_<?php echo esc_attr($role_name); ?>">
                                            <?php foreach ($variations as $variation_name => $variation_info): ?>
                                                <div class="checkbox-item">
                                                    <input type="checkbox" 
                                                           id="variation_<?php echo esc_attr($role_name . '_' . sanitize_title($variation_name)); ?>"
                                                           name="disabled_variations[<?php echo esc_attr($role_name); ?>][]" 
                                                           value="<?php echo esc_attr($variation_name); ?>"
                                                           <?php checked(isset($block_manager_settings[$role_name]['disabled_variations']) && in_array($variation_name, $block_manager_settings[$role_name]['disabled_variations'])); ?>>
                                                    <div class="checkbox-content">
                                                        <div class="checkbox-title"><?php echo esc_html($variation_info['title']); ?></div>
                                                        <div class="checkbox-name"><?php echo esc_html($variation_name); ?></div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <input type="submit" name="gspb_save_block_manager" value="<?php esc_attr_e('Save Settings', 'greenshift-animation-and-page-builder-blocks'); ?>" class="button button-primary button-large save-button">
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Select All functionality
    document.querySelectorAll('.select-all-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const container = document.getElementById(targetId);
            if (container) {
                const checkboxes = container.querySelectorAll('input[type="checkbox"]');
                checkboxes.forEach(function(checkbox) {
                    checkbox.checked = true;
                });
            }
        });
    });

    // Deselect All functionality
    document.querySelectorAll('.deselect-all-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const container = document.getElementById(targetId);
            if (container) {
                const checkboxes = container.querySelectorAll('input[type="checkbox"]');
                checkboxes.forEach(function(checkbox) {
                    checkbox.checked = false;
                });
            }
        });
    });
});
</script>