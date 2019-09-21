<?php

namespace Drupal\Composer\Plugin\Scaffold;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Installer\PackageEvent;
use Composer\Installer\PackageEvents;
use Composer\Plugin\Capability\CommandProvider;
use Composer\Plugin\Capable;
use Composer\Plugin\CommandEvent;
use Composer\Plugin\PluginEvents;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;
use Drupal\Composer\Plugin\Scaffold\CommandProvider as ScaffoldCommandProvider;

/**
 * Composer plugin for handling drupal scaffold.
 */
class Plugin implements PluginInterface, EventSubscriberInterface, Capable {

  /**
   * The Composer Scaffold handler.
   *
   * @var \Drupal\Composer\Plugin\Scaffold\Handler
   */
  protected $handler;

  /**
   * {@inheritdoc}
   */
  public function activate(Composer $composer, IOInterface $io) {
    // We use a Handler object to separate the main functionality
    // of this plugin from the Composer API. This also avoids some
    // debug issues with the plugin being copied on initialisation.
    // @see \Composer\Plugin\PluginManager::registerPackage()
    $this->handler = new Handler($composer, $io);
  }

  /**
   * {@inheritdoc}
   */
  public function getCapabilities() {
    return [CommandProvider::class => ScaffoldCommandProvider::class];
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      ScriptEvents::POST_UPDATE_CMD => 'postCmd',
      ScriptEvents::POST_INSTALL_CMD => 'postCmd',
      ScriptEvents::POST_CREATE_PROJECT_CMD => 'postProject',
      PackageEvents::POST_PACKAGE_INSTALL => 'postPackage',
      PluginEvents::COMMAND => 'onCommand',
    ];
  }

  /**
   * Post command event callback.
   *
   * @param \Composer\Script\Event $event
   *   The Composer event.
   */
  public function postCmd(Event $event) {
    $this->handler->scaffold();
  }

  /**
   * Post package event behaviour.
   *
   * @param \Composer\Installer\PackageEvent $event
   *   Composer package event sent on install/update/remove.
   */
  public function postPackage(PackageEvent $event) {
    $this->handler->onPostPackageEvent($event);
  }

  /**
   * Pre command event callback.
   *
   * @param \Composer\Plugin\CommandEvent $event
   *   The Composer command event.
   */
  public function onCommand(CommandEvent $event) {
    if ($event->getCommandName() == 'require') {
      $this->handler->beforeRequire($event);
    }
  }

  public function postProject(Event $event) {
      if (function_exists('posix_isatty') && posix_isatty(STDIN)) {
          echo <<<GITIGNORE_PREFS_DISCUSSION
.gitignore preference
=====================
If you use git to manage your new site, which files are best committed to git
will vary depending on how you deploy changes to your production website.

How you answer this quesiton will only affect which initial .gitignore file we
get you started with; if you are unsure how to answer or don't know how changes
will be deployed to production yet, you can always adjust your .gitignore file
later.

Some people prefer to commit all the files Composer has downloaded into their
git repository, and use git by itself to deploy changes. Others prefer to commit
only their project's unique modules and themes to their repository, and then
configure a more complex deployment strategy.

GITIGNORE_PREFS_DISCUSSION;

          do {
              echo <<<GITIGNORE_PREFS_PROMPT
Would you like to:
  1) Use a .gitignore that includes Composer-managed files in the repository
  2) Use a .gitignore that ignores all locations Composer writes files
  3) Write no .gitignore file now

1-3? 
GITIGNORE_PREFS_PROMPT;

              $input = fgets(STDIN);
          } while (! preg_match('/^(?:1|2|3)\s*/', $input));
      }
  }

}
