<?php
if (!defined('ABSPATH')) exit;

// Verificar se há um ID de material válido
$material_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$material_id) {
    wp_die(__('Material não encontrado.', 'gma-plugin'));
}

// Obter dados do material
$material = gma_obter_material($material_id);
if (!$material) {
    wp_die(__('Material não encontrado.', 'gma-plugin'));
}

// Processar formulário de atualização
if (isset($_POST['atualizar_material']) && isset($_POST['gma_nonce']) && wp_verify_nonce($_POST['gma_nonce'], 'editar_material')) {
    $copy = sanitize_textarea_field($_POST['copy']);
    $link_canva = sanitize_url($_POST['link_canva']);
    $imagem_url = isset($_POST['imagem_url']) ? sanitize_url($_POST['imagem_url']) : $material->imagem_url;

    $dados = array(
        'copy' => $copy,
        'link_canva' => $link_canva,
        'imagem_url' => $imagem_url
    );

    if (gma_atualizar_material($material_id, $dados)) {
        echo '<div class="gma-notice success">
                <i class="dashicons dashicons-yes-alt"></i> 
                ' . __('Material atualizado com sucesso!', 'gma-plugin') . '
              </div>';
        $material = gma_obter_material($material_id);
    } else {
        echo '<div class="gma-notice error">
                <i class="dashicons dashicons-warning"></i> 
                ' . __('Erro ao atualizar material.', 'gma-plugin') . '
              </div>';
    }
}

// Enqueue necessário
wp_enqueue_media();
wp_enqueue_style('dashicons');
wp_enqueue_script('jquery');

// Localizar script para AJAX
wp_localize_script('jquery', 'gma_ajax', array(
    'ajaxurl' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('gma_copy_suggestions')
));
?>

<div class="gma-approval-wrap">
    <h1 class="gma-approval-title"><?php _e('Edit Material', 'gma-plugin'); ?></h1>
    
    <div class="gma-approval-card">
        <form id="gma-approval-form" method="post" action="">
          
            <?php wp_nonce_field('editar_material', 'gma_nonce'); ?>
            <input type="hidden" name="material_id" value="<?php echo esc_attr($material->id); ?>">
            <input type="hidden" name="imagem_url" id="gma-imagem-url" value="<?php echo esc_attr($material->imagem_url); ?>">
            
            <div class="gma-material-preview">
                <div id="gma-image-preview">
                    <?php if (!empty($material->imagem_url)): ?>
                        <img src="<?php echo esc_url($material->imagem_url); ?>" alt="<?php _e('Material Preview', 'gma-plugin'); ?>">
                    <?php endif; ?>
                </div>
                <button type="button" id="gma-upload-btn" class="gma-button secondary">
                    <i class="dashicons dashicons-upload"></i>
                    <?php _e('Update Image', 'gma-plugin'); ?>
                </button>
            </div>
          
            <div class="gma-form-group">
                <label for="copy">
                    <i class="dashicons dashicons-edit"></i>
                    <?php _e('Material Copy', 'gma-plugin'); ?>
                </label>
                <textarea name="copy" id="copy" rows="5"><?php echo esc_textarea($material->copy); ?></textarea>
            </div>

          <div class="gma-form-group full-width">
    <button type="button" id="get-suggestions" class="gma-button secondary">
        <i class="dashicons dashicons-admin-customizer"></i> <?php _e('Get AI Suggestions', 'gma-plugin'); ?>
    </button>
    <div id="suggestions-container" style="display: none;">
        <h3><?php _e('AI Suggestions', 'gma-plugin'); ?></h3>
        <div id="suggestions-content"></div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    $('#get-suggestions').on('click', function() {
        const copy = $('#copy').val();
        const button = $(this);
        
        if (!copy) {
            alert( wp.i18n.__( 'Please enter some text first.', 'gma-plugin' ) );
            return;
        }
        
        button.prop('disabled', true).text( wp.i18n.__( 'Getting suggestions...', 'gma-plugin' ) );
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'gma_get_copy_suggestions',
                nonce: '<?php echo wp_create_nonce("gma_copy_suggestions"); ?>',
                copy: copy
            },
            success: function(response) {
                if (response.success) {
                    $('#suggestions-content').html(response.data.suggestions);
                    $('#suggestions-container').slideDown();
                } else {
                    alert( wp.i18n.__( 'Failed to get suggestions. Please try again.', 'gma-plugin' ) );
                }
            },
            error: function() {
                alert( wp.i18n.__( 'Error connecting to the server.', 'gma-plugin' ) );
            },
            complete: function() {
                button.prop('disabled', false).text( wp.i18n.__( 'Get AI Suggestions', 'gma-plugin' ) );
            }
        });
    });
});
</script>

