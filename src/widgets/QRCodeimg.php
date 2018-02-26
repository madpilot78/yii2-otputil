<?php

namespace mad\otputil\widgets;

use Yii;
use yii\base\Widget;
use yii\helpers\Html;
use yii\web\ServerErrorHttpException;
use mad\otputil\models\Secret;
use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Encoder\Encoder;
use BaconQrCode\Writer as QRCWriter;

class QRCodeimg extends Widget
{
    /**
     * @var Secret The secret object to be rendered
     */
    private $secret;

    /**
     * @var int Secret ID to be rendered
     */
    public $sid = null;

    /**
     * @var string Error correction level, (L|M|Q|H)
     */
    public $ecLevel = 'L';

    /**
     * @var string format to be used, (eps|png|svg)
     */
    public $fmt = 'png';

    /**
     * @var int height of the resulting image in pixels
     */
    public $height = 384;

    /**
     * @var int width of the resulting image in pixels
     */
    public $width = 384;

    /**
     * @var string username encoded with the secret
     */
    public $username = '';

    /**
     * @var string issuer encoded with the secret
     */
    public $issuer = '';
    
    /**
     * @var string label label to the encoded secret
     */
    public $label = '';

    /**
     * @var string alt text in img element
     */
    public $alt = 'QRCode';

    /**
     * @var array extra options for the img widget
     */
    public $imgopts = [];

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        if ($this->sid === null)
            throw new ServerErrorHttpException('Missing Secret ID');

        if (!in_array($this->fmt, ['eps', 'png', 'svg']))
            throw new ServerErrorHttpException('Invalid image format');

        switch ($this->ecLevel) {
            case 'L':
                $this->ecLevel = ErrorCorrectionLevel::L;
                break;

            case 'M':
                $this->ecLevel = ErrorCorrectionLevel::M;
                break;

            case 'Q':
                $this->ecLevel = ErrorCorrectionLevel::Q;
                break;

            case 'H':
                $this->ecLevel = ErrorCorrectionLevel::H;
                break;

            default:
                throw new ServerErrorHttpException('Invalid error correction level');
                break;
        }

        if (strpos($this->label, ':') || strpos($this->username, ':'))
            throw new ServerErrorHttpException('QRCode label and username cannot contain ":"');

        $this->secret = Secret::findOne($this->sid);
        if (is_null($this->secret))
            throw new ServerErrorHttpException('Secret not found');
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        $coded = "otpauth://{$this->secret->mode}/";

        if ($this->label)
            $coded .= Html::encode($this->label);

        $coded .= ':';

        if ($this->username)
            $coded .= Html::encode($this->username);

        $coded .= '?';

        $data = [
            'secret' => $this->secret->secret,
            'algorithm' => $this->secret->algo,
            'digits' => $this->secret->digits,
        ];

        if ($this->issuer)
            $data['issuer'] = Html::encode($this->issuer);

        if ($this->secret->mode == 'totp') {
            $data['period'] = $this->secret->period;
        } elseif ($this->secret->mode == 'hotp') {
            $data['counter'] = $this->secret->counter;
        }

        $coded .= \http_build_query($data);

        $ufmt = $this->fmt;
        $ufmt[0] = strtoupper($ufmt[0]);

        $renderformat = '\\BaconQrCode\\Renderer\\Image\\' . $ufmt;

        $renderer = new $renderformat();
        $renderer->setHeight($this->height);
        $renderer->setWidth($this->width);

        $writer = new QRCWriter($renderer);

        $qrcode = base64_encode(
            $writer->writeString(
                $coded,
                Encoder::DEFAULT_BYTE_MODE_ECODING,
                $this->ecLevel
            )
        );

        return Html::img("data:image/{$this->fmt};base64," . $qrcode, array_merge(['alt' => $this->alt], $this->imgopts));
    }
}
