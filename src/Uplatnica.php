<?php

class Uplatnica
{
    /** @var  tFPDF */
    protected $pdf;

    /** @var  string */
    protected $uplatilac = '';

    /** @var  string */
    protected $svrha = '';

    /** @var  string */
    protected $primalac = '';

    /** @var  string */
    protected $telefon = '';

    /** @var  string */
    protected $sifra = '';

    /** @var  string */
    protected $sifraQr = '';

    /** @var  string */
    protected $valuta = 'RSD';

    /** @var  string */
    protected $iznos = '';

    /** @var  string */
    protected $racun = '';

    /** @var  string */
    protected $model = '';

    /** @var  string */
    protected $poziv_na_broj = '';

    /** @var  bool */
    protected $izgenerisana = false;
    /**
     * @var bool|string
     */
    protected $generisiQrKod = false;

    /**
     * Uplatnica constructor.
     *
     * @param string $orientation
     * @param string $units
     * @param string $paper
     * @param int    $fontSize
     *
     * @throws Exception
     */
    public function __construct($orientation = 'L', $units = 'mm', $paper = 'A4', $fontSize = 12, $generisiQrKod = false)
    {
        if (!class_exists('tFPDF')) {
            throw new Exception("This class requires tFPDF to work!");
        }
        $this->reset($orientation, $units, $paper, $fontSize);
        $this->generisiQrKod = $generisiQrKod;
    }

    /**
     * @param string $orientation
     * @param string $units
     * @param string $paper
     * @param int    $fontSize
     *
     * @return $this
     */
    public function reset($orientation = 'L', $units = 'mm', $paper = 'A4', $fontSize = 12)
    {
        $this->pdf = new tFPDF($orientation, $units, $paper);

        $this->pdf->AddPage();

        $this->pdf->AddFont('DejaVu', '', 'DejaVuSansCondensed.ttf', true);
        $this->pdf->SetFont('DejaVu', '', $fontSize);

        $this->izgenerisana = false;

        return $this;
    }

    /**
     * @param string $uplatilac
     *
     * @return $this
     */
    public function uplatilac($uplatilac)
    {
        $this->uplatilac = $uplatilac;
        return $this;
    }

    /**
     * @return array
     */
    protected function getUplatilac()
    {
        $parts = preg_split("/(\r\n|\n|\r)/", $this->uplatilac);
        $ime   = array_shift($parts);
        return [
            'ime'    => $ime,
            'adresa' => $parts,
        ];
    }

    /**
     * @param string $svrha
     *
     * @return $this
     */
    public function svrha($svrha)
    {
        $this->svrha = $svrha;
        return $this;
    }

    /**
     * @param string $primalac
     *
     * @return $this
     */
    public function primalac($primalac)
    {
        $this->primalac = $primalac;
        return $this;
    }

    /**
     * @param string $tel
     *
     * @return $this
     */
    public function telefon($tel)
    {
        $this->telefon = $tel;
        return $this;
    }

    /**
     * @return array
     */
    protected function getPrimalac()
    {
        $parts = preg_split("/(\r\n|\n|\r)/", $this->primalac);
        $ime   = array_shift($parts);
        return [
            'ime'    => $ime,
            'adresa' => $parts,
        ];
    }

    /**
     * @param string $sifra
     *
     * @return $this
     */
    public function sifra($sifra)
    {
        $this->sifra = $sifra;
        return $this;
    }

    /**
     * @param string $sifra
     *
     * @return $this
     */
    public function sifraQr($sifra)
    {
        $this->sifraQr = $sifra;
        return $this;
    }

    /**
     * @param string $valuta
     *
     * @return $this
     */
    public function valuta($valuta)
    {
        $this->valuta = $valuta;
        return $this;
    }

    /**
     * @param int|float $iznos
     *
     * @return $this
     */
    public function iznos($iznos)
    {
        $this->iznos = $iznos;
        return $this;
    }

    public function formatirajIznos($decimale = 2, $decimalSeparator = ',', $hiljadeSeparator = '.')
    {
        return number_format($this->iznos, $decimale, $decimalSeparator, $hiljadeSeparator);
    }

    /**
     * @param string $racun
     *
     * @return $this
     */
    public function racun($racun)
    {
        $this->racun = $racun;
        return $this;
    }

    /**
     * @param string $model
     *
     * @return $this
     */
    public function model($model)
    {
        $this->model = $model;
        return $this;
    }

    /**
     * @param string $poziv_na_broj
     *
     * @return $this
     */
    public function pozivNaBroj($poziv_na_broj)
    {
        $this->poziv_na_broj = $poziv_na_broj;
        return $this;
    }

