<?php

namespace Drupal\movie_ratings;

use Drupal\Core\Database\Connection;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;


class MovieRatingService {

  /**
   * Databse connection
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Current user
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $user;

  /**
   * Request stack
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $request;

  /**
   * Time service
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $timeObject;

  /**
   * Logger factory
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * Construct MovieRating service object
   *
   * @param \Drupal\Core\Database\Connection $database
   * @param \Drupal\Core\Session\AccountInterface $user
   * @param \Symfony\Component\HttpFoundation\RequestStack $request
   * @param \Drupal\Component\Datetime\TimeInterface $timeObject
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   */
  public function __construct(Connection $database, AccountInterface $user, RequestStack $request, TimeInterface $timeObject, LoggerChannelFactoryInterface $loggerFactory)
  {
    $this->database = $database;
    $this->user = $user;
    $this->request = $request;
    $this->timeObject = $timeObject;
    $this->loggerFactory = $loggerFactory;
  }

  /**
   * This function implements rating submission for a movie
   *
   * @param int $movie_id
   * @param int $rating
   *
   * @return array
   */
  public function submitRating($movieId, $rating)
  {
    $userIp = $this->request->getCurrentRequest()->getClientIp();

    // Check if rating value is valid.
    if ($rating < 1 || $rating > 5) {
      return ['message' => 'Invalid rating value.', 'success' => FALSE];
    }

    // Check if user has already rated the movie with current IP.
    if ($this->hasUserAlreadyRated($movieId, $userIp)) {
      return ['message' => 'You have already rated this movie.', 'success' => FALSE];
    }

    try {
      $this->database->insert('movie_ratings')
        ->fields([
          'movie_id' => $movieId,
          'rating' => $rating,
          'user_ip' => $userIp,
          'created' => $this->timeObject->getRequestTime()
        ])
        ->execute();
      return ['message' => 'Thankyou for sharing you rating', 'success' => TRUE];
    } catch (\Exception $e) {
      $this->loggerFactory->get('movie_ratings')->error('Rating submission failed: @err', ['@err' => $e->getMessage()]);
      return ['message' => 'Failed to submit rating.', 'success' => FALSE];
    }
  }

  /**
   * This function checks if user has already rated the movie with current IP.
   */
  public function hasUserAlreadyRated($movieId, $userIp = NULL) {

    if ($userIp === NULL) {
      $userIp = $this->request->getCurrentRequest()->getClientIp();
    }

    // Search if user has already rated the movie with current IP.
    $searchQuery = $this->database->select('movie_ratings', 'mr')
      ->condition('movie_id', $movieId)
      ->condition('user_ip', $userIp)
      ->countQuery();

    // If query returns result then return TRUE otherwise return FALSE
    return $searchQuery->execute()->fetchField() > 0;
  }

  /**
   * This function queries through the table and calculate the average rating for a movie.
   * @param int $movieId
   */
  public function getAverageRating($movieId) {
    $searchQuery = $this->database->select('movie_ratings', 'mr')
      ->fields('mr', ['rating'])
      ->condition('movie_id', $movieId);

    $ratings = $searchQuery->execute()->fetchCol();

    if (empty($ratings)) {
      return ['count' => 0, 'average' => 0];
    }

    $average = array_sum($ratings) / count($ratings);
    return ['count' => count($ratings), 'average' => $average];

  }
}
