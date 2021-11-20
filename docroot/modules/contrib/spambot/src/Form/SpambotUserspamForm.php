<?php

namespace Drupal\spambot\Form;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Batch\BatchBuilder;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Settings form to save the configuration for Spambot.
 */
class SpambotUserspamForm extends ConfigFormBase {

  const SPAMBOT_CONTENT_ACTION_UNPUBLISH = 'unpublish_content';
  const SPAMBOT_CONTENT_ACTION_DELETE = 'delete_content';

  /**
   * This will hold Database connection object.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Service that manages modules in a Drupal installation.
   *
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected $moduleHandler;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * Batch Builder.
   *
   * @var \Drupal\Core\Batch\BatchBuilder
   */
  protected $batchBuilder;

  /**
   * Config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Database\Connection $connection
   *   Constructs a Connection object.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Provides an interface for an entity type and its metadata.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   Interface for classes that manage a set of enabled modules.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   Stores runtime messages sent out to individual users on the page.
   */
  public function __construct(ConfigFactoryInterface $config_factory, Connection $connection, EntityTypeManagerInterface $entityTypeManager, ModuleHandlerInterface $moduleHandler, MessengerInterface $messenger) {
    parent::__construct($config_factory);

    $this->connection = $connection;
    $this->entityTypeManager = $entityTypeManager;
    $this->moduleHandler = $moduleHandler;
    $this->messenger = $messenger;
    $this->batchBuilder = new BatchBuilder();
    $this->config = \Drupal::config('spambot.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('database'),
      $container->get('entity_type.manager'),
      $container->get('module_handler'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'spambot_user_spam_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['spambot.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, UserInterface $user = NULL) {
    $config = $this->config('spambot.settings');
    $key = $config->get('spambot_sfs_api_key');
    $comments_enabled = $this->moduleHandler->moduleExists('comment');
    $node_count = $this->connection->select('node_field_data', 'n')
      ->fields('n', ['nid'])
      ->condition('uid', $user->id())
      ->countQuery()
      ->execute()
      ->fetchField();

    $status = $this->t('This account has @n nodes.', ['@n' => $node_count]);
    if ($comments_enabled) {
      $comment_count = $this->connection->select('comment_field_data', 'c')
        ->fields('c', ['cid'])
        ->condition('uid', $user->id())
        ->countQuery()
        ->execute()
        ->fetchField();

      $status = $this->t('This account has @n nodes and @c comments.', ['@n' => $node_count, '@c' => $comment_count]);
    }

    $form['check'] = [
      '#type' => 'submit',
      '#value' => $this->t('Check if this account matches a known spammer'),
    ];

    $form['action'] = [
      '#type' => 'details',
      '#title' => $this->t('Take action against this account'),
      '#open' => TRUE,
      '#description' => $status,
    ];

    $form['action']['action_content'] = [
      '#type' => 'radios',
      '#options' => [
        static::SPAMBOT_CONTENT_ACTION_UNPUBLISH => $this
          ->t('Unpublish nodes and comments by this account'),
        static::SPAMBOT_CONTENT_ACTION_DELETE => $this
          ->t('Delete nodes and comments by this account'),
      ],
    ];

    $form['action']['report'] = [
      '#type' => 'details',
      '#title' => $this->t('Report this account to www.stopforumspam.com'),
      '#tree' => TRUE,
      '#open' => TRUE,
      '#collapsible' => TRUE,
    ];

    // Fetch a list of reportable nodes.
    $form['action']['report']['nids'] = [];
    $result = $this->connection->select('node_spambot', 'ns')
      ->fields('ns', ['nid', 'hostname'])
      ->condition('ns.uid', $user->id())
      ->orderBy('ns.nid', 'DESC')
      ->range(0, 20)
      ->execute();

    $nid_hostnames = [];
    foreach ($result as $record) {
      $nid_hostnames[$record->nid] = $record->hostname;
    }

    foreach ($nid_hostnames as $nid => $hostname) {
      if ($node = Node::load($nid)) {
        $title = Unicode::truncate(Html::escape($node->getTitle()), 128, TRUE, TRUE);
        $url = Url::fromRoute('entity.node.canonical', ['node' => $nid], ['absolute' => TRUE]);
        $form['action']['report']['nids'][$nid] = [
          '#type' => 'checkbox',
          '#title' => Link::fromTextAndUrl(
              $title,
              $url)->toString() . ' ' . $this->t('(node, ip=@ip)', ['@ip' => $hostname]),
          '#disabled' => !$key,
        ];
      }
    }

    // Fetch a list of reportable comments.
    if ($comments_enabled) {
      $form['action']['report']['cids'] = [];
      $result = $this->connection->select('comment_field_data', 'comment')
        ->fields('comment', ['cid'])
        ->condition('uid', $user->id())
        ->orderBy('cid', 'DESC')
        ->range(0, 20)
        ->execute();

      $cids = [];
      foreach ($result as $record) {
        $cids[$record->cid] = $record->cid;
      }

      foreach ($cids as $cid) {
        /** @var \Drupal\comment\Entity\Comment $comment */
        $comment = $this->entityTypeManager->getStorage('comment')->load($cid);
        if ($comment) {
          $subject = $comment->getSubject();
          $form['action']['report']['cids'][$cid] = [
            '#type' => 'checkbox',
            '#title' => Link::fromTextAndUrl(
                $subject,
                $comment->permalink()
            )
              ->toString() . ' ' . $this->t('(comment, ip=@ip)', ['@ip' => $comment->getHostname()]),
            '#disabled' => !$key,
          ];
        }
      }
    }

    if ($key) {
      $comment_cids = $comments_enabled ? count($form['action']['report']['cids']) : 0;
      $evidence_count = count($form['action']['report']['nids']) + $comment_cids;
      $form['action']['report']['#description'] = $evidence_count
        ? $this->t('Select one or more posts below to report them to www.stopforumspam.com.')
        : $this->t('This account cannot be reported because no evidence or IP address is available.');
    }
    else {
      $url = Url::fromRoute('spambot.settings_form')->toString();
      $form['action']['report']['#description'] = $this->t('An API key from <a href="http://www.stopforumspam.com">www.stopforumspam.com</a> must <a href="@admin-url">be configured</a> to report spammers.', ['@admin-url' => $url]);
    }

    $form['action']['action_to_user'] = [
      '#type' => 'radios',
      '#options' => [
        'block_user' => $this->t('Block this account'),
        'delete_user' => $this->t('Delete this account'),
      ],
    ];

    $form['action']['action'] = [
      '#type' => 'submit',
      '#value' => $this->t('Take action'),
    ];

    $form['uid'] = [
      '#type' => 'value',
      '#value' => $user->id(),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $key_required = (!empty($values['report']['nids']) && count(array_filter($values['report']['nids'])));
    if ($comments_enabled = $this->moduleHandler->moduleExists('comment')) {
      $key_required = (!empty($values['report']['cids']) && count(array_filter($values['report']['cids']))) || $key_required;
    }

    if ($key_required && !$this->config->get('spambot_sfs_api_key')) {
      $form_state->setErrorByName('action',
        $this->t('To report spammers to www.stopforumspam.com, you need to register for an API key at <a href="http://www.stopforumspam.com">www.stopforumspam.com</a> and enter it into the @page.', [
          '@page' => Link::fromTextAndUrl($this->t('spambot settings'), Url::fromRoute('spambot.settings_form'))->toString(),
        ]));
    }
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    /** @var \Drupal\user\UserInterface $account */
    $account = $this->entityTypeManager->getStorage('user')->load($values['uid']);
    $config = \Drupal::config('spambot.settings');

    if ($form_state->getValue('op') == $form_state->getValue('check')) {
      static::checkSubmit($account, $config);
    }
    elseif ($form_state->getValue('op') == $form_state->getValue('action')) {
      static::actionSubmit($form_state, $account, $config, $values);
    }
  }

  /**
   * Function provide functional for "Check" button.
   *
   * @param \Drupal\user\UserInterface|null $account
   *   Account who will checked.
   * @param \Drupal\Core\Config\ImmutableConfig|null $config
   *   Config for get white list ip.
   */
  public function checkSubmit($account, $config) {
    $messages = [];
    $service_down = FALSE;
    $data = [];
    $request = [
      'email' => $account->getEmail(),
      'username' => $account->getAccountName(),
    ];

    if (spambot_sfs_request($request, $data)) {
      if (!empty($data['email']['appears'])) {
        $messages[] = static::sfsRequestDataMessage($request, $data, 'email');
      }

      if (!empty($data['username']['appears'])) {
        $messages[] = static::sfsRequestDataMessage($request, $data, 'username');
      }

      // Check data at whitelist.
      if (spambot_check_whitelist('email', $config, $account->getEmail())) {
        $messages[] = [
          'text' => $this->t("This account's email address placed at your whitelist."),
          'type' => 'status',
        ];
      }
      if (spambot_check_whitelist('username', $config, $account->getAccountName())) {
        $messages[] = [
          'text' => $this->t("This account's username placed at your whitelist."),
          'type' => 'status',
        ];
      }
    }
    else {
      $this->messenger->addMessage($this->t('Error contacting service.'), 'warning');
      $service_down = TRUE;
    }

    // Check IP addresses.
    if (!$service_down) {
      $ips = spambot_account_ip_addresses($account);
      foreach ($ips as $ip) {
        // Skip the loopback interface.
        if ($ip == '127.0.0.1') {
          continue;
        }
        elseif (spambot_check_whitelist('ip', $config, $ip)) {
          $whitelist_ips[] = $ip;
          continue;
        }
        // Make sure we have a valid IPv4 address
        // (the API doesn't support IPv6 yet).
        elseif (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === FALSE) {
          $messages[] = [
            'text' => $this->t('Invalid IP address: @ip. Spambot will not rely on it.', ['@ip' => $ip]),
            'type' => 'warning',
          ];
          continue;
        }

        $request = ['ip' => $ip];
        $data = [];
        if (spambot_sfs_request($request, $data)) {
          if (!empty($data['ip']['appears'])) {
            $messages[] = [
              'text' => $this->t('An IP address !ip used by this account matches %num times.', [
                '!ip' => Link::fromTextAndUrl($ip, Url::fromUri('http://www.stopforumspam.com/search?q=' . $ip)),
                '%num' => $data['ip']['frequency'],
              ]),
              'type' => 'warning',
            ];
          }
        }
        else {
          $this->messenger->addMessage($this->t('Error contacting service.'), 'warning');
          break;
        }
      }

      if (!empty($whitelist_ips)) {
        $messages[] = [
          'text' => $this->t('These IP addresses placed at your whitelist: %ips', ['%ips' => implode(', ', $whitelist_ips)]),
          'type' => 'status',
        ];
      }
    }

    if ($messages) {
      foreach ($messages as $message) {
        $this->messenger->addMessage($message['text'], $message['type']);
      }
    }
    else {
      $this->messenger->addMessage($this->t('No matches against known spammers found.'));
    }
  }

  /**
   * Function add message by field.
   *
   * @param array|null $request
   *   Query.
   * @param array|null $data
   *   Request data.
   * @param string $field
   *   Email or username field.
   *
   * @return array
   *   Message array element containing 'text' and 'type' data.
   */
  public function sfsRequestDataMessage($request, $data, $field) {
    return [
      'text' => $this->t('This account\'s @field address matches %num times: <a href=":href" target="_blank">@field</a>.', [
        '@field' => $field,
        ':href' => "http://www.stopforumspam.com/search?q={$request[$field]}",
        '@field' => $request[$field],
        '%num' => $data[$field]['frequency'],
      ]),
      'type' => 'warning',
    ];
  }

  /**
   * Function provide functional for button "take action".
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   FormState.
   * @param \Drupal\user\UserInterface|null $account
   *   Account who will take action.
   * @param \Drupal\Core\Config\ImmutableConfig|null $config
   *   Config for get api key.
   * @param array|null $values
   *   FormState values.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException|\Drupal\Core\Entity\EntityStorageException
   */
  public function actionSubmit(FormStateInterface $form_state, $account, $config, $values) {
    $comments_enabled = $this->moduleHandler->moduleExists('comment');
    if ($account->id() == 1) {
      $this->messenger->addMessage($this->t('Sorry, taking action against uid 1 is not allowed.'), 'warning');
      return;
    }

    // Prepare some data.
    $nids = $this->connection->select('node_field_data', 'n')
      ->fields('n', ['nid'])
      ->condition('uid', $account->id())
      ->orderBy('nid')
      ->execute()
      ->fetchCol();

    $node_hostnames = [];
    $result = $this->connection->select('node_spambot')
      ->fields('node_spambot', ['nid', 'hostname'])
      ->condition('uid', $account->id())
      ->orderBy('nid', 'DESC')
      ->execute();
    foreach ($result as $record) {
      $node_hostnames[$record->nid] = $record->hostname;
    }

    $cids = [];
    if ($comments_enabled) {
      $cids = $this->connection->select('comment_field_data', 'c')
        ->fields('c', ['cid'])
        ->condition('uid', $account->id(), '=')
        ->orderBy('cid')
        ->execute()
        ->fetchCol();
    }

    // Report posts to www.stopforumspam.com.
    if (!empty($values['report']['nids'])) {
      foreach (array_filter($values['report']['nids']) as $nid => $unused) {
        /** @var \Drupal\node\Entity\Node $node */
        $node = $this->entityTypeManager->getStorage('node')->load($nid);
        if ($node && !empty($node->id())) {
          $body = $node->get('body')->getValue();
          $api_key = $config->get('spambot_sfs_api_key');
          if (spambot_report_account($account, $node_hostnames[$nid], $node->getTitle() . "\n\n" . $body[0]['summary'] . "\n\n" . $body[0]['value'], $api_key)) {
            $this->messenger->addMessage($this->t('Node %title has been reported.', ['%title' => $node->getTitle()]));
          }
          else {
            $this->messenger->addMessage($this->t('There was a problem reporting node %title.', ['%title' => $node->getTitle()]));
          }
        }
      }
    }

    if ($comments_enabled && !empty($values['report']['cids'])) {
      foreach (array_filter($values['report']['cids']) as $cid => $unused) {
        /** @var \Drupal\comment\Entity\Comment $comment */
        $comment = $this->entityTypeManager->getStorage('comment')->load($cid);
        if ($comment && !empty($comment->id())) {
          $body = $comment->get('comment_body')->getValue();
          $api_key = $config->get('spambot_sfs_api_key');
          if (spambot_report_account($account, $comment->getHostname(), $comment->getSubject() . "\n\n" . $body[0]['value'], $api_key)) {
            $this->messenger->addMessage($this->t('Comment %title has been reported.', ['%title' => $comment->getSubject()]));
          }
          else {
            $this->messenger->addMessage($this->t('There was a problem reporting comment %title.', ['%title' => $comment->getSubject()]));
          }
        }
      }
    }

    // Delete nodes and comments.
    if (!empty($values['action_content'])) {
      static::actionUserContent($values, $nids, $cids);
    }

    // Block or delete account.
    if (!empty($values['action_to_user'])) {
      if ($values['action_to_user'] === 'block_user') {
        $status = $account->get('status')->getValue();
        if ($status[0]['value']) {
          $account->set('status', 0);
          $account->save();
          $this->messenger->addMessage($this->t('Account blocked.'));
        }
        else {
          $this->messenger->addMessage($this->t('This account is already blocked.'));
        }
      }
      else {
        // Redirect to user delete form.
        $form_state->setRedirect(
          'entity.user.cancel_form',
          ['user' => $account->id()],
          []
        );
      }
    }
  }

  /**
   * Function apply selected action to content.
   *
   * @param array|null $values
   *   FormState values.
   * @param array|null $nids
   *   Array of nodes id.
   * @param array|null $cids
   *   Array of comments id.
   */
  public function actionUserContent($values, $nids, $cids) {
    if ($values['action_content'] === 'delete_content') {
      if ($nids) {
        static::defaultBatchBuilderSettings();
        $this->batchBuilder->addOperation([$this, 'deleteEntitiesBatch'], [$nids, 'node']);
        $this->batchBuilder->setFinishCallback([$this, 'finishedDeleteEntities']);
      }
      if ($cids) {
        static::defaultBatchBuilderSettings();
        $this->batchBuilder->addOperation([$this, 'deleteEntitiesBatch'], [$cids, 'comment']);
        $this->batchBuilder->setFinishCallback([$this, 'finishedDeleteEntities']);
      }

      if (($nids || $cids)) {
        batch_set($this->batchBuilder->toArray());
      }
    }
    else {
      // Unpublish nodes and content.
      if ($nids) {
        static::defaultBatchBuilderSettings();
        $this->batchBuilder->addOperation([$this, 'entitiesUnpublish'], [$nids, 'node']);
        $this->batchBuilder->setFinishCallback([$this, 'finishedUnpublishEntities']);
      }

      if ($cids) {
        static::defaultBatchBuilderSettings();
        $this->batchBuilder->addOperation([$this, 'entitiesUnpublish'], [$cids, 'comment']);
        $this->batchBuilder->setFinishCallback([$this, 'finishedUnpublishEntities']);
      }

      if (($nids || $cids)) {
        batch_set($this->batchBuilder->toArray());
      }
    }
  }

  /**
   * Function for set default setting for each batch.
   */
  public function defaultBatchBuilderSettings() {
    $this->batchBuilder
      ->setTitle($this->t('Processing'))
      ->setInitMessage($this->t('Initializing.'))
      ->setProgressMessage($this->t('Completed @current of @total.'))
      ->setErrorMessage($this->t('An error has occurred.'));

    $this->batchBuilder->setFile(drupal_get_path('module', 'spambot') . '/src/Form/SpambotUserspamForm.php');
  }

  /**
   * Processor for delete batch operations.
   *
   * @param array|null $items
   *   Array of entities id.
   * @param string $type
   *   Entity type.
   * @param array $context
   *   Batch data.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function deleteEntitiesBatch($items, $type, array &$context) {
    // Elements per operation.
    $limit = 50;
    // Set default progress values.
    if (empty($context['sandbox']['progress'])) {
      $context['sandbox']['progress'] = 0;
      $context['sandbox']['max'] = count($items);
    }

    if (empty($context['sandbox']['items'])) {
      $context['sandbox']['items'] = $items;
    }

    $counter = 0;
    if (!empty($context['sandbox']['items'])) {
      // Remove already processed items.
      if ($context['sandbox']['progress'] != 0) {
        array_splice($context['sandbox']['items'], 0, $limit);
      }

      foreach ($context['sandbox']['items'] as $item) {
        if ($counter !== $limit) {
          static::deleteEntity($item, $type);
          $counter++;
          $context['sandbox']['progress']++;

          $context['message'] = $this->t('Now processing :entity :progress of :count', [
            ':entity' => $type,
            ':progress' => $context['sandbox']['progress'],
            ':count' => $context['sandbox']['max'],
          ]);

          $context['results']['processed'] = $context['sandbox']['progress'];
        }
      }
    }

    if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
      $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
    }
  }

  /**
   * Function for delete entity.
   *
   * @param int $id
   *   Entity id.
   * @param string $type
   *   Entity type.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function deleteEntity($id, $type) {
    $storage = $this->entityTypeManager->getStorage($type);
    $entity = $storage->load($id);
    if (!empty($entity)) {
      $entity->delete();
      // Delete from node_spambot table.
      if ($type === 'node') {
        $this->connection->delete('node_spambot')
          ->condition('nid', $id)
          ->execute();
      }
    }
  }

  /**
   * Finished callback for delete entities batch.
   */
  public function finishedDeleteEntities() {
    $message = $this->t('Entities have been deleted.');

    $this->messenger()
      ->addStatus($message);
  }

  /**
   * Processor for unpublish batch operations.
   *
   * @param array|null $items
   *   Array of entities id.
   * @param string $type
   *   Type of entity.
   * @param array $context
   *   Batch data.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function entitiesUnpublish($items, $type, array &$context) {
    // Elements per operation.
    $limit = 50;

    if (empty($context['sandbox']['progress'])) {
      $context['sandbox']['progress'] = 0;
      $context['sandbox']['max'] = count($items);
    }

    if (empty($context['sandbox']['items'])) {
      $context['sandbox']['items'] = $items;
    }

    $counter = 0;
    if (!empty($context['sandbox']['items'])) {
      // Remove already processed items.
      if ($context['sandbox']['progress'] != 0) {
        array_splice($context['sandbox']['items'], 0, $limit);
      }

      foreach ($context['sandbox']['items'] as $item) {
        if ($counter != $limit) {
          $entity = $this->entityTypeManager->getStorage($type)
            ->load($item);
          $entity->setPublished(FALSE);
          $entity->save();

          $counter++;
          $context['sandbox']['progress']++;

          $context['message'] = $this->t('Now processing :entity :progress of :count', [
            ':entity' => $type,
            ':progress' => $context['sandbox']['progress'],
            ':count' => $context['sandbox']['max'],
          ]);

          $context['results']['processed'] = $context['sandbox']['progress'];
        }
      }
    }

    if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
      $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
    }
  }

  /**
   * Finished callback for unpublish entities batch.
   */
  public function finishedUnpublishEntities() {
    $message = $this->t('Objects have been retired.');

    $this->messenger()
      ->addStatus($message);
  }

}
