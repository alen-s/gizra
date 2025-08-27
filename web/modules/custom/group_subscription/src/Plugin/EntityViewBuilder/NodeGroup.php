<?php

namespace Drupal\group_subscription\Plugin\EntityViewBuilder;

use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\og\MembershipManagerInterface;
use Drupal\og\OgAccessInterface;
use Drupal\pluggable_entity_view_builder\EntityViewBuilderPluginAbstract;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The "Node Group" plugin.
 *
 * @EntityViewBuilder(
 *   id = "node.group",
 *   label = @Translation("Node - Group"),
 *   description = "Node view builder for Group bundle."
 * )
 */
class NodeGroup extends EntityViewBuilderPluginAbstract {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * The language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected LanguageManagerInterface $languageManager;

  /**
   * The OG access service.
   *
   * @var \Drupal\og\OgAccessInterface
   */
  protected $ogAccess;

  /**
   * The OG membership manager service.
   *
   * @var \Drupal\og\MembershipManagerInterface
   */
  protected $membershipManager;

  /**
   * Abstract constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\og\OgAccessInterface $og_access
   *   The access manager.
   * @param \Drupal\og\MembershipManagerInterface $membership_manager
   *   The membership manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, AccountInterface $current_user, EntityRepositoryInterface $entity_repository, LanguageManagerInterface $language_manager, OgAccessInterface $og_access, MembershipManagerInterface $membership_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $current_user, $entity_repository, $language_manager);
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
    $this->entityRepository = $entity_repository;
    $this->languageManager = $language_manager;
    $this->ogAccess = $og_access;
    $this->membershipManager = $membership_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('current_user'),
      $container->get('entity.repository'),
      $container->get('language_manager'),
      $container->get('og.access'),
      $container->get('og.membership_manager')
    );
  }

  /**
   * Build full view mode.
   *
   * @param array $build
   *   The existing build.
   * @param \Drupal\node\NodeInterface $entity
   *   The entity.
   *
   * @return array
   *   Render array.
   */
  public function buildFull(array $build, NodeInterface $entity) {
    $account = $this->currentUser;

    // Only for authenticated users.
    if ($account->isAuthenticated()) {
      $elements = [];
      // Set required entity information for proper rendering
      $elements['#entity_type'] = 'node';
      $elements['#entity'] = $entity;
      $elements['#node'] = $entity;

      // Check if user is already a member.
      /** @var \Drupal\og\MembershipManagerInterface $membership_manager */
      $membership_manager = \Drupal::service('og.membership_manager');
      $is_member = $membership_manager->isMember($entity, $account->id());

      // Check if the user is a group manager.
      if ($is_member) {
        if ($entity->getOwnerId() == $account->id()) {
          // User is the group manager.
          $elements['content'] = [
            '#type' => 'html_tag',
            '#tag' => 'span',
            '#attributes' => [
              'title' => $this->t('You are the group manager'),
              'class' => ['group', 'manager'],
            ],
            '#value' => $this->t('You are the group manager'),
          ];
        }
        else {
          $elements['content'] = [
            '#type' => 'html_tag',
            '#tag' => 'span',
            '#attributes' => [
              'title' => $this->t('You are already in the group'),
              'class' => ['group', 'member'],
            ],
            '#value' => $this->t('You are already in the group'),
          ];
        }

        return $elements;
      } else {
        // Check if user is allowed to subscribe to this group.
        if ($this->ogAccess->userAccess($entity, 'subscribe', $account)->isAllowed()) {
          $url = Url::fromRoute('group_subscription.subscribe', [
            'node' => $entity->id(),
          ]);
          $link = Link::fromTextAndUrl(
            $this->t('Hi @name, click here if you would like to subscribe to this group called @label', [
              '@name' => $account->getDisplayName(),
              '@label' => $entity->label(),
            ]),
            $url
          )->toRenderable();

          $link['#attributes']['class'][] = 'subscribe-link';
          $build['subscribe_message'] = $link;
        }
      }
    }

    return $build;
  }
}
