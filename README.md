Yii2 Stripe Wrapper.
==========
<b>In plans:</b>
Maybe will add PayAction and Jquery Payment library (optional). <br />
https://stripe.com/docs/tutorials/forms <br />
https://github.com/stripe/jquery.payment <br />

Installation
--------------------------

The preferred way to install this extension is through http://getcomposer.org/download/.

Either run

```sh
php composer.phar require ruskid/yii2-stripe "dev-master"
```

or add

```json
"ruskid/yii2-stripe": "dev-master"
```

to the require section of your `composer.json` file.


Usage
--------------------------
Add a new component in main.php
```php
'components' => [
...
'stripe' => [
    'class' => 'ruskid\stripe\Stripe',
    'publicKey' => "pk_test_xxxxxxxxxxxxxxxxxxx",
    'privateKey' => "sk_test_xxxxxxxxxxxxxxxxxx",
],
...

```

To render simple checkout form just call the widget in the view, it will automatically register the scripts.
Check stripe documentation for more options.
```php
use ruskid\stripe\StripeCheckout;

<?= 
StripeCheckout::widget([
    'action' => '/',
    'name' => 'Demo test',
    'description' => '2 widgets ($20.00)',
    'amount' => 2000,
    'image' => '/128x128.png',
]);
?>
```

Custom checkout form is an extended version of simple form, but you can customize the button (see buttonOptions) and handle token as you want (tokenFunction).
```php
use ruskid\stripe\StripeCheckoutCustom;

<?= 
StripeCheckoutCustom::widget([
    'action' => '/',
    'name' => 'Demo test',
    'description' => '2 widgets ($20.00)',
    'amount' => 2000,
    'image' => '/128x128.png',
    'buttonOptions' => [
        'class' => 'btn btn-lg btn-success',
    ],
    'tokenFunction' => new JsExpression('function(token) { 
                alert("Here you should control your token."); 
    }'),
    'openedFunction' => new JsExpression('function() { 
                alert("Model opened"); 
    }'),
    'closedFunction' => new JsExpression('function() { 
                alert("Model closed"); 
    }'),
]);
?>
```
Example of a custom form. StripeForm is an <b>extended ActiveForm</b> so you can perform validation of amount and other attributes you want. 
You can change the token name that will be sent to your action and error's container id. You can also change JsExpression for response and request handlers.

```php
use ruskid\stripe\StripeForm;

 <?php
 $form = StripeForm::begin([
             'tokenInputName' => 'stripeToken',
             'errorContainerId' => 'payment-errors',
             'options' => ['autocomplete' => 'on']
 ]);
 ?>

 <div class="form-group">
     <label for="number" class="control-label">Card number</label>
     <?= $form->numberInput() ?>
 </div>

 <div class="form-group">
     <label for="cvc" class="control-label">CVC</label>
     <?= $form->cvcInput() ?>
 </div>

 <!-- Use month and year in the same input. -->
 <div class="form-group">
     <label for="cvc" class="control-label">Card expiry</label>
     <?= $form->monthAndYearInput() ?>
 </div>

 <!-- OR in two separate inputs. -->
 <div class="form-group">
     <label for="cvc" class="control-label">Month</label>
     <?= $form->monthInput() ?>
 </div>

 <div class="form-group">
     <label for="cvc" class="control-label">Year</label>
     <?= $form->yearInput() ?>
 </div>

 <div id="payment-errors"></div>
 <?php StripeForm::end(); ?>
```

