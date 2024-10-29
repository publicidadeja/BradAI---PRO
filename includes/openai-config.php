<?php
if (!defined('ABSPATH')) exit;

// Remova ou comente a função existente gma_add_openai_settings_page()

// Adicione esta nova função
function gma_register_openai_settings() {
    add_settings_section(
        'gma_openai_settings',
        __('OpenAI Settings', 'gma-plugin'), // Título da seção
        null,
        'gma-settings'
    );

    add_settings_field(
        'gma_openai_api_key',
        __('OpenAI API Key', 'gma-plugin'), // Rótulo do campo
        'gma_openai_api_key_callback',
        'gma-settings',
        'gma_openai_settings'
    );

    register_setting('gma_settings', 'gma_openai_api_key');
}
add_action('admin_init', 'gma_register_openai_settings');

// Adicione a função de callback
function gma_openai_api_key_callback() {
    $api_key = get_option('gma_openai_api_key', '');
    ?>
    <input type="text" 
           id="gma_openai_api_key" 
           name="gma_openai_api_key" 
           value="<?php echo esc_attr($api_key); ?>" 
           class="regular-text">
    <p class="description"><?php _e('Enter your OpenAI API key', 'gma-plugin'); ?></p>
    <?php
}
// Renderizar página de configurações
function gma_render_openai_settings() {
    if (isset($_POST['gma_openai_api_key'])) {
        update_option('gma_openai_api_key', sanitize_text_field($_POST['gma_openai_api_key']));
        echo '<div class="notice notice-success"><p>' . __('API Key saved successfully!', 'gma-plugin') . '</p></div>';
    }
    
    $api_key = get_option('gma_openai_api_key', '');
    ?>
    <div class="wrap">
        <h2><?php _e('OpenAI Settings', 'gma-plugin'); ?></h2>
        <form method="post">
            <table class="form-table">
                <tr>
                    <th><label for="gma_openai_api_key"><?php _e('OpenAI API Key', 'gma-plugin'); ?></label></th>
                    <td>
                        <input type="text" 
                               id="gma_openai_api_key" 
                               name="gma_openai_api_key" 
                               value="<?php echo esc_attr($api_key); ?>" 
                               class="regular-text">
                        <p class="description"><?php _e('Enter your OpenAI API key', 'gma-plugin'); ?></p>
                    </td>
                </tr>
            </table>
            <?php submit_button(__('Save Settings', 'gma-plugin')); ?>
        </form>
    </div>
    <?php
}

// Add this to includes/openai-config.php

function gma_register_language_settings() {
    add_settings_section(
        'gma_language_settings',
        __('Language Settings', 'gma-plugin'), // Título da seção
        null,
        'gma-settings'
    );

    add_settings_field(
        'gma_plugin_language',
        __('Plugin Language', 'gma-plugin'), // Rótulo do campo
        'gma_language_callback',
        'gma-settings',
        'gma_language_settings'
    );

    register_setting('gma_settings', 'gma_plugin_language');
}
add_action('admin_init', 'gma_register_language_settings');

function gma_language_callback() {
    $current_language = get_option('gma_plugin_language', 'en_US');
    $languages = array(
        'en_US' => __('English', 'gma-plugin'),
        'pt_BR' => __('Portuguese', 'gma-plugin'),
        'es_ES' => __('Spanish', 'gma-plugin')
    );
    ?>
    <select name="gma_plugin_language" id="gma_plugin_language">
        <?php foreach ($languages as $code => $name): ?>
            <option value="<?php echo esc_attr($code); ?>" <?php selected($current_language, $code); ?>>
                <?php echo esc_html($name); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <?php
}