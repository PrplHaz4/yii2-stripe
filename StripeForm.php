<?php

/**
 * @copyright Copyright Victor Demin, 2014
 * @license https://github.com/ruskid/yii2-stripe/LICENSE
 * @link https://github.com/ruskid/yii2-stripe#readme
 */

namespace ruskid\stripe;

use Yii;
use yii\helpers\Html;
use yii\web\JsExpression;

/**
 * Yii stripe custom form.
 * https://stripe.com/docs/tutorials/forms
 *
 * @author Victor Demin <demmbox@gmail.com>
 */
class StripeForm extends \yii\widgets\ActiveForm {

    /**
     * @see Stripe's javascript location
     * @var string url to stripe's javascript
     */
    public $stripeJs = 'https://js.stripe.com/v2/';

    /**
     * Js Expression that will handle the response.
     *
     * If not set the default behavior will be used:
     * function stripeResponseHandler(status, response) {
     *      var $form = $('#payment-form');
     *        if (response.error) {
     *           // Show the errors on the form
     *           $form.find('.payment-errors').text(response.error.message);
     *           $form.find('button').prop('disabled', false);
     *        } else {
     *           // response contains id and card, which contains additional card details
     *           var token = response.id;
     *           // Insert the token into the form so it gets submitted to the server
     *           $form.append($('<input type="hidden" name="stripeToken" />').val(token));
     *           // and submit
     *           $form.get(0).submit();
     *        }
     * }
     *
     * @var JsExpression
     */
    public $stripeResponseHandler;

    /**
     * Js Expression that will handle the request.
     *
     * If not set the default behavior will be used:
     * jQuery(function($) {
     *    $('#payment-form').submit(function(event) {
     *         var $form = $(this);
     *         // Disable the submit button to prevent repeated clicks
     *         $form.find('button').prop('disabled', true);
     *          Stripe.card.createToken($form, stripeResponseHandler);
     *          // Prevent the form from submitting with the default action
     *          return false;
     *      });
     *   });
     *
     * @var JsExpression
     */
    public $stripeRequestHandler;

    /**
     * Input id and name tags of the hidden token input that will be sent to PayAction.
     * @var string
     */
    public $tokenInputName = 'stripeToken';

    /**
     * If the default behavior for the response is used, then you can set the id of error's container.
     * Note! this property is useless if you set your own response handler.
     * @var string
     */
    public $errorContainerId = "payment-errors";

    /**
     * Apply Jquery Payment format to the inputs
     * @see https://github.com/stripe/jquery.payment.
     * @var boolean
     */
    public $applyJqueryPaymentFormat = true;

    /**
     * Perform Jquery Payment client validation.
     * @var boolean
     */
    public $applyJqueryPaymentValidation = true;

    //Stripe constants
    const NUMBER_ID = 'number';
    const CVC_ID = 'cvc';
    const MONTH_ID = 'exp-month';
    const YEAR_ID = 'exp-year';
    const MONTH_YEAR_ID = 'exp-month-year'; //actually not stripe =)
    //Autofill spec. @see https://html.spec.whatwg.org/multipage/forms.html
    const AUTO_CC_ATTR = 'cc-number';
    const AUTO_EXP_ATTR = 'cc-exp';
    const AUTO_MONTH_ATTR = 'cc-exp-month';
    const AUTO_YEAR_ATTR = 'cc-exp-year';

    /**
     * @see Init extension default
     */
    public function init() {
        parent::init();

        //Set default response behavior
        if (!isset($this->stripeResponseHandler)) {
            $this->stripeResponseHandler = 'function stripeResponseHandler(status, response) {
                    var $form = $("#' . $this->options['id'] . '");
                    if (response.error) {
                        $form.find("#' . $this->errorContainerId . '").text(response.error.message);
                        $form.find("button").prop("disabled", false);
                    } else {
                        var token = response.id;
                        $form.append($("<input type=\"hidden\" name=\"' . $this->tokenInputName . '\" id=\"' . $this->tokenInputName . '\" />").val(token));
                        $form.get(0).submit();
                    }
            };';
        }

