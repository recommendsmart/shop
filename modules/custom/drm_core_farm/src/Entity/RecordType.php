<?php

namespace Drupal\drm_core_farm\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\Core\Entity\EntityDescriptionInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\drm_core_farm\FarmTypeInterface;
use Drupal\Core\Entity\RevisionableEntityBundleInterface;

/**
 * DRM Record Type Entity Class.
 *
 * @ConfigEntityType(
 *   id = "drm_core_record_type",
 *   label = @Translation("DRM Core Record type"),
 *   bundle_of = "drm_core_record",
 *   config_prefix = "type",
 *   handlers = {
 *     "access" = "Drupal\drm_core_farm\RecordTypeAccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\drm_core_farm\Form\RecordTypeForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *     "list_builder" = "Drupal\drm_core_farm\FarmTypeListBuilder",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     },
 *   },
 *   admin_permission = "administer record types",
 *   entity_keys = {
 *     "id" = "type",
 *     "label" = "name",
 *   },
 *   config_export = {
 *     "name",
 *     "type",
 *     "description",
 *     "locked",
 *     "primary_fields",
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/drm-core/record-types/add",
 *     "edit-form" = "/admin/structure/drm-core/record-types/{drm_core_record_type}",
 *     "delete-form" = "/admin/structure/drm-core/record-types/{drm_core_record_type}/delete",
 *   }
 * )
 */
class RecordType extends ConfigEntityBundleBase implements FarmTypeInterface, EntityDescriptionInterface, RevisionableEntityBundleInterface {

  /**
   * The machine-readable name of this type.
   *
   * @var string
   */
  public $type;

  /**
   * The human-readable name of this type.
   *
   * @var string
   */
  public $name;

  /**
   * A brief description of this type.
   *
   * @var string
   */
  public $description;

  /**
   * Whether or not this type is locked.
   *
   * A boolean indicating whether this type is locked or not, locked record
   * type cannot be edited or disabled/deleted.
   *
   * @var bool
   */
  public $locked;

  /**
   * Primary fields.
   *
   * An array of key-value pairs, where key is the primary field type and value
   * is real field name used for this type.
   *
   * @var array
   */
  public $primary_fields;

  /**
   * Should new entities of this bundle have a new revision by default.
   *
   * @var bool
   */
  protected $new_revision = TRUE;

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->type;
  }

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage, array &$values) {
    parent::preCreate($storage, $values);

    // Ensure default values are set.
    $values += [
      'locked' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function getNames() {
    $record_types = RecordType::loadMultiple();
    $record_types = array_map(function ($record_type) {
      return $record_type->label();
    }, $record_types);
    return $record_types;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function setDescription($description) {
    $this->description = $description;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function shouldCreateNewRevision() {
    return $this->new_revision;
  }

}