<style>
#suggestions-container {
    margin-top: 20px;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 5px;
    border-left: 4px solid #4a90e2;
}

#suggestions-content {
    white-space: pre-line;
    line-height: 1.5;
}

#get-suggestions {
    margin-top: 10px;
}
</style>
          
            <div class="gma-form-group">
                <label for="link_canva">
                    <i class="dashicons dashicons-admin-links"></i>
                    <?php _e('Canva Link', 'gma-plugin'); ?>
                </label>
                <input type="url" name="link_canva" id="link_canva" value="<?php echo esc_url($material->link_canva); ?>" placeholder="https://www.canva.com/...">
            </div>

            <div class="gma-form-actions">
                <button type="submit" name="atualizar_material" class="gma-button primary">
                    <i class="dashicons dashicons-yes-alt"></i>
                    <?php _e('Update Material', 'gma-plugin'); ?>
                </button>
                <a href="javascript:history.back()" class="gma-button secondary">
                    <i class="dashicons dashicons-dismiss"></i>
                    <?php _e('Cancel', 'gma-plugin'); ?>
                </a>
            </div>
          
        </form>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Inicialização do Media Uploader
    var mediaUploader;
    
    $('#gma-upload-btn').on('click', function(e) {
        e.preventDefault();

        if (mediaUploader) {
            mediaUploader.open();
            return;
        }

        mediaUploader = wp.media({
            title: '<?php _e('Choose or upload an image', 'gma-plugin'); ?>',
            button: {
                text: '<?php _e('Use this image', 'gma-plugin'); ?>'
            },
            multiple: false
        });

        mediaUploader.on('select', function() {
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            $('#gma-imagem-url').val(attachment.url);
            $('#gma-image-preview').html('<img src="' + attachment.url + '" alt="<?php _e('Material Preview', 'gma-plugin'); ?>">');
        });

        mediaUploader.open();
    });

    // Sugestões AI
    $('#get-suggestions').on('click', function() {
        const copy = $('#copy').val();
        const button = $(this);
        
        if (!copy) {
            alert( wp.i18n.__( 'Please enter some text first.', 'gma-plugin' ) );
            return;
        }
        
        button.prop('disabled', true).text( wp.i18n.__( 'Getting suggestions...', 'gma-plugin' ) );
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'gma_get_copy_suggestions',
                nonce: '<?php echo wp_create_nonce("gma_copy_suggestions"); ?>',
                copy: copy
            },
            success: function(response) {
                if (response.success) {
                    $('#suggestions-content').html(response.data.suggestions);
                    $('#suggestions-container').slideDown();
                } else {
                    alert( wp.i18n.__( 'Failed to get suggestions. Please try again.', 'gma-plugin' ) );
                }
            },
            error: function() {
                alert( wp.i18n.__( 'Error connecting to the server.', 'gma-plugin' ) );
            },
            complete: function() {
                button.prop('disabled', false).text( wp.i18n.__( 'Get AI Suggestions', 'gma-plugin' ) );
            }
        });
    });
});
</script>

<style>
:root {
    --primary-color: #4a90e2;
    --secondary-color: #2ecc71;
    --danger-color: #e74c3c;
    --text-color: #2c3e50;
    --background-color: #f5f6fa;
    --card-background: #ffffff;
    --border-radius: 12px;
    --transition: all 0.3s ease;
}