        //Set default request behavior
        if (!isset($this->stripeRequestHandler)) {
            $this->stripeRequestHandler = 'jQuery(function($) {
                $("#' . $this->options['id'] . '").submit(function(event) {
                    var $form = $(this);
                    $form.find("button").prop("disabled", true);
                    Stripe.card.createToken($form, stripeResponseHandler);
                    return false;
                });
            });';
        }
    }

    /**
     * Will show the Stripe's simple form modal
     */
    public function run() {
        $this->registerFormScripts();
        if ($this->applyJqueryPaymentFormat || $this->applyJqueryPaymentValidation) {
            $this->registerJqueryPaymentScripts();
        }
    }

    /**
     * Will register mandatory javascripts to work
     */
    public function registerFormScripts() {
        $view = $this->getView();
        $view->registerJsFile($this->stripeJs, ['position' => \yii\web\View::POS_HEAD]);

        $js = "Stripe.setPublishableKey('" . Yii::$app->stripe->publicKey . "');";
        $view->registerJs($js, \yii\web\View::POS_BEGIN);

        //form scripts
        $view->registerJs($this->stripeResponseHandler, \yii\web\View::POS_READY);
        $view->registerJs($this->stripeRequestHandler, \yii\web\View::POS_READY);
    }

    /**
     * Will register Jquery Payment scripts
     */
    public function registerJqueryPaymentScripts() {
        $view = $this->getView();
        JqueryPaymentAsset::register($view);

        if ($this->applyJqueryPaymentFormat) {
            $js = "jQuery(function($) {
                $('input[data-stripe=" . self::NUMBER_ID . "]').payment('formatCardNumber');
                $('input[data-stripe=" . self::CVC_ID . "]').payment('formatCardCVC');
                $('input[data-stripe=" . self::MONTH_YEAR_ID . "]').payment('formatCardExpiry');
                $('input[data-stripe=" . self::MONTH_ID . "]').payment('restrictNumeric');
                $('input[data-stripe=" . self::YEAR_ID . "]').payment('restrictNumeric');
            });";
            $view->registerJs($js);
        }

        if ($this->applyJqueryPaymentValidation) {
            /* $js = "jQuery(function($) {
              $.fn.toggleInputError = function(erred) {
              this.parent('.form-group').toggleClass('has-error', erred);
              return this;
              };

              $('form').submit(function(e) {
              e.preventDefault();
              var cardType = $.payment.cardType($('.cc-number').val());
              $('.cc-number').toggleInputError(!$.payment.validateCardNumber($('.cc-number').val()));
              $('.cc-exp').toggleInputError(!$.payment.validateCardExpiry($('.cc-exp').payment('cardExpiryVal')));
              $('.cc-cvc').toggleInputError(!$.payment.validateCardCVC($('.cc-cvc').val(), cardType));
              $('.cc-brand').text(cardType);
              $('.validation').removeClass('text-danger text-success');
              $('.validation').addClass($('.has-error').length ? 'text-danger' : 'text-success');
              });
              });"; */
        }
    }

    /**
     * Will generate card number input
     * @param array $options
     * @return string genetared input tag
     */
    public function numberInput($options = []) {
        if (empty($options)) {
            $options = [
                'id' => self::NUMBER_ID,
                'class' => 'form-control',
                'autocomplete' => self::AUTO_CC_ATTR,
                'placeholder' => '•••• •••• •••• ••••',
                'required' => true,
                'type' => 'tel',
                'size' => 20
            ];
        } else {
            StripeHelper::secCheck($options);
        }
        $options['data-stripe'] = self::NUMBER_ID;
        return Html::input('text', null, null, $options);
    }

    /**
     * Will generate cvc input
     * @param array $options
     * @return string genetared input tag
     */
    public function cvcInput($options = []) {
        if (empty($options)) {
            $options = [
                'id' => self::CVC_ID,
                'class' => 'form-control',
                'autocomplete' => 'off',
                'placeholder' => '•••',
                'required' => true,
                'type' => 'tel',
                'size' => 4
            ];
        } else {
            StripeHelper::secCheck($options);
        }
        $options['data-stripe'] = self::CVC_ID;
        return Html::input('text', null, null, $options);
    }

    /**
     * Will generate year input
     * @param array $options
     * @return string genetared input tag
     */
    public function yearInput($options = []) {
        if (empty($options)) {
            $options = [
                'id' => self::YEAR_ID,
                'class' => 'form-control',
                'autocomplete' => self::AUTO_YEAR_ATTR,
                'placeholder' => '••••',
                'required' => true,
                'type' => 'tel',
                'maxlength' => 4,
                'size' => 4
            ];
        } else {
            StripeHelper::secCheck($options);
        }
        $options['data-stripe'] = self::YEAR_ID;
        return Html::input('text', null, null, $options);
    }

    /**
     * Will generate month input
     * @param array $options
     * @return string genetared input tag
     */
    public function monthInput($options = []) {
        if (empty($options)) {
            $options = [
                'id' => self::MONTH_ID,
                'class' => 'form-control',
                'autocomplete' => self::AUTO_MONTH_ATTR,
                'placeholder' => '••',
                'required' => true,
                'type' => 'tel',
                'maxlength' => 2,
                'size' => 2
            ];
        } else {
            StripeHelper::secCheck($options);
        }
        $options['data-stripe'] = self::MONTH_ID;
        return Html::input('text', null, null, $options);
    }

    /**
     * Will generate month and year input. Like in Jquery Payment example.
     * @param array $options
     * @return string genetared input tag
     */
    public function monthAndYearInput($options = []) {
        if (empty($options)) {
            $options = [
                'id' => self::MONTH_YEAR_ID,
                'class' => 'form-control',
                'autocomplete' => self::AUTO_EXP_ATTR,
                'placeholder' => '•• / ••',
                'required' => true,
                'type' => 'tel',
            ];
        } else {
            StripeHelper::secCheck($options);
        }
        $options['data-stripe'] = self::MONTH_YEAR_ID;
        return Html::input('text', null, null, $options);
    }

}
