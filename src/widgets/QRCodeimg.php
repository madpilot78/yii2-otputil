<?php

namespace mad\otputil\widgets;

use Yii;
use yii\base\Widget;
use yii\helpers\Html;
use yii\web\ServerErrorHttpException;
use mad\otputil\models\Secret;
use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Encoder\Encoder;
use BaconQrCode\Renderer\Image as ImageRenderer;
use BaconQrCode\Writer as QRCWriter;
use chillerlan\Authenticator\Base32;

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

    public function init()
    {
        parent::init();

        if ($this->sid === null) {
            throw new ServerErrorHttpException("Missing Secret ID");
        }

        if (!in_array($this->fmt, ['eps', 'png', 'svg'])) {
            throw new ServerErrorHttpException("Invalid image format");
        }

        if (!in_array($this->ecLevel, ['L', 'M', 'Q', 'H'])) {
            throw new ServerErrorHttpException("Invalid error correction level");
        }

        $this->secret = Secret::findOne($this->sid);
        if (is_null($this->secret)) {
            throw new ServerErrorHttpException("Secret not found");
        }
    }

    public function run()
    {
        $base32 = new Base32();
        $renderformat = 'ImageRenderer\\' . strtoupper($this->fmt[0]);
        $renderer = new $renderformat();
        $renderer->setHeight($this->height);
        $renderer->setWidth($this->width);
        $writer = new QRCWriter($renderer);
        $qrcode = base64_encode(
            $writer->writeString(
                $base32->toString($this->secret),
                Encoder::DEFAULT_BYTE_MODE_ECODING,
                ErrorCorrectionLevel::$ecLevel
            )
        );

        return Html::img("data:image/{$this->fmt};base64," . $qrcode, array_merge(['alt' => $this->alt], $this->imgopts));
    }
}
