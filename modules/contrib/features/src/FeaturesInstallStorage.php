<?php

namespace Drupal\features;

use Drupal\Core\Config\ExtensionInstallStorage;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Extension\ExtensionDiscovery;
use Drupal\Core\Extension\ExtensionPathResolver;
use Drupal\Core\Site\Settings;

/**
 * Storage to access configuration and schema in installed extensions.
 *
 * Overrides the normal ExtensionInstallStorage to prevent profile from
 * overriding.
 *
 * Also supports modules that are not installed yet.
 *
 * @see \Drupal\Core\Config\ExtensionInstallStorage
 */
class FeaturesInstallStorage extends ExtensionInstallStorage {

  /**
   * Instance of the extension path resolver service.
   *
   * @var \Drupal\Core\Extension\ExtensionPathResolver
   */
  protected ExtensionPathResolver $extensionPathResolver;

  /**
   * Overrides \Drupal\Core\Config\ExtensionInstallStorage::__construct().
   *
   * Sets includeProfile to FALSE.
   *
   * @param \Drupal\Core\Config\StorageInterface $config_storage
   *   The active configuration store where the list of enabled modules and
   *   themes is stored.
   * @param \Drupal\Core\Extension\ExtensionPathResolver $extension_path_resolver
   *   Instance of the extension path resolver service.
   * @param string $directory
   *   The directory to scan in each extension to scan for files. Defaults to
   *   'config/install'. This parameter will be mandatory in Drupal 9.0.0.
   * @param string $collection
   *   (optional) The collection to store configuration in. Defaults to the
   *   default collection. This parameter will be mandatory in Drupal 9.0.0.
   * @param bool $include_profile
   *   (optional) Whether to include the install profile in extensions to
   *   search and to get overrides from. This parameter will be mandatory in
   *   Drupal 9.0.0.
   * @param string|null $profile
   *   (optional) The current installation profile. This parameter will be
   *   mandatory in Drupal 9.0.0.
   */
  public function __construct(StorageInterface $config_storage, ExtensionPathResolver $extension_path_resolver, $directory = self::CONFIG_INSTALL_DIRECTORY, $collection = StorageInterface::DEFAULT_COLLECTION, $include_profile = TRUE, $profile = NULL) {
    $this->extensionPathResolver = $extension_path_resolver;
    // @todo: determine if we should be setting $include_profile to FALSE.
    parent::__construct($config_storage, $directory, $collection, FALSE, $profile);
  }

  /**
   * Returns a map of all config object names and their folders.
   *
   * The list is based on installed modules and themes. The active
   * configuration storage is used rather than
   * \Drupal\Core\Extension\ModuleHandler and
   * \Drupal\Core\Extension\ThemeHandler in order to resolve circular
   * dependencies between these services and
   * \Drupal\Core\Config\ConfigInstaller and
   * \Drupal\Core\Config\TypedConfigManager.
   *
   * NOTE: This code is copied from ExtensionInstallStorage::getAllFolders()
   * with the following changes (Notes in CHANGED below)
   *   - Load all modules whether installed or not
   *
   * @return array
   *   An array mapping config object names with directories.
   */
  public function getAllFolders() {
    // @todo: update to bring in upstream changes from the method this was
    // forked from.
    if (!isset($this->folders)) {
      $this->folders = [];
      $this->folders += $this->getCoreNames();

      $install_profile = Settings::get('install_profile');
      $profile = $this->installProfile;
      $extensions = $this->configStorage->read('core.extension');
      // @todo Remove this scan as part of https://www.drupal.org/node/2186491
      $listing = new ExtensionDiscovery(\Drupal::root());

      // CHANGED START: Add profile directories for any bundles that use a
      // profile.
      $listing->setProfileDirectoriesFromSettings();
      $profile_directories = $listing->getProfileDirectories();
      if ($this->includeProfile) {
        // Add any profiles used in bundles.
        /** @var \Drupal\features\FeaturesAssignerInterface $assigner */
        $assigner = \Drupal::service('features_assigner');
        $bundles = $assigner->getBundleList();
        foreach ($bundles as $bundle_name => $bundle) {
          if ($bundle->isProfile()) {
            // Register the profile directory.
            $profile_directory = 'profiles/' . $bundle->getProfileName();
            if (is_dir($profile_directory)) {
              $profile_directories[] = $profile_directory;
            }
          }
        }
      }
      $listing->setProfileDirectories($profile_directories);
      // CHANGED END.
      if (!empty($extensions['module'])) {

        // CHANGED START: Find ANY modules, not just installed ones.
        // $modules = $extensions['module'];.
        $module_list_scan = $listing->scan('module');
        $modules = $module_list_scan;
        // CHANGED END.
        // Remove the install profile as this is handled later.
        unset($modules[$install_profile]);
        $profile_list = $listing->scan('profile');
        if ($profile && isset($profile_list[$profile])) {
          // Prime the drupal_get_filename() static cache with the profile info
          // file location so we can use drupal_get_path() on the active profile
          // during the module scan.
          // @todo Remove as part of https://www.drupal.org/node/2186491
          $this->extensionPathResolver->getPathname('profile', $profile);
        }
        // CHANGED START: Put Features modules first in list returned.
        // to allow features to override config provided by other extensions.
        $featuresManager = \Drupal::service('features.manager');
        $features_list = [];
        $module_list = [];
        foreach (array_keys($module_list_scan) as $module) {
          if ($featuresManager->isFeatureModule($module_list_scan[$module])) {
            $features_list[$module] = $module_list_scan[$module];
          }
          else {
            $module_list[$module] = $module_list_scan[$module];
          }
        }
        $this->folders += $this->getComponentNames($features_list);
        $this->folders += $this->getComponentNames($module_list);
        // CHANGED END.
      }
      if (!empty($extensions['theme'])) {
        $theme_list_scan = $listing->scan('theme');
        foreach (array_keys($extensions['theme']) as $theme) {
          if (isset($theme_list_scan[$theme])) {
            $theme_list[$theme] = $theme_list_scan[$theme];
          }
        }
        $this->folders += $this->getComponentNames($theme_list);
      }

      if ($this->includeProfile) {
        // The install profile can override module default configuration. We do
        // this by replacing the config file path from the module/theme with the
        // install profile version if there are any duplicates.
        if (isset($profile)) {
          if (!isset($profile_list)) {
            $profile_list = $listing->scan('profile');
          }
          if (isset($profile_list[$profile])) {
            $profile_folders = $this->getComponentNames([$profile_list[$profile]]);
            $this->folders = $profile_folders + $this->folders;
          }
        }
      }
    }

    return $this->folders;
  }

}