    /**
     * @return $this
     */
    protected function generisi()
    {
        if ($this->izgenerisana) {
            return $this;
        }

        $this->pdf->Image(dirname(__DIR__).'/assets/nalog-za-uplatu.jpg');

        $this->pdf->Text(17, 25, $this->getUplatilac()['ime'].($this->telefon ? ' ('.$this->telefon.')' : ''));
        $this->pdf->Text(17, 30, isset($this->getUplatilac()['adresa'][0]) ? $this->getUplatilac()['adresa'][0] : '');
        $this->pdf->Text(17, 35, isset($this->getUplatilac()['adresa'][1]) ? $this->getUplatilac()['adresa'][1] : '');
        $this->pdf->Text(17, 47, $this->svrha);
        $this->pdf->Text(17, 67, $this->getPrimalac()['ime']);
        $this->pdf->Text(17, 72, isset($this->getPrimalac()['adresa'][0]) ? $this->getPrimalac()['adresa'][0] : '');
        $this->pdf->Text(17, 77, isset($this->getPrimalac()['adresa'][1]) ? $this->getPrimalac()['adresa'][1] : '');

        $this->pdf->Text(124, 29, $this->sifra);
        $this->pdf->Text(142, 29, $this->valuta);
        $this->pdf->Text(162, 29, $this->iznos ? '= '.$this->formatirajIznos() : '');

        $this->pdf->Text(124, 43, $this->racun);

        $this->pdf->Text(124, 55, $this->model);
        $this->pdf->Text(142, 55, $this->poziv_na_broj);

        if ($this->generisiQrKod) {
            $this->pdf->Image($this->nbsQrUrl(), 5, 118, 75, 75, 'png');
            $this->pdf->Text(10, 117, $this->generisiQrKod);
        }

        $this->izgenerisana = true;

        return $this;
    }

    /**
     * @param string $fileName
     */
    public function prikazi($fileName = 'uplatnica.pdf')
    {
        $this->generisi()->pdf->Output($fileName, 'I');
    }

    /**
     * @param string $fileName
     */
    public function download($fileName = 'uplatnica.pdf')
    {
        $this->generisi()->pdf->Output($fileName, 'D');
    }

    /**
     * @param string $filePath
     *
     * @throws Exception
     */
    public function sacuvaj($filePath)
    {
        if (!is_dir(dirname($filePath))) {
            $dir = mkdir(dirname($filePath), 0755, true);

            if (!$dir) {
                throw new Exception(dirname($filePath).' could not be created! Check your folder permissions.');
            }
        }

        if (!is_writable(dirname($filePath)) || (is_file($filePath) && !is_writable($filePath))) {
            throw new Exception('Could not write file '.$filePath);
        }

        $this->generisi()->pdf->Output($filePath, 'F');
    }


    public function nbsQrKod()
    {
        $racun   = preg_replace('/[^0-9]/', '', $this->racun);
        $prva3   = substr($racun, 0, 3);
        $ostatak = substr($racun, 3);

        if (strlen($ostatak) < 15) {
            $ostatak = str_pad($ostatak, 15, '0', STR_PAD_LEFT);
        }

        $racun    = $prva3.$ostatak;
        $primalac = $this->getPrimalac()['ime'];

        if ($adresa = $this->getPrimalac()['adresa'][0] ?? false) {
            $primalac .= "\n".$adresa;
        }

        if ($adresa2 = $this->getPrimalac()['adresa'][1] ?? false) {
            $primalac .= "\n".$adresa2;
        }

        $primalac = substr($primalac, 0, 70);
        $iznos    = $this->formatirajIznos(2, ',', '');
        $platilac = $this->getUplatilac()['ime'].($this->telefon ? ' ('.$this->telefon.')' : '');

        if ($adresa = $this->getUplatilac()['adresa'][0] ?? false) {
            $platilac .= "\n".$adresa;
        }

        if ($adresa2 = $this->getUplatilac()['adresa'][1] ?? false) {
            $platilac .= "\n".$adresa2;
        }

        $platilac = substr($platilac, 0, 70);
        $sifra    = trim($this->sifraQr);
        $svrha    = substr($this->svrha, 0, 35);
        $model    = empty($this->model) ? '00' : $this->model;
        $poziv    = preg_replace('/[^0-9\-]/', '', $this->poziv_na_broj);

        if (empty($poziv)) {
            return "K:PR|V:01|C:1|R:{$racun}|N:{$primalac}|I:RSD{$iznos}|P:{$platilac}|SF:{$sifra}|S:{$svrha}";
        }

        return "K:PR|V:01|C:1|R:{$racun}|N:{$primalac}|I:RSD{$iznos}|P:{$platilac}|SF:{$sifra}|S:{$svrha}|RO:{$model}{$poziv}";
    }

    public function nbsQrUrl($size = 256)
    {
        return "https://chart.googleapis.com/chart?cht=qr&chs={$size}x{$size}&chld=L|0&chl="
            .urlencode($this->nbsQrKod());
    }

    /**
     * @param string $orientation
     * @param string $units
     * @param string $paper
     * @param int    $fontSize
     *
     * @return static
     */
    public static function nova($generisiQrKod = false, $orientation = 'L', $units = 'mm', $paper = 'A4', $fontSize = 12)
    {
        return new self($orientation, $units, $paper, $fontSize, $generisiQrKod);
    }

}