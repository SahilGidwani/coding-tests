<?php

namespace Drupal\movie_ratings\Form;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\honeypot\HoneypotService;
use Drupal\movie_ratings\MovieRatingService;

/**
 * Class MovieRatingForm.
 *
 * Provides a form for rating a movie.
 */
class MovieRatingForm extends FormBase {

  /**
   * Movie rating service object.
   *
   * @var \Drupal\movie_ratings\MovieRatingService
   */
  protected $movieRating;

  /**
   * Current route object.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $currentRoute;

  /**
   * Honeypot service object.
   *
   * @var \Drupal\honeypot\HoneypotService
   */
  protected $honeypotService;

  /**
   * Construct MovieRatingForm object.
   *
   * @param \Drupal\movie_ratings\MovieRatingService $movieRating
   *   Movie rating service object.
   * @param \Drupal\Core\Routing\RouteMatchInterface $currentRoute
   *   Current route object.
   * @param \Drupal\honeypot\HoneypotService $honeypotService
   *   Honeypot service object.
   */
  public function __construct(MovieRatingService $movieRating, RouteMatchInterface $currentRoute, HoneypotService $honeypotService) {
    $this->movieRating = $movieRating;
    $this->currentRoute = $currentRoute;
    $this->honeypotService = $honeypotService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('movie_ratings.movie_rating_service'),
      $container->get('current_route_match'),
      $container->get('honeypot')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'movie_ratings_form';
  }

  /**
   * {@inheritdoc}
   *
   * Build the form for rating a movie.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $movie_id = NULL) {

    // Get current node from route.
    $currentNode = $this->currentRoute->getParameter('node');

    $movieId = $movie_id ?? $currentNode->id();

    // Form wrapper.
    $form['#prefix'] = '<div id="movie-rating-form-wrapper">';
    $form['#suffix'] = '</div>';

    $form['movie_id'] = [
      '#type' => 'hidden',
      '#value' => $movieId,
    ];

    // If user has already rated don't build form field and display message.
    if ($this->movieRating->hasUserAlreadyRated($movieId)) {
      $ratingAverage = $this->movieRating->getAverageRating($movieId);

      $form['already_rated'] = [
        '#markup' => '<div class="alert alert-info">' .
        $this->t('You have already rated this movie. Current average: @avg/5 (@count votes)', [
          '@avg' => $ratingAverage['average'],
          '@count' => $ratingAverage['count'],
        ]) . '</div>',
      ];

      return $form;
    }

    // Used stars as label for radios in order to work with stars hover effect.
    $form['rating'] = [
      '#type' => 'radios',
      '#title' => $this->t('Rate this movie'),
      '#options' => [
        '1' => $this->t('★'),
        '2' => $this->t('★'),
        '3' => $this->t('★'),
        '4' => $this->t('★'),
        '5' => $this->t('★'),
      ],
      '#required' => TRUE,
      '#attributes' => [
        'class' => ['movie-rating-radios'],
      ],
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit Rating'),
      '#attributes' => [
        'class' => ['btn', 'btn-primary', 'movie-rating-submit'],
      ],
    ];

    $form['#attached']['library'][] = 'movie_ratings/rating_form';

    $this->honeypotService->addFormProtection($form, $form_state, ['honeypot']);

    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * Handle form submission.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $movieId = $form_state->getValue('movie_id');
    $rating = $form_state->getValue('rating');

    // Submit the rating.
    $result = $this->movieRating->submitRating($movieId, $rating);

    if (!empty($result['success'])) {
      // Add a success message (will show on the reloaded page).
      $this->messenger()->addStatus($result['message']);
    }
    else {
      $this->messenger()->addError($result['message'] ?? $this->t('Unable to save rating.'));
    }

    // Redirect back to the node canonical page.
    $form_state->setRedirect('entity.node.canonical', ['node' => $movieId]);
  }

  /**
   * {@inheritdoc}
   *
   * Validate form.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $rating = $form_state->getValue('rating');

    if (!is_numeric($rating) || $rating < 1 || $rating > 5) {
      $form_state->setErrorByName('rating', $this->t('Please select a valid rating between 1 and 5.'));
    }

    $movie_id = $form_state->getValue('movie_id');
    if (!is_numeric($movie_id) || $movie_id <= 0) {
      $form_state->setErrorByName('movie_id', $this->t('Invalid movie ID.'));
    }
  }

}
