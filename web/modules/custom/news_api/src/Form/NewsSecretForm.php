<?php

namespace Drupal\news_api\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Config form to set secret_key for admin.
 */
class NewsSecretForm extends ConfigFormBase {

  /**
   * Gets value from config.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Contructs the object of the class.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Object of config factory to get and set values in form.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'news_api_config_page';
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return ['news_api.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state = NULL) {
    $config = $this->configFactory->get('form_api.settings');
    $form = [];
    $form['secret_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Secret Key'),
      '#description' => $this->t('Enter The Secret Key'),
      '#required' => TRUE,
      '#default_value' => $config->get('secret_key') ?? '',
      '#suffix' => "<div id='full-name-result' class='red'></div>",
    ];
    $form['actions'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#ajax' => [
        'callback' => '::submitUsingAjax',
        'progress' => [
          'type' => 'throbber',
          'message' => NULL,
        ],
      ],
      '#suffix' => "<div id='submitted'></div>",
    ];

    return $form;
  }

  /**
   * Validates the form using AJAX and submits it.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The AJAX response.
   */
  public function submitUsingAjax(array &$form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $response = new AjaxResponse();
    if ($triggering_element['#type'] === 'submit') {
      $config = $this->configFactory()->getEditable('news_api.settings');
      $config->set('secret_key', $form_state->getValue('secret_key'));
      $config->save();
      $response->addCommand(new HtmlCommand('#submitted', $this->t('Thanks! For Submitting The Form.')));
    }
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Left Empty as submit is done using AJAX.
  }

}
