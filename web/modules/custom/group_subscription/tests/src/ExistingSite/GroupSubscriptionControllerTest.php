<?php

namespace Drupal\Tests\group_subscription\ExistingSite;

use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\BrowserTestBase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Simple tests for GroupSubscriptionController.
 */
class GroupSubscriptionControllerTest extends BrowserTestBase {

  /**
   * Use a core theme for testing.
   *
   * @var string
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'node',
    'user',
    'og',
    'group_subscription',
  ];

  /**
   * Helper: ensure the group content type exists and is registered as a group.
   */
  protected function createGroupBundle(string $bundle = 'group') {
    if (!NodeType::load($bundle)) {
      $node_type = NodeType::create([
        'type' => $bundle,
        'name' => 'Group',
      ]);
      $node_type->save();
    }
    \Drupal::service('og.group_type_manager')->addGroup('node', $bundle);
    return $bundle;
  }

  /**
   * Test subscribing to a group node as an authenticated user.
   */
  public function testSubscribeAsAuthenticatedUser() {
    // Ensure the 'group' content type exists and is registered as a group.
    $this->createGroupBundle('group');

    // Now safely create the group node.
    $group_node = $this->createNode([
      'type' => 'group',
      'title' => 'Test Group',
    ]);

    // Create an authenticated user.
    $authenticated_user = $this->drupalCreateUser();

    // Create a group manager user and set ownership of the node.
    $group_manager = $this->drupalCreateUser();
    $group_node->setOwnerId($group_manager->id());
    $group_node->save();

    // Test as an anonymous user.
    $this->drupalGet('node/' . $group_node->id());
    $this->assertSession()->pageTextContains('click here if you would like to subscribe to this group');

    // Test as an authenticated user (not subscribed).
    $this->drupalLogin($authenticated_user);
    $this->drupalGet('node/' . $group_node->id());
    $this->assertSession()->pageTextContains('Hi ' . $authenticated_user->getDisplayName() . ', click here if you would like to subscribe to this group called Test Group');

    // Subscribe the authenticated user to the group.
    $membership_manager = \Drupal::service('og.membership_manager');
    $membership_manager->createMembership($group_node, $authenticated_user)->save();

    // Test as an authenticated user (subscribed).
    $this->drupalGet('node/' . $group_node->id());
    $this->assertSession()->pageTextContains('You are already in the group');

    // Test as the group manager.
    $this->drupalLogin($group_manager);
    $this->drupalGet('node/' . $group_node->id());
    $this->assertSession()->pageTextContains('You are the group manager');
  }
}
