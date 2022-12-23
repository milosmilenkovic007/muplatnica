<?php

class WC_Gateway_Muplatnica extends WC_Payment_Gateway
{

    /**
     * WC_Gateway_Muplatnica constructor.
     */
    public function __construct()
    {
        $this->init_settings();
        $this->init_form_fields();

        $this->id                 = 'uplatnica';
        $this->has_fields         = false;
        $this->method_title       = 'Opšta uplatnica';
        $this->method_description = 'Plaćanje opštom uplatnicom u poštama i bankama Srbije sa mogućnošću generisanja NBS IPS QR kôda.';
        $this->title              = $this->get_option('title');
        $this->description        = $this->get_option('description');

        add_action('woocommerce_update_options_payment_gateways_'.$this->id, array($this, 'process_admin_options'));

    }

    /**
     * Initialize gateway settings
     */
    public function init_form_fields()
    {
        $this->form_fields = array(
            'enabled'     => array(
                'title'   => __('Aktivirano', 'muplatnica'),
                'type'    => 'checkbox',
                'label'   => __('Uključi generisanje uplatnica', 'muplatnica'),
                'default' => '',
            ),
            'title'       => array(
                'title'       => __('Naslov*', 'muplatnica'),
                'type'        => 'text',
                'description' => __('Naslov ovog tipa plaćanja, prikazan kupcima.', 'muplatnica'),
                'default'     => "Opšta uplatnica",
            ),
            'description' => array(
                'title'       => __('Opis*', 'muplatnica'),
                'type'        => 'textarea',
                'description' => __('Opis koji vide kupci.', 'muplatnica'),
                'default'     => "Dobićete opštu uplatnicu u PDF formatu na email koju možete iskoristiti za plaćanje.",
            ),

            'instructions'  => array(
                'title'       => __('Uputstvo', 'muplatnica'),
                'type'        => 'textarea',
                'description' => __('Tekst email poruke koja se šalje kupcu.', 'muplatnica'),
                'default'     => "U prilogu ove poruke ćete naći uplatnicu u PDF formatu koju možete iskoristiti za plaćanje.",
            ),
            'primalac'      => array(
                'title'       => __('Primalac*', 'muplatnica'),
                'type'        => 'textarea',
                'description' => __('Puno ime osobe/firme i adresa u drugom redu.', 'muplatnica'),
                'default'     => "Petar Petrović\nBul. Kralja Aleksandra 154/2, 11000 Beograd",
            ),
            'platilac_tel'  => array(
                'title'       => __('Telefon platioca', 'muplatnica'),
                'type'        => 'checkbox',
                'label'       => __('Uključi broj telefona platioca', 'muplatnica'),
                'description' => __('Označite ako želite da uplatnica sadrži broj telefona platioca.', 'muplatnica'),
                'default'     => '',
            ),
            'racun'         => array(
                'title'       => __('Broj računa*', 'muplatnica'),
                'description' => __('Broj računa na koji se vrše uplate.', 'muplatnica'),
                'type'        => 'text',
                'default'     => '',
            ),
            'svrha'         => array(
                'title'       => __('Svrha uplate*', 'muplatnica'),
                'description' => __('Svrha uplate. Možete koristiti %order%, %date%, %year%, %month%, %day% i %products% promenljive.', 'muplatnica'),
                'type'        => 'text',
                'default'     => 'Plaćanje porudžbine #%order%',
            ),
            'sifra'         => array(
                'title'   => __('Šifra plaćanja', 'muplatnica'),
                'type'    => 'text',
                'default' => '',
            ),
            'valuta'        => array(
                'title'   => __('Valuta*', 'muplatnica'),
                'type'    => 'text',
                'default' => 'RSD',
            ),
            'model'         => array(
                'title'   => __('Model', 'muplatnica'),
                'type'    => 'text',
                'default' => '',
            ),
            'poziv_na_broj' => array(
                'title'       => __('Poziv na broj', 'muplatnica'),
                'description' => __('Poziv na broj. Možete koristiti %order%, %date%, %year%, %month% i %day% promenljive.', 'muplatnica'),
                'type'        => 'text',
                'default'     => '%year%-%order%',
            ),
            'veznik'        => array(
                'title'       => __('Veznik', 'muplatnica'),
                'type'        => 'text',
                'description' => __('Koristi se za spajanje proizvoda kada koristite %products% promenljivu.', 'muplatnica'),
                'default'     => 'i',
            ),
            'qr_code'       => array(
                'title'   => __('QR kôd', 'muplatnica'),
                'type'    => 'checkbox',
                'label'   => __('Uključi generisanje NBS IPS QR kôda', 'muplatnica'),
                'default' => '',
            ),
            'qr_code_sifra' => array(
                'title'       => __('QR šifra plaćanja', 'muplatnica'),
                'type'        => 'text',
                'description' => __('Šifra plaćanja za NBS IPS QR kôd. Proverite sa knjigovodstvom šta upisati ovde.', 'muplatnica'),
                'default'     => '189',
            ),
            'qr_code_opis'  => array(
                'title'       => __('QR uputstvo', 'muplatnica'),
                'type'        => 'text',
                'description' => __('Kratko uputstvo za skeniranje QR kôda. Biće prikazano iznad QR kôda u generisanom PDF-u.', 'muplatnica'),
                'default'     => 'Možete platiti i skeniranjem sledećeg NBS IPS QR kôda:',
            ),
        );
    }

    /**
     * @param int $order_id
     *
     * @return array
     */
    public function process_payment($order_id)
    {
        global $woocommerce;
        $order = new WC_Order($order_id);

        $order->update_status('on-hold', __('Awaiting payment', 'woocommerce'));
        $woocommerce->cart->empty_cart();

        return array(
            'result'   => 'success',
            'redirect' => $this->get_return_url($order)
        );
    }

}