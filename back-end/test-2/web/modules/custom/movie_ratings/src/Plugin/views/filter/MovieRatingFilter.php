<?php

namespace Drupal\movie_ratings\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\InOperator;

/**
 * Filter handler to provide select list filtering for movie rating.
 *
 * @ViewsFilter("movie_rating_select")
 */
class MovieRatingFilter extends InOperator {

  /**
   * {@inheritdoc}
   */
  public function getValueOptions() {
    if (!isset($this->valueOptions)) {
      $this->valueOptions = [
        '1' => $this->t('1'),
        '2' => $this->t('2'),
        '3' => $this->t('3'),
        '4' => $this->t('4'),
        '5' => $this->t('5'),
      ];
    }
    return $this->valueOptions;
  }

  /**
   * {@inheritdoc}
   */
  public function canExpose() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function canAllowMultiple() {
    return FALSE;
  }

}
