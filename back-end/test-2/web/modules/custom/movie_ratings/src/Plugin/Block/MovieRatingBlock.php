<?php

namespace Drupal\movie_ratings\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\movie_ratings\MovieRatingService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Movie Rating Block.
 *
 * @Block(
 *   id = "movie_rating_block",
 *   admin_label = @Translation("Movie Rating Block"),
 *   category = @Translation("Movie Ratings")
 * )
 */
class MovieRatingBlock extends BlockBase implements ContainerFactoryPluginInterface {


  /**
   * Route match object.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Movie rating service object
   *
   * @var \Drupal\movie_ratings\MovieRatingService
   */
  protected $movieRating;

  /**
   * Constructs a new MovieRatingBlock instance.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RouteMatchInterface $route_match, MovieRatingService $movie_rating) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->routeMatch = $route_match;
    $this->movieRating = $movie_rating;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_route_match'),
      $container->get('movie_ratings.movie_rating_service'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Get current node
    $currentNode = $this->routeMatch->getParameter('node');

    // Check if we're on a movie page
    if (!$currentNode || $currentNode->bundle() !== 'movies') {
      return []; // Don't show block on non-movie pages
    }

    $movieId = $currentNode->id();

    $averageRatings = $this->movieRating->getAverageRating($movieId);

    // Get rating form
    $form = \Drupal::formBuilder()->getForm(
      'Drupal\movie_ratings\Form\MovieRatingForm',
      $movie_id = $movieId
    );

    return [
      '#theme' => 'movie_rating_block',
      '#movie_id' => $movie_id,
      '#average_rating' => $averageRatings['average'],
      '#total_votes' => $averageRatings['count'],
      '#rating_form' => $form,
      '#attached' => [
        'library' => ['movie_ratings/rating_form'],
      ],
    ];
  }

}
