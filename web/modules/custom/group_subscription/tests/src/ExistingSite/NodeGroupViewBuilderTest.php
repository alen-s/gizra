<?php

namespace Drupal\Tests\group_subscription\ExistingSite;

use Drupal\og\Entity\OgRole;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tests the group node view builder.
 */
class NodeGroupViewBuilderTest extends GroupSubscriptionTestBase {

  /**
   * Tests viewing group nodes.
   */
  public function testViewGroup() {
    // Create a user.
    $user = $this->createUser();
    
    // Create a group node.
    $group = $this->createGroupNode();
    
    // Allow subscription.
    $role = OgRole::getRole('node', 'group', OgRole::ANONYMOUS);
    if ($role) {
      $role->grantPermission('subscribe')->save();
    }
    
    // Login as user.
    $this->drupalLogin($user);
    
    // View the node.
    $this->drupalGet($group->toUrl()->toString());
    $this->assertSession()->statusCodeEquals(Response::HTTP_OK);
    
    // Basic assertions about the page.
    $this->assertSession()->pageTextContains($group->getTitle());
  }
  
  /**
   * Tests group owner view.
   */
  public function testGroupOwnerView() {
    // Create a user.
    $owner = $this->createUser();
    
    // Create a group node owned by this user.
    $group = $this->createNode([
      'title' => 'Owner Group',
      'type' => 'group',
      'uid' => $owner->id(),
      'status' => 1,
    ]);
    
    // Define as group.
    if (!\Drupal\og\Og::isGroup('node', $group->bundle())) {
      \Drupal\og\Og::groupTypeManager()->addGroup('node', $group->bundle());
    }
    
    // Login as owner.
    $this->drupalLogin($owner);
    
    // View the node.
    $this->drupalGet($group->toUrl()->toString());
    $this->assertSession()->statusCodeEquals(Response::HTTP_OK);
  }

}