<?php

defined('ABSPATH') || exit;

class TMDinamicPricesPlugin
{

    /**
     * Iniciamos el plugin
     * @return void
     * @author Daniel Lucia
     */
    public function init(): void
    {
        add_action('dl_ticket_event_fields_after', [$this, 'eventFieldsAfter'], 10, 3);
        add_action('dl_ticket_save_event_fields', [$this, 'saveEventFields']);
        add_filter('woocommerce_product_get_price', [$this, 'setDynamicPrice'], 20, 2);
        add_action('woocommerce_single_product_summary', [$this, 'showDynamicPricesOnProduct'], 15);
    }

    /**
     * Cambia el precio del producto dependiendo de los rangos
     * @param mixed $price
     * @param mixed $product
     * @author Daniel Lucia
     */
    public function setDynamicPrice($price, $product)
    {
        if ($product->get_type() !== 'ticket') {
            return $price;
        }

        $prices = get_post_meta($product->get_id(), '_dynamic_prices', true);
        if (!is_array($prices) || empty($prices)) {
            return $price;
        }

        $today = date('Y-m-d');
        $dynamic_price = null;

        //Ordenamos los precios
        usort($prices, function ($a, $b) {
            return strtotime($a['date']) <=> strtotime($b['date']);
        });

        foreach ($prices as $row) {
            if (!empty($row['date']) && !empty($row['price'])) {
                if ($today <= $row['date']) {
                    $dynamic_price = floatval($row['price']);
                    break;
                }
            }
        }

        // Si no hay precio válido, usamos el último
        if ($dynamic_price === null && !empty($prices)) {
            $last = end($prices);
            if (!empty($last['price'])) {
                $dynamic_price = floatval($last['price']);
            }
        }

        return $dynamic_price !== null ? $dynamic_price : $price;
    }

    /**
     * Muestra los precios dinámicos
     * @return void
     * @author Daniel Lucia
     */
    public function eventFieldsAfter()
    {
        echo '<div class="options_group">';
?>
        <div class="_event_dynamic_prices" style="padding: 10px 0 10px 162px; width:100%;max-width:500px;">

            <label><?php esc_html_e('Dinamic prices', 'dl-ticket-manager-dinamic-prices'); ?></label>

            <table id="dynamic-prices-table" style="width:100%;">
                <thead>
                    <tr>
                        <th style="text-align: left;"><?php esc_html_e('End date', 'dl-ticket-manager-dinamic-prices'); ?></th>
                        <th style="text-align: left;"><?php esc_html_e('Price', 'dl-ticket-manager-dinamic-prices'); ?></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $prices = get_post_meta(get_the_ID(), '_dynamic_prices', true);
                    if (!is_array($prices)) {
                        $prices = [[]];
                    }

                    foreach ($prices as $row) {
                    ?>
                        <tr>
                            <td><input type="date" name="dynamic_price_date[]" value="<?php echo esc_attr($row['date'] ?? ''); ?>" style="width: 100%;" /></td>
                            <td><input type="number" step="0.01" min="0" name="dynamic_price_value[]" value="<?php echo esc_attr($row['price'] ?? ''); ?>" style="width: 100%;" /></td>
                            <td><button type="button" class="button remove-row">-</button></td>
                        </tr>
                    <?php
                    }
                    ?>
                </tbody>
            </table>

            <button type="button" class="button" id="add-dynamic-price-row" style="margin: 5px 0 0 auto;display: block;"><?php esc_html_e('Add price', 'dl-ticket-manager-dinamic-prices'); ?></button>

        </div>

        <script>
            jQuery(document).ready(function($) {
                $('#add-dynamic-price-row').on('click', function() {
                    $('#dynamic-prices-table tbody').append(
                        '<tr>' +
                        '<td><input type="date" name="dynamic_price_date[]" value="" style="width: 100%;"  /></td>' +
                        '<td><input type="number" step="0.01" min="0" name="dynamic_price_value[]" value="" style="width: 100%;"  /></td>' +
                        '<td><button type="button" class="button remove-row">-</button></td>' +
                        '</tr>'
                    );
                });
                $(document).on('click', '.remove-row', function() {
                    $(this).closest('tr').remove();
                });
            });
        </script>
<?php

        echo '</div>';
    }

    /**
     * Guarda los precios dinámicos
     * @param mixed $post_id
     * @return void
     * @author Daniel Lucia
     */
    public function saveEventFields(): void
    {

        if (isset($_POST['dynamic_price_date']) && isset($_POST['dynamic_price_value'])) {
            $prices = [];

            foreach ($_POST['dynamic_price_date'] as $key => $date) {
                $price = $_POST['dynamic_price_value'][$key] ?? 0;
                $prices[] = ['date' => $date, 'price' => $price];
            }

            update_post_meta(get_the_ID(), '_dynamic_prices', $prices);
        }
    }

    /**
     * Mostramos los rangos de precios en la ficha de producto
     * @return void
     * @author Daniel Lucia
     */
    public function showDynamicPricesOnProduct()
    {
        global $product;

        if ($product->get_type() !== 'ticket') {
            return;
        }

        $prices = get_post_meta($product->get_id(), '_dynamic_prices', true);
        if (!is_array($prices) || empty($prices)) {
            return;
        }

        // Ordena los precios por fecha ascendente
        usort($prices, function ($a, $b) {
            return strtotime($a['date']) <=> strtotime($b['date']);
        });

        echo '<div class="dl-ticket-dynamic-prices" style="margin-bottom:15px;">';
        echo '<strong>' . esc_html__('Rangos de precio:', 'dl-ticket-manager-dinamic-prices') . '</strong>';
        echo '<ul style="margin:0; padding-left:18px;">';
            foreach ($prices as $row) {
                if (!empty($row['date']) && !empty($row['price'])) {
                    echo '<li>' .
                        esc_html__('Hasta el', 'dl-ticket-manager-dinamic-prices') . ' ' .
                        esc_html(date_i18n(get_option('date_format'), strtotime($row['date']))) .
                        ': <strong>' . wc_price($row['price']) . '</strong>';
                    echo '</li>';
                }
            }

            // Precio normal después del último rango
            echo '<li>' .
                esc_html__('Después de la última fecha:', 'dl-ticket-manager-dinamic-prices') .
                ' <strong>' . wc_price($product->get_regular_price()) . '</strong>';
            echo '</li>';

        echo '</ul>';
        echo '</div>';
    }
}
