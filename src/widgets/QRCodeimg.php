<?php

namespace madpilot78\otputil\widgets;

use Yii;
use yii\base\Widget;
use yii\helpers\Html;
use yii\web\ServerErrorHttpException;
use madpilot78\otputil\models\Secret;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Encoder\Encoder;
use BaconQrCode\Writer as QRCWriter;

class QRCodeimg extends Widget
{
    /**
     * @var imagebackend The name of the actual backend rendering plugin to be used
     */
    private $imagebackend;

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
     * @var int size side size of the image in pixels
     */
    public $size = 384;

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

        if ($this->sid === null) {
            throw new ServerErrorHttpException('Missing Secret ID');
        }

        switch ($this->fmt) {
            // @codeCoverageIgnoreStart
            case 'eps':
                $this->imagebackend = '\BaconQrCode\Renderer\Image\EpsImageBackEnd';
                break;

            case 'png':
                $this->imagebackend = '\BaconQrCode\Renderer\Image\ImagickImageBackEnd';
                break;

            case 'svg':
                $this->imagebackend = '\BaconQrCode\Renderer\Image\SvgImageBackEnd';
                break;
            // @codeCoverageIgnoreEnd

            default:
                throw new ServerErrorHttpException('Invalid image format');
                break; // @codeCoverageIgnore
        }

        switch ($this->ecLevel) {
            // @codeCoverageIgnoreStart
            case 'L':
                $this->ecLevel = ErrorCorrectionLevel::forBits(1);
                break;

            case 'M':
                $this->ecLevel = ErrorCorrectionLevel::forBits(0);
                break;

            case 'Q':
                $this->ecLevel = ErrorCorrectionLevel::forBits(3);
                break;

            case 'H':
                $this->ecLevel = ErrorCorrectionLevel::forBits(2);
                break;
            // @codeCoverageIgnoreEnd

            default:
                throw new ServerErrorHttpException('Invalid error correction level');
                break; // @codeCoverageIgnore
        }
        

        if (strpos($this->label, ':') || strpos($this->username, ':')) {
            throw new ServerErrorHttpException('QRCode label and username cannot contain ":"');
        }

        $this->secret = Secret::findOne($this->sid);
        if (is_null($this->secret)) {
            throw new ServerErrorHttpException('Secret not found');
        }
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        $coded = 'otpauth://' . $this->secret->mode . '/';

        if ($this->label) {
            $coded .= Html::encode($this->label);
        }

        $coded .= ':';

        if ($this->username) {
            $coded .= Html::encode($this->username);
        }

        $coded .= '?';

        $data = [
            'secret' => $this->secret->secret,
            'algorithm' => $this->secret->algo,
            'digits' => $this->secret->digits,
        ];

        if ($this->issuer) {
            $data['issuer'] = Html::encode($this->issuer);
        }

        if ($this->secret->mode == 'totp') {
            $data['period'] = $this->secret->period;
        } elseif ($this->secret->mode == 'hotp') {
            $data['counter'] = $this->secret->counter;
        }

        $coded .= \http_build_query($data);

        $ufmt = $this->fmt;
        $ufmt[0] = strtoupper($ufmt[0]);

        $renderer = new ImageRenderer(
            new RendererStyle($this->size),
            new $this->imagebackend()
        );

        $writer = new QRCWriter($renderer);

        $qrcode = base64_encode(
            $writer->writeString(
                $coded,
                Encoder::DEFAULT_BYTE_MODE_ECODING,
                $this->ecLevel
            )
        );

        return Html::img(
            'data:image/' . $this->fmt . ';base64,' . $qrcode,
            array_merge(
                ['alt' => $this->alt],
                $this->imgopts
            )
        );
    }
}
