<?php

class Muplatnica
{
    /**
     * @var string
     */
    protected $path;

    /**
     * @var array
     */
    protected $options;

    /**
     * Initialize plugin and hooks
     */
    public function __construct()
    {
        load_plugin_textdomain('muplatnica', false, basename(dirname(__FILE__)).'/languages');

        $this->options = get_option('woocommerce_uplatnica_settings');
        $paths         = wp_upload_dir();
        $this->path    = str_replace('uploads', 'uplatnice', $paths['path']);

        add_filter('woocommerce_payment_gateways', array($this, 'add_muplatnica_gateway_class'));
        add_filter('admin_footer_text', [$this, '_replace_footer_admin']);
        if ($this->options && $this->options['enabled'] === 'yes') {
            add_filter('woocommerce_email_attachments', array($this, 'attach_pdf'), 10, 3);
            add_action('woocommerce_thankyou_uplatnica', array($this, 'thankyou_page'));
            add_action('woocommerce_email_before_order_table', array($this, 'email_instructions'), 10, 3);
        }

    }

    public function _replace_footer_admin()
    {
        echo '<em>Uplatnice generiše sa &hearts; <a href="https://milos.live/" target="_blank">MMUplatnica</a> plugin.</em> ';
    }


    /**
     * @param array    $attachments
     * @param string   $type
     * @param WC_Order $order
     *
     * @return array
     * @throws Exception
     */
    function attach_pdf($attachments, $type, $order)
    {
        if (!$order) {
            return $attachments;
        }

        $orderMethod = get_post_meta($order->get_id(), '_payment_method', true);

        if ($orderMethod === 'uplatnica' && $this->options['enabled'] === 'yes' && in_array($type, [
                'customer_invoice',
                'customer_on_hold_order'
            ])) {

            if (!is_dir($this->path)) {
                $created = mkdir($this->path, 0755, true);

                if (!$created) {
                    throw new Exception('Could not create directory '.$this->path);
                }

                file_put_contents($this->path.'/index.html', 'Nothing to see here.');
            }

            $order    = apply_filters('muplatnica_order', $order);
            $ukupno   = apply_filters('muplatnica_cena', $order->get_total());
            $fileName = $this->path.'/'.$order->get_id().'-'.sanitize_title($order->get_billing_first_name()).'-'.sanitize_title($order->get_billing_last_name()).'.pdf';
            $qrCode   = ($this->options['qr_code'] === 'yes') ? $this->options['qr_code_opis'] : false;

            Uplatnica::nova($qrCode)
                ->primalac(trim($this->options['primalac']) ?: get_bloginfo('name')."\n".get_bloginfo('description'))
                ->racun($this->options['racun'])
                ->svrha($this->replace($this->options['svrha'], $order))
                ->sifra($this->options['sifra'])
                ->sifraQr($this->options['qr_code_sifra'])
                ->valuta($this->options['valuta'] ?: $order->get_currency())
                ->model($this->options['model'])
                ->pozivNaBroj($this->replace($this->options['poziv_na_broj'], $order))
                ->iznos($ukupno)
                ->uplatilac($order->get_billing_first_name().' '.$order->get_billing_last_name()."\n".$order->get_billing_address_1().($order->get_billing_address_2() ? ' '.$order->get_billing_address_2() : '')."\n".$order->get_billing_postcode().' '.$order->get_billing_city())
                ->telefon(($this->options['platilac_tel'] === 'yes') ? $order->get_billing_phone() : false)
                ->sacuvaj($fileName);

            $attachments[] = $fileName;
        }

        return $attachments;
    }

    /**
     * @param string   $string
     * @param WC_Order $order
     *
     * @return mixed
     */
    protected function replace($string, $order)
    {
        return str_replace([
            '%order%',
            '%date%',
            '%year%',
            '%month%',
            '%day%',
            '%products%',
        ], [
            $order->get_id(),
            date('Y-m-d'),
            date('Y'),
            date('m'),
            date('d'),
            $this->product_list($order),
        ], $string);
    }

    /**
     * @param WC_Order $order
     *
     * @return string
     */
    protected function product_list($order)
    {
        $items  = $order->get_items();
        $titles = [];

        foreach ($items as $item) {
            $titles[] = $item->get_name();
        }

        if (count($titles) === 1) {
            return $titles[0];
        }

        $last = array_pop($titles);

        return implode(', ', $titles).sprintf(' %s ', $this->options['veznik'] ?? 'i').$last;
    }

    /**
     * @param array $methods
     *
     * @return array
     */
    public function add_muplatnica_gateway_class($methods)
    {
        $methods[] = WC_Gateway_Muplatnica::class;

        return $methods;
    }

    /**
     * Add content to the WC emails.
     *
     * @param WC_Order $order
     * @param bool     $sent_to_admin
     * @param bool     $plain_text
     */
    public function email_instructions($order, $sent_to_admin, $plain_text = false)
    {
        if (!$sent_to_admin && 'uplatnica' === $order->get_payment_method() && $order->has_status('on-hold')) {
            if ($this->options['instructions']) {
                echo wpautop(wptexturize($this->options['instructions'])).PHP_EOL;
            }
        }
    }

    /**
     * Output for the order received page.
     *
     * @param int $order_id
     */
    public function thankyou_page($order_id)
    {
        if ($this->options['description']) {
            echo wpautop(wptexturize(wp_kses_post($this->options['description'])));
        }
    }


}