<?php

namespace Drupal\movie_ratings\Form;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\movie_ratings\MovieRatingService;

class MovieRatingForm extends FormBase {

  /**
   * Movie rating service object
   *
   * @var \Drupal\movie_ratings\MovieRatingService
   */
  protected $movieRating;

  /**
   * Current route object
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $currentRoute;

  /**
   * Construct MovieRatingForm object
   */
  public function __construct(MovieRatingService $movieRating, RouteMatchInterface $currentRoute) {
    $this->movieRating = $movieRating;
    $this->currentRoute = $currentRoute;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('movie_ratings.movie_rating_service'),
      $container->get('current_route_match')
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
   */
  public function buildForm(array $form, FormStateInterface $form_state, $movie_id = NULL) {

    $currentNode = $this->currentRoute->getParameter('node');

    $movieId =  $movie_id ?? $currentNode->id();

    // Form wrapper for ajax response after form submit.
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
            '@count' => $ratingAverage['count']
          ]) . '</div>',
      ];

      return $form;
    }

    $form['rating'] = [
      '#type' => 'radios',
      '#title' => $this->t('Rate this movie'),
      '#options' => [
        '1' => $this->t('1'),
        '2' => $this->t('2'),
        '3' => $this->t('3'),
        '4' => $this->t('4'),
        '5' => $this->t('5'),
      ],
      '#required' => TRUE,
      '#attributes' => [
        'class' => ['movie-rating-radios']
      ]
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit Rating'),
      '#attributes' => [
        'class' => ['btn', 'btn-primary', 'movie-rating-submit'],
      ],
      '#ajax' => [
        'callback' => '::ajaxSubmitCallback',
        'wrapper' => 'movie-rating-form-wrapper',
        'method' => 'replace',
        'effect' => 'fade',
        'progress' => [
          'type' => 'throbber',
          'message' => $this->t('Submitting rating...'),
        ],
      ],
    ];

    $form['#attached']['library'][] = 'movie_ratings/rating_form';

    return $form;
  }

  /**
   * AJAX callback for rating submission.
   */
  public function ajaxSubmitCallback(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    // If form has errors, return form with error messages
    if ($form_state->hasAnyErrors()) {
      $response->addCommand(new ReplaceCommand('#movie-rating-form-wrapper', $form));
      return $response;
    }

    $movie_id = $form_state->getValue('movie_id');
    $rating = $form_state->getValue('rating');

    // Submit the rating
    $result = $this->movieRating->submitRating($movie_id, $rating);

    if ($result['success']) {
      // Get updated rating data
      $rating_data = $this->movieRating->getAverageRating($movie_id);

      $success_message = '<div class="alert alert-success">' .
        $result['message'] . '<br>' .
        $this->t('New average rating: @avg/5 (@count votes)', [
          '@avg' => $rating_data['average'],
          '@count' => $rating_data['count']
        ]) . '</div>';

      $response->addCommand(new HtmlCommand('#movie-rating-form-wrapper', $success_message));
    }
    else {
      $error_message = '<div class="alert alert-danger">' . $result['message'] . '</div>';
      $response->addCommand(new HtmlCommand('#movie-rating-form-wrapper', $error_message));
    }

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Submission is being handled via AJAX handles, but this is required by FormBase
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $rating = $form_state->getValue('rating');

    if (empty($rating)) {
      $form_state->setErrorByName('rating', $this->t('Please select a rating.'));
    }

    if ($rating < 1 || $rating > 5) {
      $form_state->setErrorByName('rating', $this->t('Rating must be between 1 and 5.'));
    }
  }
}
