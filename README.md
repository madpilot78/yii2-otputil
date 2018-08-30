# Yii2 OTP Util

[![Latest Stable Version](https://img.shields.io/packagist/v/madpilot78/yii2-otputil.svg)](https://packagist.org/packages/madpilot78/yii2-otputil)
[![Total Downloads](https://img.shields.io/packagist/dt/madpilot78/yii2-otputil.svg)](https://packagist.org/packages/madpilot78/yii2-otputil)
[![license](https://img.shields.io/github/license/madpilot78/yii2-otputil.svg)](https://github.com/madpilot78/yii2-otputil)
[![Build Status](https://api.travis-ci.org/madpilot78/yii2-otputil.png?branch=master)](http://travis-ci.org/madpilot78/yii2-otputil)
[![Coverage Status](https://coveralls.io/repos/github/madpilot78/yii2-otputil/badge.svg?branch=master)](https://coveralls.io/github/madpilot78/yii2-otputil?branch=master)
[![StyleCI Status](https://github.styleci.io/repos/125235434/shield?branch=master&style=flat)](https://github.styleci.io/repos/125235434)

Yii2 extension to manage TOTP/HOTP secrets and scratch codes (backup codes) providing it's own tables and related models.

It has a simple and straightforward API.

## Installation

### Composer

```sh
php composer.phar require "madpilot78/yii2-otputil"
```

or add

```
"madpilot78/yii2-otputil": "~0.3.0"
```

to the require section of your composer.json.

## Usage

Configure the component:

```php
return [
    'components' => [
        'otp' => [
            'class' => 'madpilot78\otputil\components\OTP', /* all other lines optional, defaults shown */
            'digits' => 6,
            'mode' => 'totp',
            'algo' => 'SHA1',
            'period' => '30',
            'scratchnum' => 5,
            'slip' => 2,
            'unconfirmedTimeout' => 900,
            'gcChance' => 5,
        ],
    ]
]
```

Run the migration in ```src/migrations/m180128_141512_init.php```.

To get a new secret use:

```php
$otp = Yii::$app->otp;
$unconfirmed_secret = $otp->create();
```

This will creates an OTP secret to be used as configured in the app, will also create ```scratchnum``` scratch codes and returns the ID of the created secret

Unconfirmed secrets may be deleted after ```unconfirmedTimeout``` seconds with ```gcChance``` percent probability

The ID of the unconfirmed secret should be saved while a confirmation window with a QRCode and the scratch codes is shown to the user. A simple widget is provided for the QRcode, it's a simple wrapper around the img tag:

```php
/* @var $user app\models\User */

<?= QRCodeimg::widget([
    'sid' => $unconfirmed_secret,
    'size' => 256,
    'username' => $user->username,
    'label' => 'foo',
    'issuer' => 'bar'
]) ?>
```

After the user successfully confirms the OTP by inserting a correct code in a confirmation  page the secret should be confirmed:

```php
$otp = Yii::$app->otp;
$otp->get($unconfirmed_secret);
$otp->confirm($otp);
```

The confirm method will check the provided OTP against the known secret, algorithm and so on, and will return true/false.

I suggest to save the unconfirmed ID in the session to pass around the confirmation page, after confirmation it should be saved in your database, for example in the user table and related model.

To perform the check for the OTP you can simply do:

```php
/* @var $user app\models\User */
/* @var $this app\models\LoginForm */

$otp = Yii::$app->otp;
if (!(
  $user &&
  $otp->get($user->otp_secret_id) &&
  $otp->verify($this->totp)
)) {
    $this->addError($attribute, 'Invalid code.');
}
```

the verify method will also check for scratch codes and if one is used will delete it from the DB.

When a user disables OTP for his account or gets deleted, or if for any other reason you want to be done with an OTP just remove it:

```php
/* @var $user app\models\User */
/* @var $delete are we deleting the user? */

$otp = Yii::$app->otp;
$otp->get($user->otp_secret_id);
$otp->forget();

if ($delete) {
    $user->delete();
} else {
    $user->otp_secret_id = null;
    $user->save();
}
```

To get the actual secret as a BASE32 encoded string use the ```getSecret()``` method;

To get the scratch codes as an array of codes use the ```getScratches()``` method;

Scratch codes can be all invalidated using ```invalidateScratches()```. ```forget()``` will automatically do this;

You can force the scratch codes to be regenerated with ```regenerateScrathes()```, which will call ```invalidateScratches()```;

To check if a secret has been confirmed use the ```isConfirmed()``` method:

```php
/* @var $user app\models\User */

$otp = Yii::$app->otp;
$otp->get($user->otp_secret_id);
$base32_secret  = $otp->getSecret();
$scratchcodes_array = $otp->getScratches();
$confirmed_bool = $otp->isConfirmed();
$otp->regenerateScrathes();
```
