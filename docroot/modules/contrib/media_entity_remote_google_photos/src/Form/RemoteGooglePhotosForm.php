<?php

namespace Drupal\media_entity_remote_google_photos\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\media\OEmbed\ResourceFetcherInterface;
use Drupal\media\OEmbed\UrlResolverInterface;
use Drupal\media_library\Form\AddFormBase;
use Drupal\media_library\MediaLibraryUiBuilder;
use Drupal\media_library\OpenerResolverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Creates a form to create media entities from oEmbed URLs.
 *
 * @internal
 *   Form classes are internal.
 */
class RemoteGooglePhotosForm extends AddFormBase {

  /**
   * The oEmbed URL resolver service.
   *
   * @var \Drupal\media\OEmbed\UrlResolverInterface
   */
  protected $urlResolver;

  /**
   * The oEmbed resource fetcher service.
   *
   * @var \Drupal\media\OEmbed\ResourceFetcherInterface
   */
  protected $resourceFetcher;

  /**
   * Constructs a new OEmbedForm.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\media_library\MediaLibraryUiBuilder $library_ui_builder
   *   The media library UI builder.
   * @param \Drupal\media\OEmbed\UrlResolverInterface $url_resolver
   *   The oEmbed URL resolver service.
   * @param \Drupal\media\OEmbed\ResourceFetcherInterface $resource_fetcher
   *   The oEmbed resource fetcher service.
   * @param \Drupal\media_library\OpenerResolverInterface $opener_resolver
   *   The opener resolver.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, MediaLibraryUiBuilder $library_ui_builder, OpenerResolverInterface $opener_resolver = NULL) {
    parent::__construct($entity_type_manager, $library_ui_builder, $opener_resolver);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('media_library.ui_builder'),
      $container->get('media_library.opener_resolver')
    );
  }


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return $this->getBaseFormId() . '_media_entity_remote_google_photos';
  }

  /**
   * {@inheritdoc}
   */
  protected function getMediaType(FormStateInterface $form_state) {
    if ($this->mediaType) {
      return $this->mediaType;
    }

    $media_type = parent::getMediaType($form_state);
//    if (!$media_type->getSource() instanceof OEmbedInterface) {
//      throw new \InvalidArgumentException('Can only add media types which use an oEmbed source plugin.');
//    }
    return $media_type;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildInputElement(array $form, FormStateInterface $form_state) {

    // Add a container to group the input elements for styling purposes.
    $form['container'] = [
      '#type' => 'container',
    ];

    $form['container']['url'] = [
      '#type' => 'url',
      '#maxlength' => 2048,
      '#title' => $this->t('Add @type via URL', [
        '@type' => $this->getMediaType($form_state)->label(),
      ]),
      '#description' => $this->t('Google Photos image URL'),
      '#required' => TRUE,
      '#attributes' => [
        'placeholder' => 'https://lh3.googleusercontent.com/',
      ],
    ];

    $form['container']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add'),
      '#button_type' => 'primary',
      '#submit' => ['::addButtonSubmit'],
      // @todo Move validation in https://www.drupal.org/node/2988215
      '#ajax' => [
        'callback' => '::updateFormCallback',
        'wrapper' => 'media-library-wrapper',
        // Add a fixed URL to post the form since AJAX forms are automatically
        // posted to <current> instead of $form['#action'].
        // @todo Remove when https://www.drupal.org/project/drupal/issues/2504115
        //   is fixed.
        'url' => Url::fromRoute('media_library.ui'),
        'options' => [
          'query' => $this->getMediaLibraryState($form_state)->all() + [
            FormBuilderInterface::AJAX_FORM_REQUEST => TRUE,
          ],
        ],
      ],
    ];
    return $form;
  }


  /**
   * Submit handler for the add button.
   *
   * @param array $form
   *   The form render array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function addButtonSubmit(array $form, FormStateInterface $form_state) {
    $this->processInputValues([$form_state->getValue('url')], $form, $form_state);
  }

}
