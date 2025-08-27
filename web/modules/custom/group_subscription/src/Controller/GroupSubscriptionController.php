<?php

namespace Drupal\group_subscription\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\og\MembershipManagerInterface;
use Drupal\og\Og;
use Drupal\og\OgAccessInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class GroupSubscriptionController extends ControllerBase {

  /**
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * @var \Drupal\og\OgAccessInterface
   */
  protected $ogAccess;

  /**
   * @var \Drupal\og\MembershipManagerInterface
   */
  protected $membershipManager;

  public function __construct(AccountProxyInterface $current_user, OgAccessInterface $og_access, MembershipManagerInterface $membershipManager) {
    $this->currentUser = $current_user;
    $this->ogAccess = $og_access;
    $this->membershipManager = $membershipManager;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('og.access'),
      $container->get('og.membership_manager')
    );
  }

  /**
   * Subscribe current user to a group node.
   */
  public function subscribe(NodeInterface $node) {
    if (!Og::isGroup('node', $node->bundle())) {
      throw new AccessDeniedHttpException();
    }

    $user = $this->entityTypeManager()->getStorage('user')->load($this->currentUser->id());

    if ($user->isAnonymous()) {
      $destination = $this->getDestinationArray();
      $user_login_url = Url::fromRoute('user.login', [], $destination)->toString();

      if ($this->config('user.settings')->get('register') === UserInterface::REGISTER_ADMINISTRATORS_ONLY) {
        $params = [':login' => $user_login_url];
        $this->messenger()->addMessage($this->t('In order to join any group, you must <a href=":login">login</a>. After you have successfully done so, you will need to request membership again.', $params));
      }
      else {
        $user_register_url = Url::fromRoute('user.register', [], $destination)->toString();
        $params = [
          ':register' => $user_register_url,
          ':login' => $user_login_url,
        ];
        $this->messenger()->addMessage($this->t('In order to join any group, you must <a href=":login">login</a> or <a href=":register">register</a> a new account. After you have successfully done so, you will need to request membership again.', $params));
      }

      return new RedirectResponse(Url::fromRoute('user.page')->setAbsolute(TRUE)->toString());
    }

    $params = [
      '@user' => $user->getDisplayName(),
      '@group' => $node->access('view', $user) ? $node->label() : $this->t('Private group'),
    ];

    if (Og::isMemberBlocked($node, $user)) {
      throw new AccessDeniedHttpException();
    }

    if (Og::isMemberPending($node, $user)) {
      $this->messenger()->addWarning($this->t('You already have a pending membership for the group @group.', $params));
      return new RedirectResponse($node->toUrl()->setAbsolute(TRUE)->toString());
    }

    if (Og::isMember($node, $user)) {
      $this->messenger()->addWarning($this->t('You are already a member of the group @group.', $params));
      return new RedirectResponse($node->toUrl()->setAbsolute(TRUE)->toString());
    }

    $subscribe = $this->ogAccess->userAccess($node, 'subscribe');
    $subscribe_without_approval = $this->ogAccess->userAccess($node, 'subscribe without approval');

    if (!$subscribe->isAllowed() && !$subscribe_without_approval->isAllowed()) {
      throw new AccessDeniedHttpException();
    }

    $membership = $this->membershipManager->createMembership($node, $user);
    $membership->save();

    $this->messenger()->addMessage($this->t('You are now subscribed to the group @group.', $params));
    return new RedirectResponse($node->toUrl()->setAbsolute(TRUE)->toString());
  }

}