.gma-approval-wrap {
    padding: 30px;
    background: var(--background-color);
    min-height: 100vh;
}

.gma-approval-title {
    font-size: 2.5em;
    color: var(--text-color);
    text-align: center;
    margin-bottom: 30px;
    font-weight: 700;
}

.gma-approval-card {
    max-width: 800px;
    margin: 0 auto;
    background: var(--card-background);
    border-radius: var(--border-radius);
    box-shadow: 0 8px 30px rgba(0,0,0,0.1);
    padding: 30px;
    animation: slideUp 0.5s ease;
}

.gma-material-preview {
    text-align: center;
    margin-bottom: 30px;
}

.gma-material-preview img {
    max-width: 100%;
    height: auto;
    border-radius: var(--border-radius);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    transition: var(--transition);
    margin-bottom: 15px;
}

.gma-form-group {
    margin-bottom: 25px;
}

.gma-form-group label {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 10px;
    font-weight: 600;
    color: var(--text-color);
}

select, textarea, input[type="url"] {
    width: 100%;
    padding: 12px;
    border: 2px solid #e1e1e1;
    border-radius: var(--border-radius);
    font-size: 1em;
    transition: var(--transition);
}

.gma-button {
    padding: 12px 24px;
    border: none;
    border-radius: var(--border-radius);
    cursor: pointer;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: var(--transition);
}

.gma-button.primary {
    background: var(--primary-color);
    color: white;
}

.gma-button.secondary {
    background: var(--secondary-color);
    color: white;
}

.gma-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}

@keyframes slideUp {
    from {
        transform: translateY(20px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

@media (max-width: 768px) {
    .gma-approval-wrap {
        padding: 15px;
    }
    
    .gma-form-actions {
        flex-direction: column;
    }
    
    .gma-button {
        width: 100%;
        justify-content: center;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Inicialização do Media Uploader
    var mediaUploader;
    
    $('#gma-upload-btn').on('click', function(e) {
        e.preventDefault();

        if (mediaUploader) {
            mediaUploader.open();
            return;
        }

        mediaUploader = wp.media({
            title: '<?php _e('Choose or upload an image', 'gma-plugin'); ?>',
            button: {
                text: '<?php _e('Use this image', 'gma-plugin'); ?>'
            },
            multiple: false
        });

        mediaUploader.on('select', function() {
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            $('#gma-imagem-url').val(attachment.url);
            $('#gma-image-preview').html('<img src="' + attachment.url + '" alt="<?php _e('Material Preview', 'gma-plugin'); ?>">');
        });

        mediaUploader.open();
    });

    // Sugestões AI
    $('#get-suggestions').on('click', function() {
        const copy = $('#copy').val();
        const button = $(this);
        
        if (!copy) {
            alert( wp.i18n.__( 'Please enter some text first.', 'gma-plugin' ) );
            return;
        }
        
        button.prop('disabled', true).text( wp.i18n.__( 'Getting suggestions...', 'gma-plugin' ) );
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'gma_get_copy_suggestions',
                nonce: '<?php echo wp_create_nonce("gma_copy_suggestions"); ?>',
                copy: copy
            },
            success: function(response) {
                if (response.success) {
                    $('#suggestions-content').html(response.data.suggestions);
                    $('#suggestions-container').slideDown();
                } else {
                    alert( wp.i18n.__( 'Failed to get suggestions. Please try again.', 'gma-plugin' ) );
                }
            },
            error: function() {
                alert( wp.i18n.__( 'Error connecting to the server.', 'gma-plugin' ) );
            },
            complete: function() {
                button.prop('disabled', false).text( wp.i18n.__( 'Get AI Suggestions', 'gma-plugin' ) );
            }
        });
    });
});
</script>

<style>
#suggestions-container {
    margin-top: 20px;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 5px;
    border-left: 4px solid #4a90e2;
}

#suggestions-content {
    white-space: pre-line;
    line-height: 1.5;
}

#get-suggestions {
    margin-top: 10px;
}
</style>