<?php

namespace Drupal\charts_c3\Settings\CThree;

/**
 * Chart Label.
 */
class ChartLabel implements \JsonSerializable {

  private $rotation;

  /**
   * Get Rotation.
   *
   * @return mixed
   *   Rotation.
   */
  public function getRotation() {
    return $this->rotation;
  }

  /**
   * Set Rotation.
   *
   * @param mixed $rotation
   *   Rotation.
   */
  public function setRotation($rotation) {
    $this->rotation = $rotation;
  }

  /**
   * Json Serialize.
   *
   * @return array
   *   Json Serialize.
   */
  #[\ReturnTypeWillChange]
  public function jsonSerialize() {
    $vars = get_object_vars($this);

    return $vars;
  }

}
