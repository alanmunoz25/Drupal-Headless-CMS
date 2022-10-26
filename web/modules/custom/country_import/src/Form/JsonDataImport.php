<?php

namespace Drupal\country_import\Form;


use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Psr\Container\ContainerInterface;


/**
 * Class JsonDataImport
 *
 * @package Drupal\country_import\Form
 */
class JsonDataImport extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'country_import_json_import_data';
  }

  /**
   * Current user account.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Node storage
   *
   * @var \Drupal\node\NodeStorageInterface
   */
  protected $nodeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    EntityTypeManager $entityTypeManager,
    AccountProxyInterface $currentUser
  ) {
    $this->nodeManager = $entityTypeManager->getStorage('node');
    $this->currentUser = $currentUser;
    // delete all node city_sector data
//    $result = \Drupal::entityQuery("node")
//      ->condition("type", "city_sector")
//      ->accessCheck(FALSE)
//      ->execute();
//    $storage_handler = \Drupal::entityTypeManager()->getStorage("node");
//    $entities = $storage_handler->loadMultiple($result);
//    $storage_handler->delete($entities);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Get all content types to render in select field
    $entityTypeManager = \Drupal::service('entity_type.manager');
    $contentTypes = [];
    $contentTypesList = $entityTypeManager->getStorage('node_type')->loadMultiple();
    foreach ($contentTypesList as $contentType) {
      $contentTypes[$contentType->id()] = $contentType->label();
    }

    $form['content_type_nodes'] = [
      '#type' => 'select',
      '#title' => $this->t('Content Type'),
      '#options' => $contentTypes,
      '#empty_option' => $this->t('- Select -'),
      '#default_value' => $config['content_type_nodes'] ?? '',
      '#required' => TRUE,
    ];

    $form['data_dump_file'] = [
      '#type' => 'file',
      '#title' => $this->t('Json File'),
      '#upload_validators' => [
        'file_validate_extensions' => ['json'],
      ],
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => 'Process!'
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->processImport($form_state);
  }

  public function processImport(&$form_state) {
    $node_type = $form_state->getValue('content_type_nodes');
    $all_files = $this->getRequest()->files->get('files', []);
    $file = $all_files['data_dump_file'];
    $file_path = $file->getRealPath();

    if (!empty($file_path)) {
      $jsonString = file_get_contents($file_path);
      $data = json_decode($jsonString, true);

      switch ($node_type) {
        case 'city_sector':
          foreach ($data as $key => $value) {
            //echo $key . " " . $value['title'];
            $node = $this->nodeManager->create([
              'type' => 'city_sector',
              'title' => $value['title'],
              'uid' => $this->currentUser->id(),
              'status' => 1
            ]);
            $node->field_city_code->value = $value['field_city_code'];
            $node->field_country_related_code->value = $value['field_country_related_code'];
            $node->save();
          }
          break;
        case 'amenities':

      }


    } else {
      $this->messenger()->addMessage($this->t('Invalid file.'), 'warning');
    }
    $this->messenger()->addMessage($this->t('Succesfully processed.'), 'status');
  }

}
